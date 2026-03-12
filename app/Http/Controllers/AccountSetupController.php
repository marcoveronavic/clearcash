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
    /* =========================
     * STEP 1 – Selezione tipo di periodo
     * ========================= */
    public function index(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup)) $accSetup = [];
        unset($accSetup['period_selection']);
        $request->session()->put('accSetup', $accSetup);

        return view('account-setup.index', ['accSetup' => $accSetup]);
    }

    public function indexStore(Request $request)
    {
        $validated = $request->validate([
            'period_selection' => ['required', 'in:first_day,last_working,fixed_date,weekly,custom'],
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

            default:
                $accSetup = array_merge($accSetup, $validated);
                $request->session()->put('accSetup', $accSetup);
                return redirect()->route('account-setup.step-two');
        }
    }

    /* =========================
     * STEP 2 – Parametri aggiuntivi
     * ========================= */
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
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

        $selection = $accSetup['period_selection'];

        if ($selection === 'fixed_date') {
            $validated = $request->validate([
                'date' => ['required', 'integer', 'between:1,31'],
            ]);
        } elseif ($selection === 'weekly') {
            $validated = $request->validate([
                'weekday' => ['required', 'integer', 'between:1,7'],
            ]);
        } elseif ($selection === 'custom') {
            $validated = $request->validate([
                'custom_start_date' => ['required', 'date'],
                'custom_end_date'   => ['required', 'date', 'after_or_equal:custom_start_date'],
            ]);

            $accSetup['custom_start_date'] = $validated['custom_start_date'];
            $accSetup['custom_end_date']   = $validated['custom_end_date'];
            $accSetup['custom_start']      = $validated['custom_start_date'];
            $accSetup['custom_end']        = $validated['custom_end_date'];

            try {
                $accSetup['date'] = (int) Carbon::parse($validated['custom_start_date'])->format('d');
            } catch (\Throwable $e) {}

            $request->session()->put('accSetup', $accSetup);
            return redirect()->route('account-setup.step-three');
        } else {
            $validated = [];
        }

        $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        return redirect()->route('account-setup.step-three');
    }

    /* =========================
     * STEP 3 – Inserimento importi (stipendio/spese)
     * ========================= */
    public function stepThreeShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

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
        ], [
            'salary_date.required'   => 'La data dello stipendio è obbligatoria.',
            'salary_amount.required' => "L'importo dello stipendio è obbligatorio.",
            'salary_amount.numeric'  => "L'importo dello stipendio deve essere un numero.",
        ]);

        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || !isset($accSetup['period_selection'])) {
            return redirect()->route('account-setup.step-one');
        }

        // Salva base_currency se inviata dal form
        $baseCurrency = strtoupper($request->input('base_currency', 'GBP'));
        $allowedCurrencies = ['GBP', 'EUR', 'USD', 'CHF'];
        if (!in_array($baseCurrency, $allowedCurrencies)) {
            $baseCurrency = 'GBP';
        }
        Auth::user()->update(['base_currency' => $baseCurrency]);

        $accSetup = array_merge($accSetup, $validated);
        $request->session()->put('accSetup', $accSetup);

        // Crea categorie di default con icone per l'utente
        $user = Auth::user();
        $defaultCategories = [
            'stipendio'          => 'fa-solid fa-wallet',
            'affitto'            => 'fa-solid fa-house',
            'mutuo'              => 'fa-solid fa-building-columns',
            'bollette'           => 'fa-solid fa-bolt',
            'spesa_alimentare'   => 'fa-solid fa-cart-shopping',
            'ristoranti'         => 'fa-solid fa-utensils',
            'trasporti'          => 'fa-solid fa-bus',
            'carburante'         => 'fa-solid fa-gas-pump',
            'abbigliamento'      => 'fa-solid fa-shirt',
            'salute'             => 'fa-solid fa-heart-pulse',
            'farmacia'           => 'fa-solid fa-prescription-bottle-medical',
            'assicurazioni'      => 'fa-solid fa-shield-halved',
            'telefono_internet'  => 'fa-solid fa-wifi',
            'abbonamenti'        => 'fa-solid fa-rotate',
            'intrattenimento'    => 'fa-solid fa-film',
            'viaggi'             => 'fa-solid fa-plane',
            'istruzione'         => 'fa-solid fa-graduation-cap',
            'cura_personale'     => 'fa-solid fa-spa',
            'casa_manutenzione'  => 'fa-solid fa-screwdriver-wrench',
            'regali'             => 'fa-solid fa-gift',
            'donazioni'          => 'fa-solid fa-hand-holding-heart',
            'tasse'              => 'fa-solid fa-file-invoice-dollar',
            'risparmi'           => 'fa-solid fa-piggy-bank',
            'investimenti'       => 'fa-solid fa-chart-line',
            'animali_domestici'  => 'fa-solid fa-paw',
            'figli'              => 'fa-solid fa-baby',
            'sport_fitness'      => 'fa-solid fa-dumbbell',
            'altro'              => 'fa-solid fa-ellipsis',
            'non_categorizzato'  => 'fa-solid fa-question',
        ];

        foreach ($defaultCategories as $catName => $catIcon) {
            BudgetCategory::firstOrCreate(
                ['user_id' => $user->id, 'name' => $catName],
                ['icon' => $catIcon]
            );
        }

        return redirect()->route('account-setup.step-four');
    }

    /* =========================
     * STEP 4 – Riepilogo
     * ========================= */
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

    /* =========================
     * STEP 5 – Banche iniziali
     * ========================= */
    public function stepFiveShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        return view('account-setup.step-five', ['accSetup' => $accSetup]);
    }

    public function stepFiveStore(Request $request)
    {
        $hasAnyBankInput =
            $request->has('name_of_bank_account') ||
            $request->has('bank_account_type') ||
            $request->has('bank_account_starting_balance') ||
            $request->has('is_salary_account');

        if (($request->boolean('skip_manual') || $request->boolean('go_next')) && !$hasAnyBankInput) {
            return redirect()->route('account-setup.step-six-investments');
        }

        $validated = $request->validate([
            'name_of_bank_account'            => ['required','array','min:1'],
            'name_of_bank_account.*'          => ['required','string','max:255'],
            'bank_account_type'               => ['required','array'],
            'bank_account_type.*'             => ['required','string','in:current_account,savings_account,isa_account,investment_account,credit_card'],
            'bank_account_starting_balance'   => ['required','array'],
            'bank_account_starting_balance.*' => ['required','numeric'],
            'is_salary_account'               => ['nullable','array'],
            'is_salary_account.*'             => ['nullable','in:0,1'],
        ]);

        $names    = array_values($validated['name_of_bank_account']);
        $types    = array_values($validated['bank_account_type']);
        $balances = array_values($validated['bank_account_starting_balance']);

        $salaryFlags = array_values($validated['is_salary_account'] ?? []);
        $salaryIndex = null;

        for ($i = 0; $i < count($salaryFlags); $i++) {
            if ((string)$salaryFlags[$i] === '1') {
                $salaryIndex = $i;
                break;
            }
        }

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $now    = now();
            $count  = min(count($names), count($types), count($balances));

            $rowsForInsert  = [];
            $rowsForSession = [];

            for ($i = 0; $i < $count; $i++) {
                $name = trim((string)$names[$i]);
                if ($name === '') continue;

                $isSalary = ($salaryIndex !== null && $i === $salaryIndex) ? 1 : 0;

                $rowsForInsert[] = [
                    'user_id'           => $userId,
                    'account_name'      => $name,
                    'account_type'      => (string)$types[$i],
                    'starting_balance'  => (float)$balances[$i],
                    'is_salary_account' => $isSalary,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $rowsForSession[] = [
                    'name_of_bank_account'          => $name,
                    'bank_account_type'             => (string)$types[$i],
                    'bank_account_starting_balance' => (float)$balances[$i],
                    'is_salary_account'             => $isSalary,
                ];
            }

            if ($rowsForInsert) {
                if ($salaryIndex !== null) {
                    DB::table('bank_accounts')->where('user_id', $userId)->update(['is_salary_account' => 0]);
                }
                DB::table('bank_accounts')->insert($rowsForInsert);
            }

            $accSetup = $request->session()->get('accSetup', []);
            $accSetup['bank_accounts'] = $rowsForSession;
            $request->session()->put('accSetup', $accSetup);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['general' => 'Could not save bank accounts: '.$e->getMessage()])->withInput();
        }

        if ($request->boolean('skip_manual') || $request->boolean('go_next')) {
            return redirect()->route('account-setup.step-six-investments');
        }

        return redirect()
            ->route('account-setup.step-five')
            ->with('success', 'Conto bancario salvato. Puoi aggiungerne altri.');
    }

    /* =========================
     * STEP 6 – Investimenti & pensioni (view)
     * ========================= */
    public function stepSixInvestmentsShow(Request $request)
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return redirect()->route('account-setup.step-one');
        }

        return view('account-setup.step-six-investments', ['accSetup' => $accSetup]);
    }

    /* =========================
     * STEP 6 – Salvataggio finale + creazione record chiave
     * ========================= */
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
        switch ($accSetup['period_selection'] ?? 'first_day') {
            case 'first_day':
                $periodStart = $now->copy()->startOfMonth();
                $periodEnd   = $now->copy()->endOfMonth();
                break;

            case 'last_working':
                $periodStart = $now->copy()->startOfMonth();
                $periodEnd   = $now->copy()->endOfMonth();
                if ($periodEnd->isSaturday())   $periodEnd->subDay();
                elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);
                break;

            case 'fixed_date':
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
                break;

            case 'weekly':
                $periodStart = $now->copy()->startOfWeek(Carbon::MONDAY);
                $periodEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                break;

            case 'custom':
            default:
                if (!empty($accSetup['custom_start']) && !empty($accSetup['custom_end'])) {
                    $periodStart = Carbon::parse($accSetup['custom_start'], $tz)->startOfDay();
                    $periodEnd   = Carbon::parse($accSetup['custom_end'],   $tz)->endOfDay();
                } else {
                    $periodStart = $now->copy()->startOfMonth();
                    $periodEnd   = $now->copy()->endOfMonth();
                }
                break;
        }

        $budget_start_date = $periodStart->toDateString();
        $budgetExpiryDate  = $periodEnd->toDateString();

        // 2) CustomerAccountDetails
        $payload = [
            'period_selection' => $accSetup['period_selection'] ?? 'first_day',
        ];
        $renewal = (int)($accSetup['date'] ?? $periodStart->day);

        if (Schema::hasColumn('customer_account_details', 'renewal_date')) {
            $payload['renewal_date'] = $renewal;
        } elseif (Schema::hasColumn('customer_account_details', 'renewal_day')) {
            $payload['renewal_day'] = $renewal;
        } elseif (Schema::hasColumn('customer_account_details', 'renewal_start')) {
            $payload['renewal_start'] = $renewal;
        }

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

        // 3) Spese (default + custom "Other") -> Budget
        $items = [];

        foreach ($accSetup as $k => $v) {
            if (preg_match('/^expense_(.+)_amount$/', (string)$k, $m)) {
                $name   = str_replace('_', ' ', strtolower($m[1]));
                $amount = (float)$v;
                if ($amount > 0) $items[$name] = $amount;
            }
        }

        $otherNames = $accSetup['other_name'] ?? [];
        $otherAmts  = $accSetup['other_amounts'] ?? [];
        if (is_array($otherNames) && is_array($otherAmts)) {
            $n = min(count($otherNames), count($otherAmts));
            for ($i = 0; $i < $n; $i++) {
                $name   = trim((string)$otherNames[$i]);
                $amount = (float)($otherAmts[$i] ?? 0);
                if ($name !== '' && $amount > 0) $items[$name] = $amount;
            }
        }

        foreach ($items as $name => $amount) {
            $cat = BudgetCategory::firstOrCreate([
                'user_id' => $user->id,
                'name'    => $name,
            ]);

            Budget::updateOrCreate(
                [
                    'user_id'           => $user->id,
                    'category_id'       => $cat->id,
                    'budget_start_date' => $budget_start_date,
                    'budget_end_date'   => $budgetExpiryDate,
                ],
                [
                    'category_name' => $cat->name,
                    'amount'        => $amount,
                ]
            );
        }

        // 4) Stipendio
        if (!empty($accSetup['salary_amount'])) {
            Budget::updateOrCreate(
                [
                    'user_id'           => $user->id,
                    'category_name'     => 'salary',
                    'budget_start_date' => $budget_start_date,
                    'budget_end_date'   => $budgetExpiryDate,
                ],
                ['amount' => (float)$accSetup['salary_amount']]
            );

            $bank = BankAccount::where('user_id', $user->id)
                ->orderByDesc('is_salary_account')
                ->orderBy('id')
                ->first();

            if ($bank) {
                RecurringPayment::where('user_id', $user->id)
                    ->whereRaw('LOWER(TRIM(COALESCE(transaction_type,""))) = ?', ['income'])
                    ->whereRaw('LOWER(TRIM(COALESCE(name,""))) = ?', ['salary'])
                    ->update(['bank_account_id' => $bank->id]);

                Transaction::where('user_id', $user->id)
                    ->whereRaw('LOWER(TRIM(COALESCE(transaction_type,""))) = ?', ['income'])
                    ->where(function ($q) {
                        $q->whereRaw('LOWER(TRIM(COALESCE(category_name,""))) = ?', ['salary'])
                            ->orWhereRaw('LOWER(TRIM(COALESCE(name,""))) = ?', ['salary']);
                    })
                    ->update(['bank_account_id' => $bank->id]);

                RecurringPayment::updateOrCreate(
                    [
                        'user_id'          => $user->id,
                        'name'             => 'Salary',
                        'bank_account_id'  => $bank->id,
                        'transaction_type' => 'income',
                    ],
                    [
                        'repeat'     => 'monthly',
                        'start_date' => $accSetup['salary_date'] ?? $now->toDateString(),
                        'amount'     => (float)$accSetup['salary_amount'],
                    ]
                );
            }
        }

        // 5) Uncategorised = 0
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
