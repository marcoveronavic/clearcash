<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\RecurringPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerRecurringPayments extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $recurringPayments = RecurringPayment::where('user_id', Auth::user()->id)
            ->with('category')
            ->with('bankAccount')
            ->get();

        $categories = Budget::where('user_id', Auth::user()->id)
            ->where('amount', '>', 0)
            ->get();

        $bankAccounts = BankAccount::where('user_id', Auth::user()->id)->orderBy('account_name', 'asc')->get();



        return view('customer.pages.recurring-payments.index', compact('recurringPayments', 'categories', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'repeat' => ['required', 'string'],
            'category' => ['required', 'integer', 'exists:budget_categories,id'],
            'bank_account' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_type' => ['required', 'string'],

        ]);

        $isInternalTransfer = $request->has('internal_transfer') ? true : false;

        RecurringPayment::create([
            'name' => $validated['name'],
            'start_date' => $validated['date'],
            'repeat' => $validated['repeat'],
            'user_id' => Auth::user()->id,
            'category_id' => $validated['category'],
            'bank_account_id' => $validated['bank_account'],
            'amount' => $validated['amount'],
            'internal_transfer' => $isInternalTransfer,
            'transaction_type' => $validated['transaction_type'],
        ]);

        return redirect()->route('recurring-payments.index')->with('success', 'Recurring Payment created.');

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = RecurringPayment::where('user_id', Auth::user()->id)
            ->where('id', $id)
            ->first();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'repeat' => ['required', 'string'],
            'category' => ['required', 'integer', 'exists:budget_categories,id'],
            'bank_account' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_type' => ['required', 'string'],
        ]);

        $isInternalTransfer = $request->has('internal_transfer') ? true : false;

        $payment->update([
            'name' => $validated['name'],
            'start_date' => $validated['date'],
            'repeat' => $validated['repeat'],
            'user_id' => Auth::user()->id,
            'category_id' => $validated['category'],
            'bank_account_id' => $validated['bank_account'],
            'amount' => $validated['amount'],
            'internal_transfer' => $isInternalTransfer,
            'transaction_type' => $validated['transaction_type'],
        ]);

        return redirect()->route('recurring-payments.index')->with('success', 'Recurring Payment updated.');
    }

    public function globalAddRecurringPayments(Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'repeat' => ['required', 'string'],
            'category' => ['required', 'integer', 'exists:budget_categories,id'],
            'bank_account' => ['required', 'integer', 'exists:bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'transaction_type' => ['required', 'string'],

        ]);

        $isInternalTransfer = $request->has('internal_transfer') ? true : false;

        RecurringPayment::create([
            'name' => $validated['name'],
            'start_date' => $validated['date'],
            'repeat' => $validated['repeat'],
            'user_id' => Auth::user()->id,
            'category_id' => $validated['category'],
            'bank_account_id' => $validated['bank_account'],
            'amount' => $validated['amount'],
            'internal_transfer' => $isInternalTransfer,
            'transaction_type' => $validated['transaction_type'],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
