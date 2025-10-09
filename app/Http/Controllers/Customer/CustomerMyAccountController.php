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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerMyAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $details = User::where('id', Auth::user()->id)->first();

        return view('customer.pages.my-account.index', compact('details'));
    }

    public function mainDetailsStore(Request $request)
    {
        $details = User::where('id', Auth::user()->id)->first();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore(Auth::id())],
        ]);

        $currentEmail = $details->email;
        $newEmail = $validated['email'];

        if ($currentEmail != $newEmail) {
            $details->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'full_name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $newEmail,
            ]);

            return redirect()->route('my-account.index')->with('success', 'Your account details have been updated.');
        } else {
            $details->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'full_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            ]);

            return redirect()->route('my-account.index')->with('success', 'Your account details have been updated.');
        }
    }

    public function passwordUpdateStore(Request $request)
    {
        $details = User::where('id', Auth::user()->id)->first();

        $validated = $request->validate([
            'current_password' => ['required', 'string', 'min:8'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check if the current password is correct
        if (!Hash::check($validated['current_password'], $details->password)) {
            return redirect()->back()->with('error', 'Your current password is incorrect');
        }

        // Check if the new password is the same as the current password
        if (Hash::check($validated['password'], $details->password)) {
            return redirect()->back()->with('error', 'Your new password must be different from your current password.');
        }

        // Update the password
        $details->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('my-account.index')->with('success', 'Your password has been updated.');
    }


    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $customer = User::findOrFail($id);

        if ($customer) {

            //Remove Budget
            Budget::where('user_id', $id)->delete();

            //Remove Recurring Payments
            RecurringPayment::where('user_id', $id)->delete();

            //Remove Transactions
            Transaction::where('user_id', $id)->delete();

            //Remove Bank Account
            BankAccount::where('user_id', $id)->delete();

            //Remove customer Details
            CustomerAccountDetails::where('user_id', $id)->delete();

            //Log the user out
            Auth::logout();

            //Remove the user
            $customer->delete();

            //Clear session for user and regen token
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            //Redirect to login
            return redirect()->route('login')->with('success', 'Your account details have been deleted and your data has been removed.');
        } else {
            return redirect()->back('dashboard');
        }
    }

    public function resetAccount(Request $request)
    {

        $userId = Auth::id();

        // Delete all budgets, recurring payments, transactions, customer details, and bank accounts
        Budget::where('user_id', $userId)->delete();
        RecurringPayment::where('user_id', $userId)->delete();
        Transaction::where('user_id', $userId)->delete();
        CustomerAccountDetails::where('customer_id', $userId)->delete();

        try {
            $deleted = BankAccount::where('user_id', $userId)->forceDelete();
            // dd('Deleted count: ' . $deleted);
        } catch (\Exception $e) {
            dd('Error: ' . $e->getMessage());
        }

        $user = User::find($userId);

        $user->has_completed_setup = 0;
        $user->save();

        // Optionally clear related session data if you store any
        $request->session()->forget([
            'budgets',
            'recurring_payments',
            'transactions',
            'customer_account_details',
            'bank_accounts',
            'accSetup',
        ]);
        // Regenerate session ID for security
        $request->session()->regenerate();

        return response()->json(['status' => 'success']);
    }
}
