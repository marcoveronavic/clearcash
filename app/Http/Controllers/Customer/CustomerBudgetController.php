<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
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

        // 2) Budget attivi nel periodo (overlap)
        $budgetItems = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['salary', 'uncategorised'])
            ->whereDate('budget_start_date', '<=', $budgetEndDate)
            ->whereDate('budget_end_date', '>=', $budgetStartDate)
            ->orderBy('category_name')
            ->get();

        // Categorie cash-sink: includono anche internal_transfer
        $cashSinkIds = $budgetItems->where('include_internal_transfers', true)
            ->pluck('category_id')->filter()->unique()->values()->all();

        $cashSinkNames = $budgetItems->where('include_internal_transfers', true)
            ->pluck('category_name')->filter()->map(fn ($n) => mb_strtolower($n))->unique()->values()->all();

        // 3) Totali periodo — SPESA NETTA (outflow - refunds)
        // >>> CONVENZIONE: expense outflow = amount > 0 ; refund = amount < 0 <<<
        $expenseAgg = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'expense')
            ->where(function ($q) use ($cashSinkIds, $cashSinkNames) {
                // (A) tutte le non-internal
                $q->whereNull('internal_transfer')
                    ->orWhere('internal_transfer', false);

                // (B) + internal transfer SOLO per categorie flagged (cash-sink)
                if (!empty($cashSinkIds) || !empty($cashSinkNames)) {
                    $q->orWhere(function ($qq) use ($cashSinkIds, $cashSinkNames) {
                        $qq->where('internal_transfer', true)
                            ->where(function ($w) use ($cashSinkIds, $cashSinkNames) {
                                if (!empty($cashSinkIds)) {
                                    $w->whereIn('category_id', $cashSinkIds);
                                }
                                if (!empty($cashSinkNames)) {
                                    $placeholders = implode(',', array_fill(0, count($cashSinkNames), '?'));
                                    $w->orWhereRaw('LOWER(category_name) IN ('.$placeholders.')', $cashSinkNames);
                                }
                            });
                    });
                }
            })
            ->selectRaw("
                SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
                SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
            ")
            ->first();

        $totalOutflow = (float) ($expenseAgg->outflow ?? 0);
        $totalRefunds = (float) ($expenseAgg->refunds ?? 0);
        $amountSpent  = max(0.0, $totalOutflow - $totalRefunds); // SPESA NETTA

        $totalBudget     = (float) $budgetItems->sum('amount');
        $remainingBudget = max(0.0, $totalBudget - $amountSpent);

        // 4) Dettaglio categorie (spesa netta per categoria)
        $categoryDetails = [];
        $totalOverspend  = 0.0;

        foreach ($budgetItems as $budgetItem) {
            $includeInternal = (bool) ($budgetItem->include_internal_transfers ?? false);

            $base = Transaction::query()
                ->where('user_id', $userId)
                ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
                ->where('transaction_type', 'expense')
                ->when(!$includeInternal, function ($qq) {
                    $qq->where(function ($q) {
                        $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                    });
                })
                ->where(function ($q) use ($budgetItem) {
                    $name = mb_strtolower($budgetItem->category_name);
                    $q->whereRaw('LOWER(category_name) = ?', [$name]);
                    if (!empty($budgetItem->category_id)) {
                        $q->orWhere('category_id', $budgetItem->category_id);
                    }
                });

            $agg = (clone $base)->selectRaw("
                    SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
                    SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
                ")->first();

            $outflow      = (float) ($agg->outflow ?? 0);
            $refunds      = (float) ($agg->refunds ?? 0);
            $netSpent     = max(0.0, $outflow - $refunds);
            $budgetAmount = (float) $budgetItem->amount;

            $remainingAmount = max(0.0, $budgetAmount - $netSpent);
            $spentPercentage = $budgetAmount > 0 ? min(100, round(($netSpent / $budgetAmount) * 100, 2)) : 0.0;

            if ($netSpent > $budgetAmount) {
                $totalOverspend += ($netSpent - $budgetAmount);
            }

            $transactions = (clone $base)
                ->orderBy('date', 'desc')
                ->get()
                ->map(function ($t) {
                    // Refund = expense con importo NEGATIVO (convenzione)
                    $t->kind = ($t->transaction_type === 'expense' && (float)$t->amount < 0)
                        ? 'refund'
                        : 'expense';
                    return $t;
                });

            $categoryDetails[] = [
                'budgetItem'           => $budgetItem,
                'transactions'         => $transactions,
                'outflow'              => $outflow,
                'refunds'              => $refunds,
                'totalSpent'           => $netSpent, // spesa netta
                'startingBudgetAmount' => $budgetAmount,
                'remainingAmount'      => $remainingAmount,
                'spentPercentage'      => $spentPercentage,
                'status'               => $netSpent > $budgetAmount ? 'overspent' : 'on-track',
            ];
        }

        // 5) INCOME del periodo (esclude internal) — SOLO income
        $incomeStrict = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'income')
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));

        // Carry-over stipendio (ultimo giorno mese precedente), esclude internal
        $prevDay = $budgetStartDate->copy()->subDay()->toDateString();
        $salaryCarry = (float) Transaction::where('user_id', $userId)
            ->whereDate('date', $prevDay)
            ->where('transaction_type', 'income')
            ->where(function ($q) {
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

        // 7) Uncategorised del periodo — spesa netta (esclude internal)
        $uncatAgg = Transaction::query()
            ->where('user_id', $userId)
            ->where('transaction_type', 'expense')
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where(function ($q) {
                $q->whereNull('category_id')->orWhereRaw("LOWER(category_name) = 'uncategorised'");
            })
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->selectRaw("
                SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
                SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
            ")
            ->first();

        $uncategorisedSpent = max(
            0.0,
            (float) ($uncatAgg->outflow ?? 0) - (float) ($uncatAgg->refunds ?? 0)
        );
        $showUncategorised = $uncategorisedSpent > 0;

        // 8) Transazioni del periodo (per tabella generale) — esclude internal
        $allTransactions = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->orderBy('date', 'desc')
            ->limit(200)
            ->get()
            ->map(function ($t) {
                if ($t->transaction_type === 'expense') {
                    $t->kind = ((float)$t->amount < 0) ? 'refund' : 'expense';
                } else {
                    $t->kind = 'income';
                }
                return $t;
            });

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
            'totalOverspend',
            'allTransactions'
        ));
    }

    public function globalAddNewBudget(Request $request)
    {
        $validated = $request->validate([
            'name'                       => ['required','string','max:255'],
            'amount'                     => ['required','numeric','min:0'],
            'include_internal_transfers' => ['sometimes','boolean'],
        ]);

        $userId       = Auth::id();
        $categoryName = trim($validated['name']);
        $amount       = (float) $validated['amount'];
        $includeIT    = $request->boolean('include_internal_transfers');

        $existingPeriod = Budget::where('user_id', $userId)
            ->orderByDesc('budget_end_date')
            ->first();

        $budgetStartDate = $existingPeriod
            ? Carbon::parse($existingPeriod->budget_start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $budgetEndDate = $existingPeriod
            ? Carbon::parse($existingPeriod->budget_end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        Budget::updateOrCreate(
            [
                'user_id'           => $userId,
                'category_name'     => $categoryName,
                'budget_start_date' => $budgetStartDate,
                'budget_end_date'   => $budgetEndDate,
            ],
            [
                'amount'                     => $amount,
                'include_internal_transfers' => $includeIT,
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
            'budget_items.*.id'                         => ['nullable', 'exists:budgets,id'],
            'budget_items.*.category_name'              => ['nullable', 'string', 'max:255'],
            'budget_items.*.amount'                     => ['required', 'numeric', 'min:0'],
            'budget_items.*.include_internal_transfers' => ['sometimes', 'boolean'],
            'new_items.*.category_name'                 => ['required', 'string', 'max:255'],
            'new_items.*.amount'                        => ['required', 'numeric', 'min:0'],
            'new_items.*.include_internal_transfers'    => ['sometimes', 'boolean'],
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
                        'category_name'              => $itemData['category_name'] ?? null,
                        'amount'                     => $itemData['amount'],
                        'include_internal_transfers' => !empty($itemData['include_internal_transfers']),
                    ]);
            }
        }

        if (!empty($validatedData['new_items'])) {
            foreach ($validatedData['new_items'] as $newItem) {
                Budget::create([
                    'user_id'                    => Auth::user()->id,
                    'category_name'              => $newItem['category_name'],
                    'amount'                     => $newItem['amount'],
                    'include_internal_transfers' => !empty($newItem['include_internal_transfers']),
                    'budget_start_date'          => $budgetStartDate,
                    'budget_end_date'            => $budgetEndDate,
                ]);
            }
        }

        return redirect()->route('budget.index')->with('success', 'Budget updated.');
    }

    public function destroy(string $id) {}
}
