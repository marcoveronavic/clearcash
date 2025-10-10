<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        // tutte le transazioni con relazione category (-> budget_categories)
        $transactions = Transaction::where('user_id', $userId)
            ->with('category')
            ->orderBy('date', 'desc')
            ->get();

        // group by day
        $groupedTransactions = $transactions->groupBy(fn ($t) =>
        \Carbon\Carbon::parse($t->date)->format('Y-m-d')
        );

        // totals of today
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $todayTransactions = $transactions->filter(fn ($t) =>
            \Carbon\Carbon::parse($t->date)->format('Y-m-d') === $today
        );

        $totalTransactionAmountToday = $todayTransactions->sum('amount');
        $totalExpenseAmountToday = $todayTransactions->where('transaction_type', 'expense')->sum('amount');

        $bankAccounts = BankAccount::where('user_id', $userId)
            ->where('account_name', '!=', 'pension')
            ->orderBy('account_name', 'asc')
            ->get();

        $dailyExpenses = [];
        foreach ($groupedTransactions as $date => $trans) {
            $dailyExpenses[$date] = $trans->where('transaction_type', 'expense')->sum('amount');
        }

        return view('customer.pages.transactions.index', compact(
            'groupedTransactions',
            'bankAccounts',
            'transactions',
            'totalTransactionAmountToday',
            'totalExpenseAmountToday',
            'dailyExpenses'
        ));
    }

    public function filterByBank($bankName)
    {
        $bank = BankAccount::whereRaw("LOWER(REPLACE(account_name, ' ', '-')) = ?", [strtolower($bankName)])
            ->firstOrFail();

        $transactions = Transaction::where('user_id', Auth::id())
            ->with('category')
            ->where('bank_account_id', $bank->id)
            ->orderBy('date', 'desc')
            ->get();

        $groupedTransactions = $transactions->groupBy(fn ($t) =>
        \Carbon\Carbon::parse($t->date)->format('Y-m-d')
        );

        $bankAccounts = BankAccount::where('user_id', Auth::id())
            ->where('account_name', '!=', 'pension')
            ->orderBy('account_name', 'asc')
            ->get();

        $categories = Budget::where('user_id', Auth::id())->get();

        return view('customer.pages.transactions.filter-by-bank', compact(
            'transactions', 'bank', 'bankAccounts', 'categories', 'groupedTransactions'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $bankAccounts = BankAccount::where('user_id', Auth::id())
            ->orderBy('account_name', 'asc')
            ->get();

        // in UI scegli l'ID DEL BUDGET (budgets.id)
        $categories = Budget::where('user_id', Auth::id())->get();

        return view('customer.pages.transactions.create', compact('bankAccounts', 'categories'));
    }

    /**
     * Store (singola transazione dalla pagina create).
     * Il form invia: category = budgets.id
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'date'            => ['required', 'date'],
            // Arriva l'ID DEL BUDGET dell'utente
            'category'        => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'    => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'          => ['required', 'numeric', 'min:1'],
            'transaction_type'=> ['required', 'string', 'max:255'],
            'internal_transfer'=> ['nullable', 'boolean'],
        ]);

        $isInternalTransfer = $request->boolean('internal_transfer');

        // Risali dal budget scelto alla categoria VERA (budget_categories.id)
        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;  // <-- FK corretta

        $transaction = Transaction::create([
            'name'              => $validated['name'],
            'date'              => $validated['date'],
            'category_name'     => $categoryName,
            'category_id'       => $categoryId,                   // <-- salva id della tabella budget_categories
            'bank_account_id'   => $validated['bank_account'],
            'amount'            => $validated['amount'],
            'transaction_type'  => $validated['transaction_type'],
            'internal_transfer' => $isInternalTransfer,
            'user_id'           => $userId,
        ]);

        // aggiorna saldo conto
        $bankAccount = BankAccount::where('user_id', $userId)->findOrFail($validated['bank_account']);
        if ($validated['transaction_type'] === 'income') {
            $bankAccount->starting_balance += $validated['amount'];
        } else {
            $bankAccount->starting_balance -= $validated['amount'];
        }
        $bankAccount->save();

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Update the specified resource in storage.
     * Il form invia: category = budgets.id
     */
    public function update(Request $request, string $id)
    {
        $userId = Auth::id();
        $transaction = Transaction::where('user_id',$userId)->findOrFail($id);
        $oldAmount = $transaction->amount;

        // regole
        $rules = [
            'name'   => ['required', 'string', 'max:255'],
            'date'   => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:1'],
        ];

        if ($transaction->transaction_type === 'fundtransfer') {
            $rules['from_account'] = ['required', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))];
            $rules['to_account']   = ['required', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))];
        } else {
            $rules['transaction_type'] = ['required', 'string', 'max:255'];
            // category = budgets.id (non budget_categories.id)
            $rules['category']      = ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))];
            $rules['bank_account']  = ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))];
            $rules['internal_transfer'] = ['nullable', 'boolean'];
        }

        $validated = $request->validate($rules);

        if ($transaction->transaction_type === 'fundtransfer') {
            // ripristina saldi vecchi
            $oldFrom = BankAccount::where('user_id',$userId)->findOrFail($transaction->from_account);
            $oldTo   = BankAccount::where('user_id',$userId)->findOrFail($transaction->to_account);
            $oldFrom->starting_balance += $oldAmount;
            $oldTo->starting_balance   -= $oldAmount;
            $oldFrom->save();
            $oldTo->save();

            // update transazione
            $transaction->update([
                'name'            => $validated['name'],
                'date'            => $validated['date'],
                'from_account'    => $validated['from_account'],
                'to_account'      => $validated['to_account'],
                'amount'          => $validated['amount'],
                'transaction_type'=> 'fundtransfer',
                'category_name'   => 'Fund Transfer',
                'user_id'         => $userId,
            ]);

            // applica nuovi saldi
            $newFrom = BankAccount::where('user_id',$userId)->findOrFail($validated['from_account']);
            $newTo   = BankAccount::where('user_id',$userId)->findOrFail($validated['to_account']);
            $newFrom->starting_balance -= $validated['amount'];
            $newTo->starting_balance   += $validated['amount'];
            $newFrom->save();
            $newTo->save();
        } else {
            // ripristina saldo del vecchio conto
            $oldBank = BankAccount::where('user_id',$userId)->findOrFail($transaction->bank_account_id);
            if ($transaction->transaction_type === 'income') {
                $oldBank->starting_balance -= $oldAmount;
            } else {
                $oldBank->starting_balance += $oldAmount;
            }
            $oldBank->save();

            // risali dal BUDGET alla categoria
            $budget = Budget::with('category')
                ->where('user_id', $userId)
                ->findOrFail($validated['category']);

            $categoryName = $budget->category_name ?? optional($budget->category)->name;
            $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

            $isInternal = $request->boolean('internal_transfer');

            // update transazione
            $transaction->update([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_id'       => $categoryId,       // <-- FK corretta
                'category_name'     => $categoryName,
                'bank_account_id'   => $validated['bank_account'],
                'amount'            => $validated['amount'],
                'transaction_type'  => $validated['transaction_type'],
                'internal_transfer' => $isInternal,
                'user_id'           => $userId,
            ]);

            // applica saldo sul nuovo conto
            $newBank = BankAccount::where('user_id',$userId)->findOrFail($validated['bank_account']);
            if ($validated['transaction_type'] === 'income') {
                $newBank->starting_balance += $validated['amount'];
            } else {
                $newBank->starting_balance -= $validated['amount'];
            }
            $newBank->save();
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    /**
     * Global add (dai pulsanti rapidi / modal). Il form invia: category = budgets.id
     */
    public function globalAddTransaction(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'date'            => ['required', 'date'],
            'category'        => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'    => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'          => ['required', 'numeric', 'min:1'],
            'transaction_type'=> ['required', 'string', 'max:255'],
            'internal_transfer'=> ['nullable', 'boolean'],
        ]);

        $isInternalTransfer = $request->boolean('internal_transfer');

        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $bankAccount = BankAccount::where('user_id', $userId)
            ->findOrFail($validated['bank_account']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        DB::transaction(function () use ($validated, $isInternalTransfer, $categoryName, $categoryId, $budget, $bankAccount, $userId) {

            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,             // <-- FK corretta (budget_categories.id)
                'bank_account_id'   => $validated['bank_account'],
                'amount'            => $validated['amount'],
                'transaction_type'  => $validated['transaction_type'],
                'internal_transfer' => $isInternalTransfer,
                'user_id'           => $userId,
            ]);

            if ($validated['transaction_type'] === 'income') {
                $bankAccount->increment('starting_balance', $validated['amount']);
                // opzionale: $budget->increment('amount', $validated['amount']);
            } else {
                $bankAccount->decrement('starting_balance', $validated['amount']);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    public function globalFundTransfer(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'from_account' => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'to_account'   => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'       => ['required', 'numeric', 'min:1'],
        ]);

        $transaction = Transaction::create([
            'name'             => $validated['name'],
            'date'             => $validated['date'],
            'category_name'    => 'Fund Transfer',
            'from_account'     => $validated['from_account'],
            'to_account'       => $validated['to_account'],
            'amount'           => $validated['amount'],
            'transaction_type' => 'fundtransfer',
            'user_id'          => $userId,
        ]);

        $from = BankAccount::where('user_id',$userId)->findOrFail($validated['from_account']);
        $to   = BankAccount::where('user_id',$userId)->findOrFail($validated['to_account']);

        $from->starting_balance -= $validated['amount'];
        $to->starting_balance   += $validated['amount'];
        $from->save();
        $to->save();

        return redirect()->back()->with('success', 'Fund transferred successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $userId = Auth::id();

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($transaction->transaction_type === 'fundtransfer') {
            $from = BankAccount::where('user_id',$userId)->find($transaction->from_account);
            $to   = BankAccount::where('user_id',$userId)->find($transaction->to_account);
            if ($from) { $from->starting_balance += $transaction->amount; $from->save(); }
            if ($to)   { $to->starting_balance   -= $transaction->amount; $to->save(); }
        } else {
            $bank = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
            if ($bank) {
                if ($transaction->transaction_type === 'income') {
                    $bank->starting_balance -= $transaction->amount;
                } else {
                    $bank->starting_balance += $transaction->amount;
                }
                $bank->save();
            }
        }

        $transaction->delete();

        return redirect()->route('transactions.index')->with('success', 'Transaction removed successfully.');
    }
}
