<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\CustomerAccountDetails;
use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountSetupInvestmentsController extends Controller
{
    public function index()
    {
        $investmentAccounts = BankAccount::query()
            ->where('user_id', Auth::id())
            ->whereIn('account_type', ['investment', 'pension'])
            ->orderByDesc('id')
            ->get();

        return view('account-setup.step-six-investments', compact('investmentAccounts'));
    }

    public function store(Request $request)
    {
        // intent: save | continue | skip
        $intent = (string) $request->input('intent', 'save');

        // Validazione arrays (matcha i name del tuo Blade)
        $validated = $request->validate([
            'name_of_pension_investment_account' => ['array'],
            'name_of_pension_investment_account.*' => ['nullable', 'string', 'max:255'],

            'pension_investment_type' => ['array'],
            'pension_investment_type.*' => ['nullable', 'in:pension,investment'],

            'pension_investment_account_starting_balance' => ['array'],
            'pension_investment_account_starting_balance.*' => ['nullable', 'numeric'],
        ]);

        $names    = $validated['name_of_pension_investment_account'] ?? [];
        $types    = $validated['pension_investment_type'] ?? [];
        $balances = $validated['pension_investment_account_starting_balance'] ?? [];

        $userId = Auth::id();

        // Salva solo le righe complete
        $savedAny = false;
        $count = max(count($names), count($types), count($balances));

        for ($i = 0; $i < $count; $i++) {
            $name = trim((string)($names[$i] ?? ''));
            $type = (string)($types[$i] ?? '');
            $bal  = $balances[$i] ?? null;

            if ($name === '' || $type === '' || $bal === null || $bal === '') {
                continue;
            }

            BankAccount::create([
                'user_id'          => $userId,
                'account_name'     => $name,
                'account_type'     => $type, // pension / investment
                'starting_balance' => (float) $bal,
            ]);

            $savedAny = true;
        }

        // redirect in base al bottone premuto
        if ($intent === 'continue' || $intent === 'skip') {

            // ✅ FINALIZZA SETUP QUI (perché questa è la vera POST che viene chiamata)
            $this->finalizeSetup($request);

            return redirect()->route('account-setup.step-seven');
        }

        // intent = save → resta sulla pagina e mostra cards
        return redirect()
            ->back()
            ->with($savedAny ? 'success' : 'error', $savedAny ? 'Saved.' : 'Nothing to save (fill Name/Type/Balance).');
    }

    /**
     * Finalizzazione setup:
     * - calcola periodo (da session accSetup)
     * - salva CustomerAccountDetails
     * - crea Budgets (spese + other + salary + uncategorised)
     * - crea recurring salary + prima transaction (se esiste almeno una banca)
     * - marca has_completed_setup
     * - pulisce sessione accSetup
     */
    private function finalizeSetup(Request $request): void
    {
        $accSetup = $request->session()->get('accSetup', []);
        if (!is_array($accSetup) || empty($accSetup)) {
            return; // niente da finalizzare
        }

        $user = Auth::user();
        if (!$user) {
            return;
        }

        $tz   = config('app.timezone');
        $now  = Carbon::now($tz);

        DB::beginTransaction();
        try {
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

            // 2) CustomerAccountDetails – mappa sul campo presente (avoid NOT NULL error)
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
                    ['amount' => (float)$accSetup['salary_amount']]
                );

                // usa la prima banca disponibile (creata allo step 5)
                $bank = BankAccount::where('user_id', $user->id)->orderBy('id')->first();

                if ($bank) {
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

                    Transaction::create([
                        'user_id'          => $user->id,
                        'name'             => 'Salary',
                        'date'             => $accSetup['salary_date'] ?? $now->toDateString(),
                        'category_name'    => 'salary',
                        'bank_account_id'  => $bank->id,
                        'amount'           => (float)$accSetup['salary_amount'],
                        'transaction_type' => 'income',
                    ]);

                    // ✅ NON aggiornare starting_balance: deve restare opening inserito nello step 5
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

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
        }
    }

    public function update(Request $request, string $id)
    {
        abort(404);
    }

    public function destroy(string $id)
    {
        abort(404);
    }
}
