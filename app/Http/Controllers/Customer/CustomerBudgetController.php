<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetCategory;
use App\Models\BankAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerBudgetController extends Controller
{

    public function index()
    {
        // Get the most recent budget period
        $mainBudget = Budget::where('user_id', Auth::user()->id)
            ->latest('budget_start_date')
            ->where('category_name', '!=', 'uncategorised')
            ->where('category_name', '!=', 'salary')
            ->first();

        // dd($mainBudget);

        $budgetStartDate = $mainBudget ? $mainBudget->budget_start_date : Carbon::now();
        $budgetEndDate = $mainBudget ? $mainBudget->budget_end_date : Carbon::now()->addMonth();

        // Get all budget items within the date range
        $budgetItems = Budget::where('user_id', Auth::user()->id)
            ->whereBetween('budget_start_date', [$budgetStartDate, $budgetEndDate])
            ->where('category_name', '!=', 'salary')
            ->get();
        // ->where('category_name', '!=', 'uncategorised')
        // dd($budgetItems);


        $totalBudget = $budgetItems->sum('amount');

        $amountSpent = Transaction::where('user_id', Auth::user()->id)
            ->where('date', '<=', $budgetEndDate)
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $remainingBudget = $totalBudget - $amountSpent;


        $categoryDetails = [];

        foreach ($budgetItems as $budgetItem) {


            if ($budgetItem->category_name == "uncategorised") {
                // Fetch the exact budget for this category (not the first available one)
                $budget = Budget::where('user_id', Auth::user()->id)
                    ->where('category_name', 'uncategorised') // Get budget by category ID
                    ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
                    ->first();
                // ->where('category_name', '!=', 'uncategorised')

                if ($budget) {
                    $transactions = Transaction::where('user_id', Auth::user()->id)
                        ->where('category_name', 'uncategorised')
                        ->where('transaction_type', 'expense')
                        ->whereBetween('created_at', [$budgetStartDate, $budgetEndDate])
                        ->get();


                    $budgetTotalSpent = $transactions->sum('amount');

                    $startingBudgetAmount = $budget->amount; // Ensure this is category-specific

                    $remainingAmount = $startingBudgetAmount - $budgetTotalSpent;
                    $spentPercentage = $startingBudgetAmount > 0
                        ? ($budgetTotalSpent / $startingBudgetAmount) * 100
                        : 0;

                    $totalTransactions = Transaction::where('user_id', Auth::user()->id)
                        ->where('category_name', 'uncategorised')->whereBetween('created_at', [$budgetStartDate, $budgetEndDate])->get();

                    $categoryDetails[] = [
                        'budgetItem' => $budgetItem,
                        'budget' => $budget,
                        'transactions' => $totalTransactions,
                        'totalSpent' => $budgetTotalSpent,
                        'startingBudgetAmount' => $startingBudgetAmount,
                        'remainingAmount' => $remainingAmount,
                        'spentPercentage' => round($spentPercentage, 2),
                    ];
                }
            } else {
                // Fetch the exact budget for this category (not the first available one)
                $budget = Budget::where('user_id', Auth::user()->id)
                    ->where('id', $budgetItem->id) // Get budget by category ID
                    ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
                    ->where('amount', '>', 0)
                    ->where('category_name', '!=', 'salary')
                    ->first();
                // ->where('category_name', '!=', 'uncategorised')

                if ($budget) {
                    $transactions = Transaction::where('user_id', Auth::user()->id)
                        ->where('category_id', $budgetItem->id)->where('transaction_type', 'expense')
                        ->get();

                    $budgetTotalSpent = $transactions->sum('amount');

                    $startingBudgetAmount = $budget->amount; // Ensure this is category-specific

                    $remainingAmount = $startingBudgetAmount - $budgetTotalSpent;
                    $spentPercentage = $startingBudgetAmount > 0
                        ? ($budgetTotalSpent / $startingBudgetAmount) * 100
                        : 0;

                    $totalTransactions = Transaction::where('user_id', Auth::user()->id)
                        ->where('category_id', $budgetItem->id)->get();

                    $categoryDetails[] = [
                        'budgetItem' => $budgetItem,
                        'budget' => $budget,
                        'transactions' => $totalTransactions,
                        'totalSpent' => $budgetTotalSpent,
                        'startingBudgetAmount' => $startingBudgetAmount,
                        'remainingAmount' => $remainingAmount,
                        'spentPercentage' => round($spentPercentage, 2),
                    ];
                }
            }
        }

        $income = Transaction::where('user_id', Auth::user()->id)
            ->where('transaction_type', 'income')
            ->sum('amount');

        $income = BankAccount::where('user_id', Auth::user()->id)->sum('starting_balance');


        $income > 0 ? $clearCashBalance = $income - $amountSpent : $clearCashBalance = 0;


        // dd($budgetStartDate,$budgetEndDate);


        $budgetEndDate = Carbon::parse($budgetEndDate);
        $today = Carbon::now();

        // Difference in days (budgetEndDate - today)
        $daysLeft = $today->diffInDays($budgetEndDate, false);;

        return view('customer.pages.budget.index', compact('budgetStartDate', 'budgetEndDate', 'budgetItems', 'totalBudget', 'amountSpent', 'remainingBudget', 'categoryDetails', 'income', 'clearCashBalance', 'daysLeft'));
    }

    public function update(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();


        $budget->update([
            'amount' => $request->amount,
        ]);

        return redirect()->route('budget.index')->with('success', 'Budget updated.');
    }

    public function resetBudget(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $budget->update([
            'amount' => 0,
        ]);

        return redirect()->route('budget.index')->with('success', 'Budget reset.');
    }

    public function editCategoryList()
    {
        $budgetItems = Budget::where('user_id', Auth::user()->id)
            ->where('amount', '>', 0)
            ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
            ->orderBy('category_name', 'asc')
            ->get();

        return view('customer.pages.budget.edit-category-list', compact('budgetItems'));
    }

    public function updateCategoryList(Request $request)
    {
        $validatedData = $request->validate([
            'budget_items.*.id' => ['nullable', 'exists:budgets,id'],
            'budget_items.*.category_name' => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount' => ['required', 'numeric', 'min:0'],
            'new_items.*.category_name' => ['required', 'string', 'max:255'],
            'new_items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $existingBudget = Budget::where('user_id', Auth::user()->id)
            ->latest('budget_start_date')
            ->first();

        $budgetStartDate = $existingBudget ? $existingBudget->budget_start_date : Carbon::today();
        $budgetEndDate = $existingBudget ? $existingBudget->budget_end_date : Carbon::today()->addMonth();

        // ✅ Update existing budget items including category_name
        if (!empty($validatedData['budget_items'])) {
            foreach ($validatedData['budget_items'] as $itemData) {
                Budget::where('id', $itemData['id'])
                    ->where('user_id', Auth::user()->id)
                    ->update([
                        'category_name' => $itemData['category_name'],
                        'amount' => $itemData['amount'],
                    ]);
            }
        }

        // Add new budget items
        if (!empty($validatedData['new_items'])) {
            foreach ($validatedData['new_items'] as $newItem) {
                Budget::create([
                    'user_id' => Auth::user()->id,
                    'category_name' => $newItem['category_name'],
                    'amount' => $newItem['amount'],
                    'budget_start_date' => $budgetStartDate,
                    'budget_end_date' => $budgetEndDate,
                ]);
            }
        }

        return redirect()->route('budget.index')->with('success', 'Budget updated.');
    }


    public function globalAddNewBudget(Request $request)
    {

        $existingBudget = Budget::where('user_id', Auth::user()->id)
            ->latest('budget_end_date')
            ->first();

        $budgetStartDate = $existingBudget ? $existingBudget->budget_start_date : Carbon::today();
        $budgetEndDate = $existingBudget ? $existingBudget->budget_end_date : Carbon::today()->addMonth();

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        Budget::create([
            'category_name' => $validatedData['name'],
            'amount' => $validatedData['amount'],
            'budget_start_date' => $budgetStartDate,
            'budget_end_date' => $budgetEndDate,
            'user_id' => Auth::user()->id,
        ]);

        return redirect()->back()->with('success', 'Budget added.');
    }



    public function destroy(string $id) {}
}
