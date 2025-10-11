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
use Illuminate\Support\Facades\Schema;

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

        // 2) Budget attivi nel periodo (overlap)
        $budgetItems = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['salary', 'uncategorised'])
            ->whereDate('budget_start_date', '<=', $budgetEndDate)
            ->whereDate('budget_end_date', '>=', $budgetStartDate)
            ->orderBy('category_name')
            ->get();

        // 3) Totali periodo
        $totalBudget = (float) $budgetItems->sum('amount');

        // Uscite REALI del periodo (esclude internal transfer) – valori positivi via ABS
        $amountSpent = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'expense')
            ->where(function ($q) {                    // ⛔ escludi trasferimenti interni
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->sum(DB::raw('ABS(amount)'));

        $remainingBudget = max(0.0, $totalBudget - $amountSpent);

        // 4) Dettaglio categorie (spese reali del periodo) – esclude internal transfer
        $categoryDetails = [];
        $totalOverspend  = 0.0;

        foreach ($budgetItems as $budgetItem) {

            $transactions = Transaction::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'expense')
                ->where(function ($q) {                // ⛔ escludi trasferimenti interni
                    $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                })
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
                return abs((float) $t->amount);
            });

            $startingBudgetAmount = (float) $budgetItem->amount;
            $remainingAmount      = max(0.0, $startingBudgetAmount - $budgetTotalSpent);
            $spentPercentage      = $startingBudgetAmount > 0
                ? min(100, round(($budgetTotalSpent / $startingBudgetAmount) * 100, 2))
                : 0.0;

            if ($budgetTotalSpent > $startingBudgetAmount) {
                $totalOverspend += ($budgetTotalSpent - $startingBudgetAmount);
            }

            $categoryDetails[] = [
                'budgetItem'           => $budgetItem,
                'budget'               => $budgetItem,
                'transactions'         => $transactions,
                'totalSpent'           => $budgetTotalSpent,
                'startingBudgetAmount' => $startingBudgetAmount,
                'remainingAmount'      => $remainingAmount,
                'spentPercentage'      => $spentPercentage,
            ];
        }

        // 5) INCOME del mese = entrate del periodo (+ eventuale salary del giorno precedente) – esclude internal transfer
        $incomeStrict = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where(function ($q) {
                $q->where('transaction_type', 'income')
                    ->orWhere('amount', '>', 0);
            })
            ->where(function ($q) {                    // ⛔ escludi trasferimenti interni
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));

        // Carry-over: stipendio dell'ultimo giorno del mese precedente
        $prevDay = $budgetStartDate->copy()->subDay()->toDateString();
        $salaryCarry = (float) Transaction::where('user_id', $userId)
            ->whereDate('date', $prevDay)
            ->where(function ($q) {
                $q->where('transaction_type', 'income')
                    ->orWhere('amount', '>', 0);
            })
            ->where(function ($q) {                    // ⛔ escludi trasferimenti interni
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->where(function ($q) {
                $q->whereRaw('LOWER(category_name) = ?', ['salary'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%salary%']);
            })
            ->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));

        $income = $incomeStrict + $salaryCarry;

        // Clear Cash Balance "pianificato": Income - Budgeted
        $clearCashBalance = $income - $totalBudget;

        // 6) Giorni rimasti
        $daysLeft = Carbon::now()->diffInDays(Carbon::parse($budgetEndDate), false);

        // 7) Uncategorised del periodo (ABS delle uscite) – esclude internal transfer
        $uncategorisedSpent = (float) Transaction::where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where(function ($q) {
                $q->whereNull('category_id')
                    ->orWhereRaw("LOWER(category_name) = 'uncategorised'");
            })
            ->where(function ($q) {                    // ⛔ escludi trasferimenti interni
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
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

    /**
     * Salva una NUOVA budget category (o aggiorna se già esiste nella stessa finestra temporale).
     * Route attesa: POST /budget/global-add-budget  (name: budget.global-add-budget)
     * Body: name, amount
     */
    public function globalAddNewBudget(Request $request)
    {
        $validated = $request->validate([
            'name'   => ['required','string','max:255'],
            'amount' => ['required','numeric','min:0'],
        ]);

        $userId       = Auth::id();
        $categoryName = trim($validated['name']);
        $amount       = (float) $validated['amount'];

        // Usa lo stesso periodo dell'ultimo budget esistente; se non c'è, mese corrente.
        $existingPeriod = Budget::where('user_id', $userId)
            ->orderByDesc('budget_end_date')
            ->first();

        $budgetStartDate = $existingPeriod
            ? Carbon::parse($existingPeriod->budget_start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $budgetEndDate = $existingPeriod
            ? Carbon::parse($existingPeriod->budget_end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // Evita duplicati sulla stessa categoria/periodo: update or create
        Budget::updateOrCreate(
            [
                'user_id'           => $userId,
                'category_name'     => $categoryName,
                'budget_start_date' => $budgetStartDate,
                'budget_end_date'   => $budgetEndDate,
            ],
            [
                'amount'            => $amount,
            ]
        );

        return redirect()->route('budget.index')->with('success', 'Budget category salvata.');
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
