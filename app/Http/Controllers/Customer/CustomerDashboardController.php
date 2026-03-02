<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\CustomerAccountDetails;
use App\Models\RecurringPayment;
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
        // ✅ Se arrivo da Step 7 (Done), finalizzo il setup
        if ($request->boolean('from_setup') && Auth::check() && Auth::user()->has_completed_setup != true) {
            $u = User::where('id', Auth::user()->id)->first();
            if ($u) {
                $u->has_completed_setup = true;
                $u->save();

                // aggiorna anche l'istanza in memoria
                Auth::user()->has_completed_setup = true;
            }
        }

        $customer = User::where('id', Auth::user()->id)->first();

        if (Auth::user()->has_completed_setup == true) {

            $userId = Auth::id();
            $tz  = config('app.timezone');
            $now = Carbon::now($tz);

            // ============================================================
            // ✅ PERIODO: PRENDILO DAL SETUP (CustomerAccountDetails) SE ESISTE
            // ============================================================

            $periodStart = $now->copy()->startOfMonth()->startOfDay();
            $periodEnd   = $now->copy()->endOfMonth()->endOfDay();

            $hasCustomerIdCol = Schema::hasColumn('customer_account_details', 'customer_id');
            $hasUserIdCol     = Schema::hasColumn('customer_account_details', 'user_id');

            $details = null;
            if ($hasCustomerIdCol || $hasUserIdCol) {
                $detailsQuery = CustomerAccountDetails::query();
                $detailsQuery->where(function ($q) use ($userId, $hasCustomerIdCol, $hasUserIdCol) {
                    if ($hasCustomerIdCol) $q->orWhere('customer_id', $userId);
                    if ($hasUserIdCol)     $q->orWhere('user_id', $userId);
                });
                $details = $detailsQuery->latest('id')->first();
            }

            $pick = function ($obj, array $fields) {
                foreach ($fields as $f) {
                    if ($obj && isset($obj->{$f}) && $obj->{$f} !== null && $obj->{$f} !== '') {
                        return $obj->{$f};
                    }
                }
                return null;
            };

            // 🔥 Se nel setup sono salvate date (con qualunque nome), usa quelle e basta.
            $setupStartRaw = $pick($details, [
                'custom_start', 'custom_start_date',
                'period_start', 'period_start_date',
                'budget_start_date', 'budget_period_start',
                'start_date'
            ]);

            $setupEndRaw = $pick($details, [
                'custom_end', 'custom_end_date',
                'period_end', 'period_end_date',
                'budget_end_date', 'budget_period_end',
                'end_date'
            ]);

            if (!empty($setupStartRaw) && !empty($setupEndRaw)) {
                $periodStart = Carbon::parse($setupStartRaw, $tz)->startOfDay();
                $periodEnd   = Carbon::parse($setupEndRaw,   $tz)->endOfDay();
            } else {

                // ✅ DIFFERENZA REALE: customer_account_details NON ha colonne per custom dates.
                // Se period_selection = custom, prendiamo il periodo dal Budget più recente.
                $selection = $pick($details, ['period_selection', 'period_type', 'budget_period_type']);

                if ($selection === 'custom') {
                    $b = Budget::where('user_id', $userId)
                        ->orderByDesc('id')
                        ->first(['budget_start_date', 'budget_end_date']);

                    if ($b && !empty($b->budget_start_date) && !empty($b->budget_end_date)) {
                        $periodStart = Carbon::parse($b->budget_start_date, $tz)->startOfDay();
                        $periodEnd   = Carbon::parse($b->budget_end_date,   $tz)->endOfDay();
                    }
                } else {
                    // altrimenti applica logica in base alla selection del setup
                    switch ($selection) {
                        case 'last_working':
                            $periodStart = $now->copy()->startOfMonth()->startOfDay();
                            $periodEnd   = $now->copy()->endOfMonth()->endOfDay();
                            if ($periodEnd->isSaturday()) $periodEnd->subDay();
                            elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);
                            break;

                        case 'fixed_date':
                            $renewalDay = (int) ($pick($details, ['renewal_date', 'renewal_day', 'fixed_day']) ?? 1);
                            $renewalDay = max(1, min($renewalDay, $now->daysInMonth));

                            $anchor = Carbon::create($now->year, $now->month, $renewalDay, 0, 0, 0, $tz);

                            if ($now->lt($anchor)) {
                                $periodStart = $anchor->copy()->subMonthNoOverflow()->startOfDay();
                                $periodEnd   = $anchor->copy()->subDay()->endOfDay();
                            } else {
                                $periodStart = $anchor->copy()->startOfDay();
                                $periodEnd   = $anchor->copy()->addMonthNoOverflow()->subDay()->endOfDay();
                            }
                            break;

                        case 'weekly':
                            $periodStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                            $periodEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                            break;

                        // default => mese corrente
                    }
                }
            }

            $budgetStartDate = $periodStart->toDateString();
            $budgetEndDate   = $periodEnd->toDateString();

            // ============================================================
            // ✅ BANK ACCOUNTS: prendi conti legati a user_id O customer_id
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

            // ============================================================
            // ✅ CALCOLA current_balance (starting + income - expense)
            // escludendo internal transfers
            // ============================================================

            $bankAccountIds = $bankAccounts->pluck('id')->filter()->values();

            $txBase = Transaction::query()
                ->where('user_id', $userId)
                ->whereIn('bank_account_id', $bankAccountIds)
                ->where(function ($q) {
                    $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                });

            // income per account
            $incomeByAccount = (clone $txBase)
                ->where('transaction_type', 'income')
                ->selectRaw('bank_account_id, COALESCE(SUM(amount),0) as total')
                ->groupBy('bank_account_id')
                ->pluck('total', 'bank_account_id');

            // expense per account (ABS per sicurezza: se expense è negativa o positiva, qui è "magnitude")
            $expenseByAccount = (clone $txBase)
                ->where('transaction_type', 'expense')
                ->selectRaw('bank_account_id, COALESCE(SUM(ABS(amount)),0) as total')
                ->groupBy('bank_account_id')
                ->pluck('total', 'bank_account_id');

            // Attacca current_balance ad ogni conto (come in Bank Accounts page)
            $bankAccounts = $bankAccounts->map(function ($acc) use ($incomeByAccount, $expenseByAccount) {
                $in  = (float) ($incomeByAccount[$acc->id] ?? 0);
                $out = (float) ($expenseByAccount[$acc->id] ?? 0);
                $acc->current_balance = (float) $acc->starting_balance + $in - $out;
                return $acc;
            });

            // Gruppi tipi per evitare mismatch (es. investment_account vs investment)
            $typeGroups = [
                'bank'        => ['current_account', 'current', 'bank', 'current account'],
                'savings'     => ['savings_account', 'savings'],
                'credit_card' => ['credit_card', 'credit card'],
                'pension'     => ['pension', 'pensions'],
                'investment'  => ['investment', 'investment_account', 'investments', 'isa_account', 'isa'],
            ];

            // Somma per tipi usando current_balance
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

            // Net worth = somma di tutti i current_balance (include credit card con segno così come definito dal starting_balance)
            $networth = (float) $bankAccounts->sum(function ($a) {
                return (float) ($a->current_balance ?? $a->starting_balance ?? 0);
            });

            // ============================================================
            // ✅ BUDGET + TRANSACTIONS: filtrati nel periodo del setup
            // ============================================================

            $totalBudget = Budget::where('user_id', $userId)
                ->whereDate('budget_start_date', $budgetStartDate)
                ->whereDate('budget_end_date', $budgetEndDate)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->sum('amount');

            // amountSpent: somma delle spese nel periodo (magnitudine)
            $amountSpent = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->where('transaction_type', 'expense')
                ->sum(\DB::raw('ABS(amount)'));

            $remainingBudget = $totalBudget - $amountSpent;

            $transactions = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->get();

            $income = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'income')
                ->sum('amount');

            // expense come "magnitude" (positivo)
            $expense = Transaction::where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'expense')
                ->sum(\DB::raw('ABS(amount)'));

            $recurringPayments = RecurringPayment::where('user_id', $userId)->get();

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
                'investmentAmountTotal'
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
        ));
    }
}
