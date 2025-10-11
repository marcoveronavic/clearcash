<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerBankAccountController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Subquery: somme entrate (no internal_transfer)
        $incomeSub = Transaction::query()
            ->selectRaw('COALESCE(SUM(amount),0)')
            ->whereColumn('bank_account_id', 'bank_accounts.id')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->where('transaction_type', 'income');

        // Subquery: somme uscite (no internal_transfer) in ABS
        $expenseSub = Transaction::query()
            ->selectRaw('COALESCE(SUM(ABS(amount)),0)')
            ->whereColumn('bank_account_id', 'bank_accounts.id')
            ->where('user_id', $userId)
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->where('transaction_type', 'expense');

        // Carico i conti con le due subquery e calcolo il current_balance
        $bankAccounts = BankAccount::query()
            ->where('user_id', $userId)
            ->select('bank_accounts.*')
            ->selectSub($incomeSub, 'income_sum')
            ->selectSub($expenseSub, 'expense_sum')
            ->with(['transactions' => function ($q) use ($userId) {
                $q->where('user_id', $userId)->orderBy('date', 'desc');
            }])
            ->orderBy('account_name', 'asc')
            ->get()
            ->map(function ($acc) {
                $in  = (float) ($acc->income_sum ?? 0);
                $out = (float) ($acc->expense_sum ?? 0);
                $acc->current_balance = (float) $acc->starting_balance + $in - $out;
                return $acc;
            });

        return view('customer.pages.bank-accounts.index', compact('bankAccounts'));
    }

    public function store(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name_of_bank_account'          => ['required', 'string'],
            'bank_account_type'             => ['required', 'string'],
            'bank_account_starting_balance' => ['required', 'numeric'],
        ]);

        BankAccount::create([
            'account_name'     => $validated['name_of_bank_account'],
            'account_type'     => $validated['bank_account_type'],
            'starting_balance' => $validated['bank_account_starting_balance'],
            'user_id'          => Auth::user()->id,
        ]);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been created.');
    }

    public function update(\Illuminate\Http\Request $request, string $id)
    {
        $account = BankAccount::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $account->update([
            'account_name'     => $request['name_of_bank_account'],
            'account_type'     => $request['bank_account_type'],
            'starting_balance' => $request['bank_account_starting_balance'],
        ]);

        return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been updated.');
    }

    public function globalAddBankAccount(\Illuminate\Http\Request $request) {
        $validated = $request->validate([
            'name_of_bank_account'          => ['required', 'string'],
            'bank_account_type'             => ['required', 'string'],
            'bank_account_starting_balance' => ['required', 'numeric'],
        ]);

        BankAccount::create([
            'account_name'     => $validated['name_of_bank_account'],
            'account_type'     => $validated['bank_account_type'],
            'starting_balance' => $validated['bank_account_starting_balance'],
            'user_id'          => Auth::user()->id,
        ]);

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
