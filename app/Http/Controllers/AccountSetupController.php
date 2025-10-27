<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\CustomerAccountDetails;
use App\Models\DefaultBudgetCategories;
use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AccountSetupController extends Controller
{
    /** STEP 1 – Selezione tipo di periodo. */
    public function index(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup)) $accSetup = [];
        unset($accSetup['period_selection']); // riparte la scelta
        $request->session()->put('accSetup', $accSetup);

        return view('account-setup.index', ['accSetup' => $accSetup]);
    }

    public function indexStore(Request $request)
    {
        $validated = $request->validate([
            'period_selection' => 'required|in:first_day,last_working,fixed_date,weekly,custom',
        ]);

        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup)) $accSetup = [];

        switch ($validated['period_selection']) {
            case 'first_day':
                $accSetup = array_merge($accSetup, [
                    'period_selection' => 'first_day',
                    'date'             => 1,
                ]);
                $request->session()->put('accSetup', $accSetup);
                return redirect()->route('account-setup.step-three');

            case 'last_working':
                $lastDay = Carbon::now()->endOfMonth();
                if ($lastDay->isSaturday())   $lastDay->subDay();
                elseif ($lastDay->isSunday()) $lastDay->subDays(2);
                $accSetup = array_merge($accSetup, [
                    'period_selection' => 'last_working',
                    'date'             => $lastDay->day,
                ]);
                $request->session()->put('accSetup', $accSetup);
                return redirect()->route('account-setup.step-three');

            default: // fixed_date | weekly | custom
                $accSetup = array_merge($accSetup, $validated);
                $request->session()->put('accSetup', $accSetup);
                return redirect()->route('account-setup.step-two');
        }
    }

    /** STEP 2 – Parametri aggiuntivi (es. day per fixed_date). */
    public function stepTwoShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }
        return view('account-setup.step-two', ['accSetup' => $accSetup]);
    }

    public function stepTwoStore(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|integer|between:1,31',
        ]);

        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

        $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        return redirect()->route('account-setup.step-three');
    }

    /** STEP 3 – Inserimento importi (stipendio/spese). */
    public function stepThreeShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

        // Autoseed se vuoto (comodo in dev)
        if (DefaultBudgetCategories::count() === 0) {
            (new \Database\Seeders\DefaultBudgetCategoriesSeeder())->run();
        }
        $defaultBudgetCategories = DefaultBudgetCategories::orderBy('name')->get();

        return view('account-setup.step-three', [
            'accSetup'                => $accSetup,
            'defaultBudgetCategories' => $defaultBudgetCategories,
        ]);
    }

    public function stepThreeStore(Request $request)
    {
        $validated = $request->validate([
            'salary_date'     => ['required'],
            'salary_amount'   => ['required','numeric'],
            '*_amount'        => ['nullable','numeric'],
            'other_name.*'    => ['nullable','string'],
            'other_amounts.*' => ['nullable','numeric'],
        ]);

        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

        $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        return redirect()->route('account-setup.step-four');
    }

    /** STEP 4 – Riepilogo. */
    public function stepFourShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        $totalExpenses = collect($accSetup)
            ->filter(fn ($v, $k) => preg_match('/^expense_.*_amount$/', (string)$k))
            ->map(fn ($v) => (float)$v)
            ->sum();

        $totalAmount = $totalExpenses
            + (float)($accSetup['savings_pension_amount']     ?? 0)
            + (float)($accSetup['savings_investments_amount'] ?? 0)
            + collect($accSetup['other_amounts'] ?? [])->map(fn($v)=>(float)$v)->sum();

        return view('account-setup.step-four', [
            'accSetup'    => $accSetup,
            'totalAmount' => $totalAmount,
        ]);
    }

    public function stepFourStore(Request $request)
    {
        if (empty($request->session()->get('accSetup'))) {
            return redirect()->route('account-setup.step-one');
        }
        return redirect()->route('account-setup.step-five');
    }

    /** STEP 5 – Banche iniziali. */
    public function stepFiveShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        return view('account-setup.step-five', ['accSetup' => $accSetup]);
    }

    /**
     * STEP 5 – Salva conti manuali nel DB (uno per riga).
     * Se esiste "skip_manual", prosegue direttamente allo step 6 investimenti.
     */
    public function stepFiveStore(Request $request)
    {
        // Se ho già conti collegati via Plaid e l'utente vuole proseguire
        if ($request->boolean('skip_manual')) {
            return redirect()->route('account-setup.step-six-investments');
        }

        // Validazione array dal form
        $validated = $request->validate([
            'name_of_bank_account'            => ['required','array','min:1'],
            'name_of_bank_account.*'          => ['required','string','max:255'],
            'bank_account_type'               => ['required','array'],
            'bank_account_type.*'             => ['required','string','in:current_account,savings_account,isa_account,investment_account,credit_card'],
            'bank_account_starting_balance'   => ['required','array'],
            'bank_account_starting_balance.*' => ['required','numeric'],
        ]);

        $names    = $validated['name_of_bank_account'];
        $types    = $validated['bank_account_type'];
        $balances = $validated['bank_account_starting_balance'];

        DB::beginTransaction();
        try {
            $count = min(count($names), count($types), count($balances));
            $now   = now();
            $userId = Auth::id();

            $rowsForInsert  = [];
            $rowsForSession = [];

            for ($i = 0; $i < $count; $i++) {
                $name = trim((string) $names[$i]);
                $type = (string) $types[$i];
                $bal  = (float)  $balances[$i];

                $rowsForInsert[] = [
                    'user_id'          => $userId,
                    'account_name'     => $name,
                    'account_type'     => $type,
                    'starting_balance' => $bal,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                $rowsForSession[] = [
                    'name_of_bank_account'          => $name,
                    'bank_account_type'             => $type,
                    'bank_account_starting_balance' => $bal,
                ];
            }

            if (!empty($rowsForInsert)) {
                DB::table('bank_accounts')->insert($rowsForInsert);
            }

            // aggiorno accSetup in sessione (opzionale, solo per coerenza UI)
            $accSetup = $request->session()->get('accSetup', []);
            $accSetup['bank_accounts'] = $rowsForSession;
            $request->session()->put('accSetup', $accSetup);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()
                ->withErrors(['general' => 'Could not save bank accounts: '.$e->getMessage()])
                ->withInput();
        }

        return redirect()->route('account-setup.step-six-investments')
            ->with('success', 'Bank accounts saved.');
    }

    /** STEP 6 – Investimenti & pensioni (schermata). */
    public function stepSixInvestmentsShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        return view('account-setup.step-six-investments', ['accSetup' => $accSetup]);
    }

    /** STEP 6 – Salvataggio finale + creazione record chiave. */
    public function stepSixInvestmentsStore(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        $user = Auth::user();
        $tz   = config('app.timezone');
        $now  = Carbon::now($tz);

        // 1) Period window
        if (($accSetup['period_selection'] ?? null) === 'first_day') {
            $periodStart = $now->copy()->startOfMonth();
            $periodEnd   = $now->copy()->endOfMonth();

        } elseif (($accSetup['period_selection'] ?? null) === 'last_working') {
            $periodStart = $now->copy()->startOfMonth();
            $periodEnd   = $now->copy()->endOfMonth();
            if ($periodEnd->isSaturday())   $periodEnd->subDay();
            elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);

        } elseif (($accSetup['period_selection'] ?? null) === 'fixed_date') {
            $day    = (int)($accSetup['date'] ?? 1);
            $day    = max(1, min($day, $now->daysInMonth));
            $anchor = Carbon::create($now->year, $now->month, $day, 0, 0, 0, $tz);

            if ($now->lt($anchor)) {
                $periodStart = $anchor->copy()->subMonthNoOverflow();
                $periodEnd   = $anchor->copy()->subDay();
            } else {
                $periodStart = $anchor->copy();
                $periodEnd   = $anchor->copy()->addMonthNoOverflow()->subDay();
            }

        } elseif (($accSetup['period_selection'] ?? null) === 'weekly') {
            $periodStart = $now->copy()->startOfWeek(Carbon::MONDAY);
            $periodEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY);

        } elseif (($accSetup['period_selection'] ?? null) === 'custom'
            && !empty($accSetup['custom_start']) && !empty($accSetup['custom_end'])) {
            $periodStart = Carbon::parse($accSetup['custom_start'], $tz)->startOfDay();
            $periodEnd   = Carbon::parse($accSetup['custom_end'],   $tz)->endOfDay();

        } else {
            $periodStart = $now->copy()->startOfMonth();
            $periodEnd   = $now->copy()->endOfMonth();
        }

        $budget_start_date = $periodStart->toDateString();
        $budgetExpiryDate  = $periodEnd->toDateString();

        // 2) CustomerAccountDetails – solo colonne presenti
        $payload = [
            'period_selection' => $accSetup['period_selection'] ?? 'first_day',
            'renewal_date'     => $accSetup['date'] ?? null,
        ];
        if (Schema::hasColumn('customer_account_details', 'custom_start')) {
            $payload['custom_start'] = $accSetup['custom_start'] ?? null;
        }
        if (Schema::hasColumn('customer_account_details', 'custom_end')) {
            $payload['custom_end'] = $accSetup['custom_end'] ?? null;
        }

        CustomerAccountDetails::updateOrCreate(
            ['customer_id' => $user->id],
            $payload
        );

        // 3) Budget dalle spese step 3
        $expenses = [];
        foreach ($accSetup as $k => $v) {
            if (preg_match('/^expense_(.+)_amount$/', (string)$k, $m)) {
                $slug   = strtolower(str_replace('__', '_', $m[1]));
                $amount = (float) $v;
                if ($amount > 0) $expenses[$slug] = $amount;
            }
        }

        foreach ($expenses as $slug => $amount) {
            $cat = BudgetCategory::firstOrCreate([
                'user_id' => $user->id,
                'name'    => str_replace('_', ' ', $slug),
            ]);

            Budget::updateOrCreate(
                [
                    'user_id'           => $user->id,
                    'category_id'       => $cat->id,
                    'budget_start_date' => $budget_start_date,
                    'budget_end_date'   => $budgetExpiryDate,
                ],
                [
                    'category_name'     => $cat->name,
                    'amount'            => $amount,
                ]
            );
        }

        // 4) Stipendio (budget + recurring + prima transazione)
        if (!empty($accSetup['salary_amount'])) {
            Budget::updateOrCreate(
                [
                    'user_id'           => $user->id,
                    'category_name'     => 'salary',
                    'budget_start_date' => $budget_start_date,
                    'budget_end_date'   => $budgetExpiryDate,
                ],
                ['amount' => (float) $accSetup['salary_amount']]
            );

            $bank = BankAccount::where('user_id', $user->id)->first();
            if ($bank) {
                RecurringPayment::updateOrCreate(
                    [
                        'user_id'         => $user->id,
                        'name'            => 'Salary',
                        'bank_account_id' => $bank->id,
                        'transaction_type'=> 'income',
                    ],
                    [
                        'repeat'     => 'monthly',
                        'start_date' => $accSetup['salary_date'] ?? $now->toDateString(),
                        'amount'     => (float) $accSetup['salary_amount'],
                    ]
                );

                Transaction::create([
                    'user_id'         => $user->id,
                    'name'            => 'Salary',
                    'date'            => $accSetup['salary_date'] ?? $now->toDateString(),
                    'category_name'   => 'salary',
                    'bank_account_id' => $bank->id,
                    'amount'          => (float) $accSetup['salary_amount'],
                    'transaction_type'=> 'income',
                ]);

                $bank->starting_balance = (float)$bank->starting_balance + (float)$accSetup['salary_amount'];
                $bank->save();
            }
        }

        // 5) Uncategorised sempre presente a 0
        Budget::updateOrCreate(
            [
                'user_id'           => $user->id,
                'category_name'     => 'uncategorised',
                'budget_start_date' => $budget_start_date,
                'budget_end_date'   => $budgetExpiryDate,
            ],
            ['amount' => 0]
        );

        // 6) Fine setup
        User::where('id', $user->id)->update(['has_completed_setup' => true]);
        $request->session()->forget('accSetup');

        return redirect()->route('account-setup.step-seven');
    }

    public function stepSevenShow(Request $request)
    {
        return view('account-setup.step-seven');
    }
}
