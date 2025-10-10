<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerBudgetController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 1) Periodo di riferimento (se non esiste un budget, usa mese corrente)
        $periodBudget = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['uncategorised', 'salary'])
            ->orderByDesc('budget_end_date')
            ->first();

        $budgetStartDate = $periodBudget
            ? Carbon::parse($periodBudget->budget_start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $budgetEndDate = $periodBudget
            ? Carbon::parse($periodBudget->budget_end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // 2) Budget attivi nel periodo (overlap: start <= fine && end >= inizio)
        $budgetItems = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['salary', 'uncategorised'])
            ->whereDate('budget_start_date', '<=', $budgetEndDate)
            ->whereDate('budget_end_date', '>=', $budgetStartDate)
            ->orderBy('category_name')
            ->get();

        // 3) Totali periodo
        $totalBudget = (float) $budgetItems->sum('amount');

        $amountSpent = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'expense')
            ->sum(DB::raw('ABS(amount)'));

        $remainingBudget = max(0.0, $totalBudget - $amountSpent);

        // 4) Dettagli per categoria (spese reali del periodo; match per ID o per nome)
        $categoryDetails = [];
        $totalOverspend  = 0.0;

        foreach ($budgetItems as $budgetItem) {

            $transactions = Transaction::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'expense')
                ->where(function ($q) use ($budgetItem) {
                    $name = strtolower($budgetItem->category_name);
                    $q->whereRaw('LOWER(category_name) = ?', [$name]);
                    if (!empty($budgetItem->category_id)) {
                        $q->orWhere('category_id', $budgetItem->category_id);
                    }
                })
                ->orderBy('date', 'desc')
                ->get();

            $budgetTotalSpent = (float) $transactions->sum(function ($t) {
                return abs((float) $t->amount); // uscite come valore positivo
            });

            $startingBudgetAmount = (float) $budgetItem->amount;
            $remainingAmount      = max(0.0, $startingBudgetAmount - $budgetTotalSpent);
            $spentPercentage      = $startingBudgetAmount > 0
                ? min(100, round(($budgetTotalSpent / $startingBudgetAmount) * 100, 2))
                : 0.0;

            if ($budgetTotalSpent > $startingBudgetAmount) {
                $totalOverspend += ($budgetTotalSpent - $startingBudgetAmount);
            }

            // struttura attesa dalla Blade
            $categoryDetails[] = [
                'budgetItem'           => $budgetItem,      // ->category_name
                'budget'               => $budgetItem,      // compat
                'transactions'         => $transactions,
                'totalSpent'           => $budgetTotalSpent, // POSITIVO
                'startingBudgetAmount' => $startingBudgetAmount,
                'remainingAmount'      => $remainingAmount,
                'spentPercentage'      => $spentPercentage,  // 0% se nessuna spesa
            ];
        }

        // 5) Income & ClearCash balance
        $income = (float) BankAccount::where('user_id', $userId)->sum('starting_balance');
        $clearCashBalance = $income - $totalBudget;

        // 6) Giorni rimasti
        $daysLeft = Carbon::now()->diffInDays(Carbon::parse($budgetEndDate), false);

        // 7) Uncategorised del periodo (ABS delle uscite)
        $uncategorisedSpent = (float) Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereRaw("LOWER(category_name) = 'uncategorised'");
            })
            ->sum(DB::raw('ABS(amount)'));

        $showUncategorised = $uncategorisedSpent > 0;

        return view('customer.pages.budget.index', compact(
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
            'uncategorisedSpent',
            'totalOverspend'
        ));
    }

    public function update(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $budget->update(['amount' => $request->amount]);

        return redirect()->route('budget.index')->with('success', 'Budget updated.');
    }

    public function resetBudget(Request $request, string $id)
    {
        $budget = Budget::where('id', $id)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        $budget->update(['amount' => 0]);

        return redirect()->route('budget.index')->with('success', 'Budget reset.');
    }

    public function editCategoryList()
    {
        $budgetItems = Budget::where('user_id', Auth::user()->id)
            ->where('amount', '>', 0)
            ->whereDate('budget_end_date', '>=', Carbon::today()->toDateString())
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
            ->orderByDesc('budget_end_date')
            ->first();

        $budgetStartDate = $existingBudget ? $existingBudget->budget_start_date : Carbon::today();
        $budgetEndDate   = $existingBudget ? $existingBudget->budget_end_date   : Carbon::today()->addMonth();

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

    public function destroy(string $id) {}
}
