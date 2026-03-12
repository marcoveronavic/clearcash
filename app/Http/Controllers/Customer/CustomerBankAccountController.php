<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerBankAccountController extends Controller
{
    private function normalizeAccountType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $t = Str::of(trim($type))
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', '_')
            ->trim('_')
            ->toString();

        return match ($t) {
            'current', 'currentaccount', 'current_account', 'bank' => 'current_account',
            'savings', 'savingsaccount', 'savings_account'          => 'savings_account',
            'isa', 'isaaccount', 'isa_account'                      => 'isa_account',
            'investment', 'investments', 'investmentaccount',
            'investment_account', 'investment_accounts'             => 'investment',
            'pension', 'pensions', 'pensionaccount',
            'pension_account', 'pension_accounts'                   => 'pension',
            'credit', 'creditcard', 'credit_card', 'credit_cards'   => 'credit_card',
            default => $t,
        };
    }

    private function getLatestBudgetPeriodForUser(int $userId): ?array
    {
        if (
            Schema::hasTable('budgets') &&
            Schema::hasColumn('budgets', 'budget_start_date') &&
            Schema::hasColumn('budgets', 'budget_end_date')
        ) {
            $b = DB::table('budgets')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first(['budget_start_date', 'budget_end_date']);

            if ($b && !empty($b->budget_start_date) && !empty($b->budget_end_date)) {
                return [$b->budget_start_date, $b->budget_end_date];
            }
        }

        return null;
    }

    private function moveSalaryTransactionsToAccount(int $userId, int $bankAccountId): void
    {
        if (!Schema::hasTable('transactions')) {
            return;
        }

        $q = Transaction::query()
            ->where('user_id', $userId)
            ->where('transaction_type', 'income')
            ->where(function ($w) {
                $w->whereRaw('LOWER(COALESCE(category_name, "")) = ?', ['salary'])
                    ->orWhereRaw('LOWER(COALESCE(name, "")) LIKE ?', ['%salary%']);
            });

        $period = $this->getLatestBudgetPeriodForUser($userId);
        if ($period) {
            $q->whereBetween('date', $period);
        }

        $q->update(['bank_account_id' => $bankAccountId]);
    }

    public function index()
    {
        $userId = Auth::id();

        $salaryAccount = BankAccount::where('user_id', $userId)
            ->where('is_salary_account', true)
            ->orderByDesc('id')
            ->first();

        if ($salaryAccount) {
            $this->moveSalaryTransactionsToAccount($userId, (int) $salaryAccount->id);
        }

        $period      = null;
        $periodStart = null;
        $periodEnd   = null;

        $latestPeriod = $this->getLatestBudgetPeriodForUser($userId);
        if ($latestPeriod) {
            $periodStart = $latestPeriod[0];
            $periodEnd   = $latestPeriod[1];
            $period      = [$periodStart, $periodEnd];
        }

        $incomeSub = Transaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('bank_account_id', 'bank_accounts.id')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->where('transaction_type', 'income');

        if ($period) {
            $incomeSub->whereBetween('date', $period);
        }

        $expenseSub = Transaction::query()
            ->selectRaw('COALESCE(SUM(ABS(amount)),0)')
            ->whereColumn('bank_account_id', 'bank_accounts.id')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->where('transaction_type', 'expense');

        if ($period) {
            $expenseSub->whereBetween('date', $period);
        }

        $bankAccounts = BankAccount::query()
            ->where('user_id', $userId)
            ->select('bank_accounts.*')
            ->selectSub($incomeSub, 'income_sum')
            ->selectSub($expenseSub, 'expense_sum')
            ->with(['transactions' => function ($q) use ($userId, $period) {
                $q->where('user_id', $userId);
                if ($period) {
                    $q->whereBetween('date', $period);
                }
                $q->orderBy('date', 'desc')->limit(10);
            }])
            ->orderByDesc('is_salary_account')
            ->orderBy('account_name', 'asc')
            ->get()
            ->map(function ($acc) use ($periodStart) {
                $in  = (float) ($acc->income_sum ?? 0);
                $out = (float) ($acc->expense_sum ?? 0);

                $acc->opening_balance       = (float) $acc->starting_balance;
                $acc->current_balance       = (float) $acc->starting_balance + $in - $out;
                $acc->recentTransactions    = $acc->transactions ?? collect();
                $acc->period_start_for_ui   = $periodStart;

                return $acc;
            });

        return view('customer.pages.bank-accounts.index', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_of_bank_account'          => ['required', 'string'],
            'bank_account_type'             => ['required', 'string'],
            'bank_account_starting_balance' => ['required', 'numeric'],
            'currency'                      => ['required', 'string', 'size:3'],
            'is_salary_account'             => ['nullable'],
        ]);

        $userId      = Auth::id();
        $accountType = $this->normalizeAccountType($validated['bank_account_type']);
        $isSalary    = $request->boolean('is_salary_account');

        DB::transaction(function () use ($validated, $userId, $accountType, $isSalary) {

            if ($isSalary) {
                BankAccount::where('user_id', $userId)->update(['is_salary_account' => false]);
            }

            $acc = BankAccount::create([
                'account_name'      => $validated['name_of_bank_account'],
                'account_type'      => $accountType,
                'starting_balance'  => $validated['bank_account_starting_balance'],
                'currency'          => strtoupper($validated['currency']),
                'is_salary_account' => $isSalary,
                'user_id'           => $userId,
            ]);

            if ($isSalary && $acc && $acc->id) {
                $this->moveSalaryTransactionsToAccount($userId, (int) $acc->id);
            }
        });

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been created.');
    }

    public function update(Request $request, string $id)
    {
        $account = BankAccount::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name_of_bank_account'          => ['required', 'string'],
            'bank_account_type'             => ['required', 'string'],
            'bank_account_starting_balance' => ['required', 'numeric'],
            'currency'                      => ['required', 'string', 'size:3'],
            'is_salary_account'             => ['nullable'],
        ]);

        $userId      = Auth::id();
        $accountType = $this->normalizeAccountType($validated['bank_account_type']);
        $isSalary    = $request->boolean('is_salary_account');

        DB::transaction(function () use ($account, $validated, $userId, $accountType, $isSalary) {

            if ($isSalary) {
                BankAccount::where('user_id', $userId)
                    ->where('id', '!=', $account->id)
                    ->update(['is_salary_account' => false]);
            }

            $account->update([
                'account_name'      => $validated['name_of_bank_account'],
                'account_type'      => $accountType,
                'starting_balance'  => $validated['bank_account_starting_balance'],
                'currency'          => strtoupper($validated['currency']),
                'is_salary_account' => $isSalary,
            ]);

            if ($isSalary) {
                $this->moveSalaryTransactionsToAccount($userId, (int) $account->id);
            }
        });

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been updated.');
    }

    public function globalAddBankAccount(Request $request)
    {
        $validated = $request->validate([
            'name_of_bank_account'          => ['required', 'string'],
            'bank_account_type'             => ['required', 'string'],
            'bank_account_starting_balance' => ['required', 'numeric'],
            'currency'                      => ['required', 'string', 'size:3'],
            'is_salary_account'             => ['nullable'],
        ]);

        $userId      = Auth::id();
        $accountType = $this->normalizeAccountType($validated['bank_account_type']);
        $isSalary    = $request->boolean('is_salary_account');

        DB::transaction(function () use ($validated, $userId, $accountType, $isSalary) {

            if ($isSalary) {
                BankAccount::where('user_id', $userId)->update(['is_salary_account' => false]);
            }

            $acc = BankAccount::create([
                'account_name'      => $validated['name_of_bank_account'],
                'account_type'      => $accountType,
                'starting_balance'  => $validated['bank_account_starting_balance'],
                'currency'          => strtoupper($validated['currency']),
                'is_salary_account' => $isSalary,
                'user_id'           => $userId,
            ]);

            if ($isSalary && $acc && $acc->id) {
                $this->moveSalaryTransactionsToAccount($userId, (int) $acc->id);
            }
        });

        return redirect()->back()->with('success', 'Bank Account has been created.');
    }

    public function destroy(string $id)
    {
        $account = BankAccount::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();

        if ($account) {
            $account->delete();
            return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been deleted.');
        }

        return redirect()->route('bank-accounts.index')->with('error', 'Bank Account not found.');
    }
}
