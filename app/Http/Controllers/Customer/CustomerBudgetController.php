<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BankAccount;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerBudgetController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 1) Periodo di budget corrente (ignora salary/uncategorised per scegliere il periodo)
        $mainBudget = Budget::where('user_id', $userId)
            ->where('category_name', '!=', 'uncategorised')
            ->where('category_name', '!=', 'salary')
            ->latest('budget_start_date')
            ->first();

        $budgetStartDate = $mainBudget ? $mainBudget->budget_start_date : Carbon::now();
        $budgetEndDate   = $mainBudget ? $mainBudget->budget_end_date   : Carbon::now()->addMonth();

        // 2) Tutti i budget items del periodo ESCLUDENDO 'uncategorised' e 'salary'
        $budgetItems = Budget::where('user_id', $userId)
            ->whereBetween('budget_start_date', [$budgetStartDate, $budgetEndDate])
            ->where('category_name', '!=', 'salary')
            ->where('category_name', '!=', 'uncategorised')
            ->get();

        // 3) Totali base
        $totalBudget = $budgetItems->sum('amount');

        $amountSpent = Transaction::where('user_id', $userId)
            ->where('date', '<=', $budgetEndDate)
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $remainingBudget = $totalBudget - $amountSpent;

        // 4) Dettagli per card categoria (solo categorie "vere")
        $categoryDetails = [];
        foreach ($budgetItems as $budgetItem) {
            // Budget della categoria (stesso id record)
            $budget = Budget::where('user_id', $userId)
                ->where('id', $budgetItem->id)
                ->whereDate('budget_end_date', '>=', Carbon::today()->format('Y-m-d'))
                ->where('amount', '>', 0)
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->first();

            if ($budget) {
                // NB: qui lasci la logica originaria con category_id = id del budget
                $transactions = Transaction::where('user_id', $userId)
                    ->where('category_id', $budgetItem->id)
                    ->where('transaction_type', 'expense')
                    ->get();

                $budgetTotalSpent = $transactions->sum('amount');

                $startingBudgetAmount = $budget->amount;
                $remainingAmount = $startingBudgetAmount - $budgetTotalSpent;
                $spentPercentage = $startingBudgetAmount > 0
                    ? ($budgetTotalSpent / $startingBudgetAmount) * 100
                    : 0;

                $totalTransactions = Transaction::where('user_id', $userId)
                    ->where('category_id', $budgetItem->id)
                    ->get();

                $categoryDetails[] = [
                    'budgetItem'           => $budgetItem,
                    'budget'               => $budget,
                    'transactions'         => $totalTransactions,
                    'totalSpent'           => $budgetTotalSpent,
                    'startingBudgetAmount' => $startingBudgetAmount,
                    'remainingAmount'      => $remainingAmount,
                    'spentPercentage'      => round($spentPercentage, 2),
                ];
            }
        }

        // 5) Income & ClearCash balance
        $income = BankAccount::where('user_id', $userId)->sum('starting_balance');

        // ✅ Clear Cash Balance = Income - Expenses (dove Expenses = somma budget)
        $clearCashBalance = $income - $totalBudget;

        // 6) Giorni rimasti a fine periodo
        $budgetEndDate = Carbon::parse($budgetEndDate);
        $today = Carbon::now();
        $daysLeft = $today->diffInDays($budgetEndDate, false);

        // 7) UNCATEGORISED: mostra solo se esistono spese senza categoria nel periodo
        $uncategorisedSpent = Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereRaw("LOWER(category_name) = 'uncategorised'");
            })
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->sum('amount');

        $showUncategorised = (float) $uncategorisedSpent > 0;

        // 8) Render
        return view(
            'customer.pages.budget.index',
            compact(
                'budgetStartDate',
                'budgetEndDate',
                'budgetItems',
                'totalBudget',
                'amountSpent',
                'remainingBudget',
                'categoryDetails',
                'income',
                'clearCashBalance',
                'daysLeft',
                'showUncategorised',
                'uncategorisedSpent'
            )
        );
    }

    public function update(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $budget->update(['amount' => $request->amount]);

        return redirect()->route('budget.index')->with('success', 'Budget updated.');
    }

    public function resetBudget(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->first();

        $budget->update(['amount' => 0]);

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
            'budget_items.*.id'            => ['nullable', 'exists:budgets,id'],
            'budget_items.*.category_name' => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount'        => ['required', 'numeric', 'min:0'],
            'new_items.*.category_name'    => ['required', 'string', 'max:255'],
            'new_items.*.amount'           => ['required', 'numeric', 'min:0'],
        ]);

        $existingBudget = Budget::where('user_id', Auth::user()->id)
            ->latest('budget_start_date')
            ->first();

        $budgetStartDate = $existingBudget ? $existingBudget->budget_start_date : Carbon::today();
        $budgetEndDate   = $existingBudget ? $existingBudget->budget_end_date   : Carbon::today()->addMonth();

        // update esistenti
        if (!empty($validatedData['budget_items'])) {
            foreach ($validatedData['budget_items'] as $itemData) {
                Budget::where('id', $itemData['id'])
                    ->where('user_id', Auth::user()->id)
                    ->update([
                        'category_name' => $itemData['category_name'],
                        'amount'        => $itemData['amount'],
                    ]);
            }
        }

        // crea nuovi
        if (!empty($validatedData['new_items'])) {
            foreach ($validatedData['new_items'] as $newItem) {
                Budget::create([
                    'user_id'           => Auth::user()->id,
                    'category_name'     => $newItem['category_name'],
                    'amount'            => $newItem['amount'],
                    'budget_start_date' => $budgetStartDate,
                    'budget_end_date'   => $budgetEndDate,
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
        $budgetEndDate   = $existingBudget ? $existingBudget->budget_end_date   : Carbon::today()->addMonth();

        $validatedData = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        Budget::create([
            'category_name'     => $validatedData['name'],
            'amount'            => $validatedData['amount'],
            'budget_start_date' => $budgetStartDate,
            'budget_end_date'   => $budgetEndDate,
            'user_id'           => Auth::user()->id,
        ]);

        return redirect()->back()->with('success', 'Budget added.');
    }

    public function destroy(string $id) {}
}
