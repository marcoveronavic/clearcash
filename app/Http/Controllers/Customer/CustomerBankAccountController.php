<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerBankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bankAccounts = BankAccount::with('transactions')->where('user_id', Auth::user()->id)->orderBy('account_name', 'asc')->get();
       
        return view('customer.pages.bank-accounts.index', compact('bankAccounts'));
    }


    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $request)
    {
       $validated = $request->validate([
          'name_of_bank_account' => ['required', 'string'],
          'bank_account_type' => ['required', 'string'],
          'bank_account_starting_balance' => ['required', 'numeric'],
       ]);

       BankAccount::create([
          'account_name' => $validated['name_of_bank_account'],
          'account_type' => $validated['bank_account_type'],
          'starting_balance' => $validated['bank_account_starting_balance'],
          'user_id' => Auth::user()->id,
       ]);

       return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been created.');
    }


    /**
    * Update the specified resource in storage.
    */
    public function update(Request $request, string $id)
    {
       $account = BankAccount::where('id', $id)
          ->where('user_id', Auth::user()->id)
          ->first();

       $account->update([
          'account_name' => $request['name_of_bank_account'],
          'account_type' => $request['bank_account_type'],
          'starting_balance' => $request['bank_account_starting_balance'],
       ]);

       return redirect()->route('bank-accounts.index')->with('success', 'Bank Account has been updated.');
    }

    public function globalAddBankAccount(Request $request) {
       $validated = $request->validate([
          'name_of_bank_account' => ['required', 'string'],
          'bank_account_type' => ['required', 'string'],
          'bank_account_starting_balance' => ['required', 'numeric'],
       ]);

       BankAccount::create([
          'account_name' => $validated['name_of_bank_account'],
          'account_type' => $validated['bank_account_type'],
          'starting_balance' => $validated['bank_account_starting_balance'],
          'user_id' => Auth::user()->id,
       ]);

       return redirect()->back()->with('success', 'Bank Account has been created.');
    }

    /**
    * Remove the specified resource from storage.
    */
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
