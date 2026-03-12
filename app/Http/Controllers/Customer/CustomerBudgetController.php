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

        $now = Carbon::now();
        if ($now->gt($budgetEndDate->copy()->endOfDay())) {
            $hasBudget = Budget::where('user_id', $userId)
                ->whereDate('budget_end_date', $budgetEndDate->toDateString())
                ->where('category_name', '!=', 'salary')
                ->where('category_name', '!=', 'uncategorised')
                ->where('amount', '>', 0)
                ->exists();

            if ($hasBudget && !request()->has('skip_summary')) {
                return redirect()->route('budget.period-summary');
            }
        }

        $budgetItems = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['salary', 'uncategorised'])
            ->whereDate('budget_start_date', '<=', $budgetEndDate)
            ->whereDate('budget_end_date', '>=', $budgetStartDate)
            ->orderBy('category_name')
            ->get();

        $cashSinkIds = $budgetItems->where('include_internal_transfers', true)
            ->pluck('category_id')->filter()->unique()->values()->all();

        $cashSinkNames = $budgetItems->where('include_internal_transfers', true)
            ->pluck('category_name')->filter()->map(fn ($n) => mb_strtolower($n))->unique()->values()->all();

        $expenseAgg = Transaction::query()
            ->where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'expense')
            ->where(function ($q) use ($cashSinkIds, $cashSinkNames) {
                $q->whereNull('internal_transfer')
                    ->orWhere('internal_transfer', false);

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
        $amountSpent  = max(0.0, $totalOutflow - $totalRefunds);

        $totalBudget     = (float) $budgetItems->sum('amount');
        $remainingBudget = max(0.0, $totalBudget - $amountSpent);

        $categoryDetails = [];
        $totalOverspend  = 0.0;
        $totalBudgetedSpent = 0.0;

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

            $totalBudgetedSpent += $netSpent;

            $remainingAmount = max(0.0, $budgetAmount - $netSpent);
            $spentPercentage = $budgetAmount > 0 ? min(100, round(($netSpent / $budgetAmount) * 100, 2)) : 0.0;

            if ($netSpent > $budgetAmount) {
                $totalOverspend += ($netSpent - $budgetAmount);
            }

            $transactions = (clone $base)
                ->orderBy('date', 'desc')
                ->get()
                ->map(function ($t) {
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
                'totalSpent'           => $netSpent,
                'startingBudgetAmount' => $budgetAmount,
                'remainingAmount'      => $remainingAmount,
                'spentPercentage'      => $spentPercentage,
                'status'               => $netSpent > $budgetAmount ? 'overspent' : 'on-track',
            ];
        }

        $incomeStrict = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'income')
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->sum(DB::raw('CASE WHEN amount > 0 THEN amount ELSE 0 END'));

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

        // Tutte le spese del periodo
        $totalAllExpenses = (float) Transaction::where('user_id', $userId)
            ->whereBetween('date', [$budgetStartDate, $budgetEndDate])
            ->where('transaction_type', 'expense')
            ->where(function ($q) {
                $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
            })
            ->sum(\DB::raw('ABS(amount)'));

        // Spese extra = totale spese - spese nelle categorie budget (così torna sempre)
        $extraExpenses = max(0, $totalAllExpenses - $totalBudgetedSpent);

        $clearCashBalance = $income - $totalAllExpenses;

        $daysLeft = Carbon::now()->diffInDays(Carbon::parse($budgetEndDate), false);

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
            'allTransactions',
            'extraExpenses',
            'totalBudgetedSpent'
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
        $userId = Auth::user()->id;

        $latestBudget = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['uncategorised', 'salary'])
            ->where('amount', '>', 0)
            ->orderByDesc('id')
            ->first();

        if (!$latestBudget) {
            return redirect()->route('budget.index');
        }

        $budgetItems = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['uncategorised', 'salary'])
            ->where('amount', '>', 0)
            ->whereDate('budget_start_date', $latestBudget->budget_start_date)
            ->whereDate('budget_end_date', $latestBudget->budget_end_date)
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

    public function periodSummary()
    {
        $userId = Auth::id();
        $tz = config('app.timezone');
        $now = Carbon::now($tz);

        $latestBudget = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['uncategorised', 'salary'])
            ->where('amount', '>', 0)
            ->whereNotNull('budget_start_date')
            ->whereNotNull('budget_end_date')
            ->orderByDesc('id')
            ->first();

        if (!$latestBudget) {
            return redirect()->route('budget.index');
        }

        $startDate = $latestBudget->budget_start_date;
        $endDate   = $latestBudget->budget_end_date;

        $totalBudget = Budget::where('user_id', $userId)
            ->whereDate('budget_start_date', $startDate)
            ->whereDate('budget_end_date', $endDate)
            ->where('category_name', '!=', 'salary')
            ->where('category_name', '!=', 'uncategorised')
            ->sum('amount');

        $income = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('transaction_type', 'income')
            ->sum('amount');

        $totalSpent = Transaction::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('transaction_type', 'expense')
            ->where('category_name', '!=', 'salary')
            ->sum(\DB::raw('ABS(amount)'));

        $saved = $totalBudget - $totalSpent;
        $isSuccess = $saved >= 0;
        $periodEnded = $now->gt(Carbon::parse($endDate, $tz)->endOfDay());

        return view('customer.pages.budget.period-summary', compact(
            'totalBudget',
            'totalSpent',
            'income',
            'saved',
            'isSuccess',
            'startDate',
            'endDate',
            'periodEnded'
        ));
    }

    public function renewPeriod(Request $request)
    {
        $userId = Auth::id();
        $tz = config('app.timezone');

        $latestBudget = Budget::where('user_id', $userId)
            ->whereNotIn('category_name', ['uncategorised', 'salary'])
            ->where('amount', '>', 0)
            ->whereNotNull('budget_start_date')
            ->whereNotNull('budget_end_date')
            ->orderByDesc('id')
            ->first();

        if (!$latestBudget) {
            return redirect()->route('budget.index');
        }

        $oldStart = Carbon::parse($latestBudget->budget_start_date, $tz);
        $oldEnd   = Carbon::parse($latestBudget->budget_end_date, $tz);

        $newStart = $oldEnd->copy()->addDay()->startOfDay();
        $newEnd   = $newStart->copy()->addDays($oldStart->diffInDays($oldEnd))->endOfDay();

        $newStartDate = $newStart->toDateString();
        $newEndDate   = $newEnd->toDateString();

        $oldBudgets = Budget::where('user_id', $userId)
            ->whereDate('budget_start_date', $latestBudget->budget_start_date)
            ->whereDate('budget_end_date', $latestBudget->budget_end_date)
            ->get();

        foreach ($oldBudgets as $old) {
            Budget::updateOrCreate(
                [
                    'user_id'           => $userId,
                    'category_name'     => $old->category_name,
                    'budget_start_date' => $newStartDate,
                    'budget_end_date'   => $newEndDate,
                ],
                [
                    'amount'      => $old->amount,
                    'category_id' => $old->category_id,
                ]
            );
        }

        return redirect()->route('budget.index')
            ->with('success', 'Budget rinnovato per il nuovo periodo!');
    }

    public function restartPeriod(Request $request)
    {
        return redirect()->route('account-setup.step-one');
    }

    public function destroy(string $id) {}
}

