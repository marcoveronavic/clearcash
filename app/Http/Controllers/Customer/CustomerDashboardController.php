<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\RecurringPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function index(){

        $customer = User::where('id',Auth::user()->id)->first();

        if(Auth::user()->has_completed_setup == true) {

            $cashSavings = BankAccount::where('user_id', Auth::user()->id)->where('account_type','current_account')->sum('starting_balance');

            $savingsAmount = BankAccount::where('user_id', Auth::user()->id)->where('account_type','savings_account')->sum('starting_balance');
            $credit_card = BankAccount::where('user_id', Auth::user()->id)->where('account_type','credit_card')->sum('starting_balance');

            $networth = BankAccount::where('user_id', Auth::user()->id)->sum('starting_balance');


            // Get budgets that have ended
            $budgets = Budget::where('user_id', Auth::user()->id)
                ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
                ->get(); // Get all matching budget records
            // dd($budgets);
            $budgetEndDate = $budgets->isNotEmpty() ? $budgets->first()->budget_end_date : null;
            $budgetStartDate = $budgets->isNotEmpty() ? $budgets->first()->budget_start_date : null;

            $totalBudget = Budget::where('user_id', Auth::user()->id)
                ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->sum('amount');

            $amountSpent = Transaction::where('user_id', Auth::user()->id)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->sum('amount');

            $remainingBudget = $totalBudget - $amountSpent;

            $transactions = Transaction::where('user_id', Auth::user()->id)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->get();

            $income = Transaction::where('user_id', Auth::user()->id)
                ->where('transaction_type', 'income')
                ->sum('amount');

            $expense = Transaction::where('user_id', Auth::user()->id)
                ->where('transaction_type', '=', 'expense')
                ->sum('amount');

            $recurringPayments = RecurringPayment::where('user_id', Auth::user()->id)
                ->get();

            $bankAccounts = BankAccount::where('user_id', Auth::user()->id)->get();

            $pensionAccountsTotal = BankAccount::where('user_id', Auth::user()->id)
                ->where('account_type', 'pension')
                ->sum('starting_balance');

            $investmentAmountTotal = BankAccount::where('user_id', Auth::user()->id)
                ->where('account_type', 'investment')
                ->sum('starting_balance');

            return view('customer.pages.dashboard', compact('customer', 'credit_card','cashSavings', 'savingsAmount', 'networth', 'remainingBudget', 'amountSpent', 'totalBudget', 'transactions', 'income', 'expense', 'recurringPayments', 'bankAccounts', 'budgetStartDate', 'budgetEndDate', 'pensionAccountsTotal', 'investmentAmountTotal'));
        }

        else {
            // Ensure variables are always passed to the view
            $cashSavings = 0;
            $savingsAmount = 0;
            $networth = 0;
            $remainingBudget = 0;
            $amountSpent = 0;
            $totalBudget = 0;
            $budgetStartDate = Carbon::now()->format('Y-m-d');
            $budgetEndDate = Carbon::now()->format('Y-m-d');
            $pensionAccountsTotal = 0;
            $investmentAmountTotal = 0;

            // Empty collections to prevent undefined variable errors
            $transactions = collect();
            $income = 0;

            $expense = 0;
            $recurringPayments = collect();
            $bankAccounts = collect();

            return view('customer.pages.dashboard', compact(
                'customer',
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
            ));
        }

    }
}

