<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\CustomerAccountDetails;
use App\Models\RecurringPayment;
use App\Models\SavingGoal;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CustomerDashboardController extends Controller
{
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');
        if ($request->boolean('from_setup') && Auth::check() && Auth::user()->has_completed_setup != true) {
            $u = User::where('id', Auth::user()->id)->first();
            if ($u) {
                $u->has_completed_setup = true;
                $u->save();
                Auth::user()->has_completed_setup = true;
            }
        }

        $customer = User::where('id', Auth::user()->id)->first();

        if (Auth::user()->has_completed_setup == true) {

            $userId = Auth::id();
            $tz  = config('app.timezone');
            $now = Carbon::now($tz);

            // ============================================================
            // PERIODO — identico al BudgetController
            // ============================================================

            $periodBudget = Budget::where('user_id', $userId)
                ->whereNotIn('category_name', ['uncategorised', 'salary'])
                ->where('amount', '>', 0)
                ->orderByDesc('id')
                ->first();

            $periodStart = $periodBudget
                ? Carbon::parse($periodBudget->budget_start_date, $tz)->startOfDay()
                : $now->copy()->startOfMonth()->startOfDay();

            $periodEnd = $periodBudget
                ? Carbon::parse($periodBudget->budget_end_date, $tz)->endOfDay()
                : $now->copy()->endOfMonth()->endOfDay();

            $budgetStartDate = $periodStart->toDateString();
            $budgetEndDate   = $periodEnd->toDateString();

            // Se il periodo budget è scaduto, reindirizza al riepilogo
            if ($now->gt($periodEnd->copy()->endOfDay())) {
                $hasBudget = Budget::where('user_id', $userId)
                    ->whereDate('budget_end_date', $budgetEndDate)
                    ->where('category_name', '!=', 'salary')
                    ->where('category_name', '!=', 'uncategorised')
                    ->where('amount', '>', 0)
                    ->exists();

                if ($hasBudget && !$request->has('skip_summary')) {
                    return redirect()->route('budget.period-summary');
                }
            }

            // ============================================================
            // BANK ACCOUNTS
            // ============================================================

            $bankQuery = BankAccount::query();

            $hasBAUserIdCol     = Schema::hasColumn('bank_accounts', 'user_id');
            $hasBACustomerIdCol = Schema::hasColumn('bank_accounts', 'customer_id');

            if ($hasBAUserIdCol || $hasBACustomerIdCol) {
                $bankQuery->where(function ($q) use ($userId, $hasBAUserIdCol, $hasBACustomerIdCol) {
                    if ($hasBAUserIdCol)     $q->orWhere('user_id', $userId);
                    if ($hasBACustomerIdCol) $q->orWhere('customer_id', $userId);
                });
            } else {
                $bankQuery->whereRaw('1=0');
            }

            $bankAccounts = $bankQuery->get();

            $bankAccountIds = $bankAccounts->pluck('id')->filter()->values();

            $txBase = Transaction::query()
                ->where('user_id', $userId)
                ->whereIn('bank_account_id', $bankAccountIds)
                ->where(function ($q) {
                    $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                });

            $incomeByAccount = (clone $txBase)
                ->where('transaction_type', 'income')
                ->selectRaw('bank_account_id, COALESCE(SUM(amount),0) as total')
                ->groupBy('bank_account_id')
                ->pluck('total', 'bank_account_id');

            $expenseByAccount = (clone $txBase)
                ->where('transaction_type', 'expense')
                ->selectRaw('bank_account_id, COALESCE(SUM(ABS(amount)),0) as total')
                ->groupBy('bank_account_id')
                ->pluck('total', 'bank_account_id');

            $bankAccounts = $bankAccounts->map(function ($acc) use ($incomeByAccount, $expenseByAccount) {
                $in  = (float) ($incomeByAccount[$acc->id] ?? 0);
                $out = (float) ($expenseByAccount[$acc->id] ?? 0);
                $acc->current_balance = (float) $acc->starting_balance + $in - $out;
                return $acc;
            });

            $typeGroups = [
                'bank'        => ['current_account', 'current', 'bank', 'current account', 'checking'],
                'savings'     => ['savings_account', 'savings'],
                'credit_card' => ['credit_card', 'credit card', 'card'],
                'pension'     => ['pension', 'pensions'],
                'investment'  => ['investment', 'investment_account', 'investments', 'isa_account', 'isa', 'market', 'pea', 'pee'],
            ];

            $sumByTypes = function ($accounts, array $types) {
                return (float) $accounts
                    ->whereIn('account_type', $types)
                    ->sum(function ($a) {
                        return (float) ($a->current_balance ?? $a->starting_balance ?? 0);
                    });
            };

            $cashSavings           = $sumByTypes($bankAccounts, $typeGroups['bank']);
            $savingsAmount         = $sumByTypes($bankAccounts, $typeGroups['savings']);
            $credit_card           = $sumByTypes($bankAccounts, $typeGroups['credit_card']);
            $pensionAccountsTotal  = $sumByTypes($bankAccounts, $typeGroups['pension']);
            $investmentAmountTotal = $sumByTypes($bankAccounts, $typeGroups['investment']);

            $networth = (float) $bankAccounts->sum(function ($a) {
                return (float) ($a->current_balance ?? $a->starting_balance ?? 0);
            });

            // ============================================================
            // BUDGET + TRANSACTIONS — identico al BudgetController
            // ============================================================

            $totalBudget = Budget::where('user_id', $userId)
                ->whereDate('budget_start_date', '<=', $budgetEndDate)
                ->whereDate('budget_end_date', '>=', $budgetStartDate)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->sum('amount');

            $expenseAgg = Transaction::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->where('transaction_type', 'expense')
                ->where(function ($q) {
                    $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                })
                ->selectRaw('
                    SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
                    SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
                ')
                ->first();

            $amountSpent     = max(0.0, (float)($expenseAgg->outflow ?? 0) - (float)($expenseAgg->refunds ?? 0));
            $remainingBudget = $totalBudget - $amountSpent;

            $transactions = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('category_name', '!=', 'salary')
                ->get();

            $income = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'income')
                ->sum('amount');

            $expense = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'expense')
                ->sum(\DB::raw('ABS(amount)'));

            $recurringPayments = RecurringPayment::where('user_id', $userId)->get();

            // ============================================================
            // NOTIFICHE BUDGET (80% e 100%)
            // ============================================================

            $budgetAlerts = [];
            $alertBudgetItems = Budget::where('user_id', $userId)
                ->whereDate('budget_start_date', '<=', $budgetEndDate)
                ->whereDate('budget_end_date', '>=', $budgetStartDate)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->where('amount', '>', 0)
                ->get();

            $totalBudgetedSpentDash = 0;

            foreach ($alertBudgetItems as $bi) {
                $agg = Transaction::where('user_id', $userId)
                    ->whereBetween('date', [$periodStart, $periodEnd])
                    ->where('transaction_type', 'expense')
                    ->where(function ($q) {
                        $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                    })
                    ->where(function ($q) use ($bi) {
                        $q->whereRaw('LOWER(category_name) = ?', [mb_strtolower($bi->category_name)]);
                        if ($bi->category_id) {
                            $q->orWhere('category_id', $bi->category_id);
                        }
                    })
                    ->selectRaw('
                        SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
                        SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
                    ')
                    ->first();

                $catSpent = max(0.0, (float)($agg->outflow ?? 0) - (float)($agg->refunds ?? 0));
                $totalBudgetedSpentDash += $catSpent;

                $pct = (float)$bi->amount > 0 ? round(($catSpent / (float)$bi->amount) * 100) : 0;

                if ($pct >= 100) {
                    $budgetAlerts[] = [
                        'category' => str_replace('_', ' ', $bi->category_name),
                        'pct'      => $pct,
                        'spent'    => $catSpent,
                        'budget'   => (float)$bi->amount,
                        'over'     => $catSpent - (float)$bi->amount,
                        'level'    => 'danger',
                    ];
                } elseif ($pct >= 80) {
                    $budgetAlerts[] = [
                        'category'  => str_replace('_', ' ', $bi->category_name),
                        'pct'       => $pct,
                        'spent'     => $catSpent,
                        'budget'    => (float)$bi->amount,
                        'remaining' => (float)$bi->amount - $catSpent,
                        'level'     => 'warning',
                    ];
                }
            }

            // Spese non previste
            $totalAllExpensesDash = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$periodStart, $periodEnd])
                ->where('transaction_type', 'expense')
                ->where(function ($q) {
                    $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                })
                ->sum(\DB::raw('ABS(amount)'));

            $extraExpensesDash = max(0, $totalAllExpensesDash - $totalBudgetedSpentDash);
            if ($extraExpensesDash > 0) {
                $budgetAlerts[] = [
                    'category' => 'spese non previste',
                    'pct'      => 0,
                    'spent'    => $extraExpensesDash,
                    'budget'   => 0,
                    'over'     => $extraExpensesDash,
                    'level'    => 'info',
                ];
            }

            // Notifica budget totale superato
            if ($totalBudget > 0 && $amountSpent > $totalBudget) {
                array_unshift($budgetAlerts, [
                    'category' => 'budget totale',
                    'pct'      => round(($amountSpent / $totalBudget) * 100),
                    'spent'    => $amountSpent,
                    'budget'   => $totalBudget,
                    'over'     => $amountSpent - $totalBudget,
                    'level'    => 'danger',
                ]);
            }

            // ============================================================
            // OBIETTIVI DI RISPARMIO
            // Precalcoliamo i saldi qui usando $incomeByAccount e
            // $expenseByAccount che abbiamo già, così il widget
            // non fa nessuna query aggiuntiva.
            // ============================================================

            try {
                $savingGoals = SavingGoal::where('user_id', $userId)
                    ->where('status', 'active')
                    ->with('bankAccount')
                    ->get();

                foreach ($savingGoals as $goal) {
                    $accId = $goal->bank_account_id;
                    $startBal = (float) ($goal->bankAccount->starting_balance ?? 0);
                    $in  = (float) ($incomeByAccount[$accId] ?? 0);
                    $out = (float) ($expenseByAccount[$accId] ?? 0);
                    $goal->computed_balance = $startBal + $in - $out;
                }

                $savingGoals = $savingGoals->where('status', 'active');
            } catch (\Throwable $e) {
                $savingGoals = collect();
            }

            return view('customer.pages.dashboard', compact(
                'customer',
                'credit_card',
                'cashSavings',
                'savingsAmount',
                'networth',
                'remainingBudget',
                'amountSpent',
                'totalBudget',
                'transactions',
                'income',
                'expense',
                'recurringPayments',
                'bankAccounts',
                'budgetStartDate',
                'budgetEndDate',
                'pensionAccountsTotal',
                'investmentAmountTotal',
                'budgetAlerts',
                'savingGoals'
            ));
        }

        // NOT COMPLETED SETUP
        $cashSavings = 0;
        $savingsAmount = 0;
        $credit_card = 0;
        $networth = 0;
        $remainingBudget = 0;
        $amountSpent = 0;
        $totalBudget = 0;
        $budgetStartDate = Carbon::now()->format('Y-m-d');
        $budgetEndDate = Carbon::now()->format('Y-m-d');
        $pensionAccountsTotal = 0;
        $investmentAmountTotal = 0;

        $transactions = collect();
        $income = 0;
        $expense = 0;
        $recurringPayments = collect();
        $bankAccounts = collect();
        $budgetAlerts = [];
        $savingGoals = collect();

        return view('customer.pages.dashboard', compact(
            'customer',
            'cashSavings',
            'savingsAmount',
            'credit_card',
            'networth',
            'remainingBudget',
            'amountSpent',
            'totalBudget',
            'transactions',
            'income',
            'expense',
            'recurringPayments',
            'bankAccounts',
            'budgetStartDate',
            'budgetEndDate',
            'pensionAccountsTotal',
            'investmentAmountTotal',
            'budgetAlerts',
            'savingGoals'
        ));
    }
}
