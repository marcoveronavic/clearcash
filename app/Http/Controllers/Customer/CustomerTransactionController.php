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

/**
 * Convenzione segno:
 *  - expense > 0  = spesa (outflow)  ⇒ diminuisce saldo
 *  - expense < 0  = refund           ⇒ aumenta saldo
 *  - income  > 0  = entrata          ⇒ aumenta saldo
 */
class CustomerTransactionController extends Controller
{
    /**
     * Lista transazioni con stesso PERIODO della Budget page (mese corrente)
     * e filtro opzionale per conto (?bank_account_id=ID | all).
     */
    public function index(Request $request)
    {
        $userId = Auth::id();

        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $currentBankId = $request->query('bank_account_id', 'all');

        $query = Transaction::where('user_id', $userId)
            ->with('category')
            ->whereBetween('date', [$start, $end]);

        if ($currentBankId !== 'all' && $currentBankId !== null && $currentBankId !== '') {
            $query->where('bank_account_id', (int)$currentBankId);
        }

        $transactions = $query
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($t) {
                // kind = refund se expense con importo negativo
                $t->kind = ($t->transaction_type === 'expense' && (float)$t->amount < 0)
                    ? 'refund'
                    : $t->transaction_type; // 'expense' | 'income'
                return $t;
            });

        $groupedTransactions = $transactions->groupBy(fn ($t) =>
        \Carbon\Carbon::parse($t->date)->format('Y-m-d')
        );

        // Totali Outflow/Refund per ogni giorno
        $dailyOutflow = [];
        $dailyRefunds = [];
        foreach ($groupedTransactions as $date => $items) {
            $dailyOutflow[$date] = $items->where('transaction_type', 'expense')
                ->sum(fn ($t) => max(0, (float)$t->amount));
            $dailyRefunds[$date] = $items->where('transaction_type', 'expense')
                ->sum(fn ($t) => max(0, -(float)$t->amount));
        }

        // TUTTI i conti (incluso “pension”) per il filtro
        $bankAccounts = BankAccount::where('user_id', $userId)
            ->orderBy('account_name', 'asc')
            ->get();

        return view('customer.pages.transactions.index', compact(
            'groupedTransactions',
            'transactions',
            'bankAccounts',
            'dailyOutflow',
            'dailyRefunds',
            'currentBankId',
            'start',
            'end'
        ));
    }

    /**
     * (Compat) Filtro per nome conto “/transactions/bank/{bankName}”.
     * Reindirizza all’index con bank_account_id.
     */
    public function filterByBank($bankName)
    {
        $bank = BankAccount::where('user_id', Auth::id())
            ->whereRaw("LOWER(REPLACE(account_name, ' ', '-')) = ?", [strtolower($bankName)])
            ->firstOrFail();

        return redirect()->route('transactions.index', ['bank_account_id' => $bank->id]);
    }

    public function create()
    {
        $bankAccounts = BankAccount::where('user_id', Auth::id())
            ->orderBy('account_name', 'asc')
            ->get();

        $categories = Budget::where('user_id', Auth::id())->get();

        return view('customer.pages.transactions.create', compact('bankAccounts', 'categories'));
    }

    /**
     * Store (pagina create) – supporta anche transfer_to_account opzionale.
     * Form: category = budgets.id
     */
    public function store(Request $request)
    {
        $userId = Auth::id();

        // Normalizza bank_account_id / bank_account
        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id']; // legacy compat
        }
        $request->replace($data);

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'date'                 => ['required', 'date'],
            'category'             => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'               => ['required', 'numeric'], // accetta negativi
            'transaction_type'     => ['required', 'string', 'in:income,expense'],
            'bank_account'         => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account_id'      => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'transfer_to_account'  => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'internal_transfer'    => ['sometimes', 'boolean'],
        ]);

        $bankId = $validated['bank_account'] ?? $validated['bank_account_id'] ?? null;
        if (!$bankId) {
            return back()->withErrors(['bank_account_id' => 'Please choose a bank account.'])->withInput();
        }

        $amount = (float)$validated['amount'];
        $type   = $validated['transaction_type'];
        $isIT   = $request->boolean('internal_transfer');
        $toId   = isset($validated['transfer_to_account']) ? (int)$validated['transfer_to_account'] : null;

        if ($type === 'income' && $amount < 0) {
            return back()->withErrors(['amount' => 'Income cannot be negative. Use expense with negative amount for refunds.'])->withInput();
        }

        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        // Internal transfer con conto destinazione diverso -> coppia
        if ($isIT && $toId && $toId !== (int)$bankId) {
            DB::transaction(function () use ($validated, $userId, $bankId, $toId, $categoryName, $categoryId) {
                $amt = (float) abs($validated['amount']); // trasferimenti sempre positivi

                // from: expense (+)
                Transaction::create([
                    'name'              => $validated['name'],
                    'date'              => $validated['date'],
                    'category_name'     => $categoryName,
                    'category_id'       => $categoryId,
                    'bank_account_id'   => (int)$bankId,
                    'amount'            => $amt,
                    'transaction_type'  => 'expense',
                    'internal_transfer' => true,
                    'user_id'           => $userId,
                ]);

                // to: income (+)
                Transaction::create([
                    'name'              => $validated['name'],
                    'date'              => $validated['date'],
                    'category_name'     => 'Fund Transfer In',
                    'bank_account_id'   => (int)$toId,
                    'amount'            => $amt,
                    'transaction_type'  => 'income',
                    'internal_transfer' => true,
                    'user_id'           => $userId,
                ]);

                // saldi
                $from = BankAccount::where('user_id',$userId)->findOrFail((int)$bankId);
                $to   = BankAccount::where('user_id',$userId)->findOrFail((int)$toId);
                $from->starting_balance -= $amt;
                $to->starting_balance   += $amt;
                $from->save(); $to->save();
            });

            return redirect()->route('transactions.index')->with('success', 'Internal transfer recorded.');
        }

        // Singola transazione (anche IT = true ma senza toId)
        DB::transaction(function () use ($validated, $userId, $bankId, $amount, $type, $categoryName, $categoryId) {
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,
                'bank_account_id'   => (int)$bankId,
                'amount'            => $amount, // manteniamo il segno
                'transaction_type'  => $type,
                'internal_transfer' => request()->boolean('internal_transfer'),
                'user_id'           => $userId,
            ]);

            // delta saldo in base a tipo/segno
            $bank = BankAccount::where('user_id',$userId)->findOrFail((int)$bankId);
            if ($type === 'income') {
                $bank->starting_balance += $amount; // income > 0
            } else {
                // expense: >0 outflow => -, <0 refund => +
                $bank->starting_balance += ($amount >= 0 ? -$amount : abs($amount));
            }
            $bank->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $userId      = Auth::id();
        $transaction = Transaction::where('user_id',$userId)->findOrFail($id);

        if ($transaction->internal_transfer) {
            return redirect()->back()->withErrors([
                'transfer' => 'Editing internal transfers is not supported. Please delete and recreate the transfer.'
            ]);
        }

        // normalizza bank_account_id / bank_account
        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id'];
        }
        if (array_key_exists('date', $data) && ($data['date'] === '' || $data['date'] === null)) {
            unset($data['date']);
        }
        $request->replace($data);

        $rules = [
            'name'               => ['sometimes', 'string', 'max:255'],
            'date'               => ['sometimes', 'date'],
            'amount'             => ['sometimes', 'numeric'], // accetta negativi
            'transaction_type'   => ['sometimes', 'string', 'in:income,expense,transfer'],
            'category'           => ['sometimes','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'       => ['sometimes','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account_id'    => ['sometimes','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'internal_transfer'  => ['sometimes', 'boolean'],
        ];

        $validated = $request->validate($rules);

        // Nuovi valori (manteniamo segno amount)
        $newName   = $validated['name']              ?? $transaction->name;
        $newDate   = $validated['date']              ?? $transaction->date;
        $newAmount = array_key_exists('amount', $validated)
            ? (float)$validated['amount']
            : (float)$transaction->amount;
        $newType   = $validated['transaction_type']  ?? $transaction->transaction_type;
        $newBankId = $validated['bank_account']
            ?? ($validated['bank_account_id'] ?? $transaction->bank_account_id);

        if ($newType === 'income' && $newAmount < 0) {
            return back()->withErrors(['amount' => 'Income cannot be negative. Use expense with negative amount for refunds.'])->withInput();
        }

        // valida banca (se indicata)
        if (isset($validated['bank_account']) || isset($validated['bank_account_id'])) {
            BankAccount::where('id', $newBankId)
                ->where('user_id', $userId)
                ->firstOrFail();
        }

        // Categoria (se cambiata)
        if (isset($validated['category'])) {
            $budget = Budget::with('category')
                ->where('user_id', $userId)
                ->findOrFail($validated['category']);
            $newCategoryName = $budget->category_name ?? optional($budget->category)->name;
            $newCategoryId   = $budget->category_id   ?? optional($budget->category)->id;
        } else {
            $newCategoryName = $transaction->category_name;
            $newCategoryId   = $transaction->category_id;
        }

        DB::transaction(function () use ($transaction, $userId, $newName, $newDate, $newAmount, $newType, $newBankId, $newCategoryId, $newCategoryName) {
            // Revert effetto della transazione attuale sul vecchio conto
            $oldBank   = BankAccount::where('user_id',$userId)->findOrFail($transaction->bank_account_id);
            $oldAmount = (float)$transaction->amount;
            $oldType   = $transaction->transaction_type;

            // delta originale sul saldo:
            // income: +oldAmount
            // expense: oldAmount>=0 => -oldAmount ; oldAmount<0 (refund) => +abs(oldAmount)
            $oldEffect = ($oldType === 'income')
                ? $oldAmount
                : ($oldAmount >= 0 ? -$oldAmount : abs($oldAmount));

            // Revert:
            $oldBank->starting_balance -= $oldEffect;
            $oldBank->save();

            // Aggiorna la transazione
            $transaction->update([
                'name'              => $newName,
                'date'              => $newDate,
                'category_id'       => $newCategoryId,
                'category_name'     => $newCategoryName,
                'bank_account_id'   => (int)$newBankId,
                'amount'            => $newAmount,  // mantiene segno
                'transaction_type'  => $newType,
                'internal_transfer' => $transaction->internal_transfer,
                'user_id'           => $userId,
            ]);

            // Applica nuovo effetto sul nuovo conto
            $newBank = BankAccount::where('user_id',$userId)->findOrFail((int)$newBankId);
            $newEffect = ($newType === 'income')
                ? $newAmount
                : ($newAmount >= 0 ? -$newAmount : abs($newAmount));
            $newBank->starting_balance += $newEffect;
            $newBank->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    /**
     * Aggiunta veloce (quick modal) – supporta transfer_to_account opzionale.
     */
    public function globalAddTransaction(Request $request)
    {
        $userId = Auth::id();

        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id']; // legacy compat
        }
        $request->replace($data);

        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'date'                 => ['required', 'date'],
            'category'             => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'               => ['required', 'numeric'],
            'transaction_type'     => ['required', 'string', 'in:income,expense'],
            'bank_account'         => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account_id'      => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'transfer_to_account'  => ['nullable','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'internal_transfer'    => ['sometimes', 'boolean'],
        ]);

        $bankId = $validated['bank_account'] ?? $validated['bank_account_id'] ?? null;
        if (!$bankId) {
            return back()->withErrors(['bank_account_id' => 'Please choose a bank account.'])->withInput();
        }

        $amount = (float)$validated['amount'];
        $type   = $validated['transaction_type'];
        $isIT   = $request->boolean('internal_transfer');
        $toId   = isset($validated['transfer_to_account']) ? (int)$validated['transfer_to_account'] : null;

        if ($type === 'income' && $amount < 0) {
            return back()->withErrors(['amount' => 'Income cannot be negative. Use expense with negative amount for refunds.'])->withInput();
        }

        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        if ($isIT && $toId && $toId !== (int)$bankId) {
            DB::transaction(function () use ($validated, $userId, $bankId, $toId, $categoryName, $categoryId) {
                $amt = (float) abs($validated['amount']);

                Transaction::create([
                    'name'              => $validated['name'],
                    'date'              => $validated['date'],
                    'category_name'     => $categoryName,
                    'category_id'       => $categoryId,
                    'bank_account_id'   => (int)$bankId,
                    'amount'            => $amt,
                    'transaction_type'  => 'expense',
                    'internal_transfer' => true,
                    'user_id'           => $userId,
                ]);

                Transaction::create([
                    'name'              => $validated['name'],
                    'date'              => $validated['date'],
                    'category_name'     => 'Fund Transfer In',
                    'bank_account_id'   => (int)$toId,
                    'amount'            => $amt,
                    'transaction_type'  => 'income',
                    'internal_transfer' => true,
                    'user_id'           => $userId,
                ]);

                $from = BankAccount::where('user_id',$userId)->findOrFail((int)$bankId);
                $to   = BankAccount::where('user_id',$userId)->findOrFail((int)$toId);
                $from->starting_balance -= $amt;
                $to->starting_balance   += $amt;
                $from->save(); $to->save();
            });

            return redirect()->route('transactions.index')->with('success', 'Internal transfer recorded.');
        }

        DB::transaction(function () use ($validated, $userId, $bankId, $amount, $type, $categoryName, $categoryId) {
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,
                'bank_account_id'   => (int)$bankId,
                'amount'            => $amount, // segno preservato
                'transaction_type'  => $type,
                'internal_transfer' => request()->boolean('internal_transfer'),
                'user_id'           => $userId,
            ]);

            $bankAccount = BankAccount::where('user_id', $userId)->findOrFail((int)$bankId);
            if ($type === 'income') {
                $bankAccount->starting_balance += $amount;
            } else {
                $bankAccount->starting_balance += ($amount >= 0 ? -$amount : abs($amount));
            }
            $bankAccount->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Fund transfer classico (sempre coppia).
     */
    public function globalFundTransfer(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'from_account' => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'to_account'   => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'       => ['required', 'numeric', 'min:1'], // i trasferimenti hanno importo positivo
            'category'     => ['nullable','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
        ]);

        $amount = abs((float)$validated['amount']);

        $categoryName = 'Fund Transfer Out';
        $categoryId   = null;

        if (!empty($validated['category'])) {
            $budget = Budget::with('category')
                ->where('user_id', $userId)
                ->findOrFail($validated['category']);
            $categoryName = $budget->category_name ?? optional($budget->category)->name ?? 'Fund Transfer Out';
            $categoryId   = $budget->category_id   ?? optional($budget->category)->id;
        }

        DB::transaction(function () use ($validated, $userId, $amount, $categoryName, $categoryId) {
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,
                'bank_account_id'   => (int)$validated['from_account'],
                'amount'            => $amount,
                'transaction_type'  => 'expense',
                'internal_transfer' => true,
                'user_id'           => $userId,
            ]);

            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => 'Fund Transfer In',
                'bank_account_id'   => (int)$validated['to_account'],
                'amount'            => $amount,
                'transaction_type'  => 'income',
                'internal_transfer' => true,
                'user_id'           => $userId,
            ]);

            $from = BankAccount::where('user_id',$userId)->findOrFail((int)$validated['from_account']);
            $to   = BankAccount::where('user_id',$userId)->findOrFail((int)$validated['to_account']);

            $from->starting_balance -= $amount;
            $to->starting_balance   += $amount;

            $from->save();
            $to->save();
        });

        return redirect()->back()->with('success', 'Fund transferred successfully.');
    }

    /**
     * Elimina una transazione e ripristina il saldo del conto in base al segno.
     */
    public function destroy(string $id)
    {
        $userId = Auth::id();

        $transaction = Transaction::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($transaction->internal_transfer) {
            if ($transaction->transaction_type === 'expense') {
                $from = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
                if ($from) { $from->starting_balance += abs((float)$transaction->amount); $from->save(); }

                $pair = Transaction::where('user_id',$userId)
                    ->where('internal_transfer', true)
                    ->where('transaction_type', 'income')
                    ->whereDate('date', $transaction->date)
                    ->where('name', $transaction->name)
                    ->where('amount', $transaction->amount)
                    ->where('id', '!=', $transaction->id)
                    ->orderBy('id','desc')
                    ->first();

                if ($pair) {
                    $to = BankAccount::where('user_id',$userId)->find($pair->bank_account_id);
                    if ($to) { $to->starting_balance -= abs((float)$pair->amount); $to->save(); }
                    $pair->delete();
                }

                $transaction->delete();
            } else {
                $to = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
                if ($to) { $to->starting_balance -= abs((float)$transaction->amount); $to->save(); }

                $pair = Transaction::where('user_id',$userId)
                    ->where('internal_transfer', true)
                    ->where('transaction_type', 'expense')
                    ->whereDate('date', $transaction->date)
                    ->where('name', $transaction->name)
                    ->where('amount', $transaction->amount)
                    ->where('id', '!=', $transaction->id)
                    ->orderBy('id','desc')
                    ->first();

                if ($pair) {
                    $from = BankAccount::where('user_id',$userId)->find($pair->bank_account_id);
                    if ($from) { $from->starting_balance += abs((float)$pair->amount); $from->save(); }
                    $pair->delete();
                }

                $transaction->delete();
            }

            return redirect()->route('transactions.index')->with('success', 'Transfer removed successfully.');
        }

        // Revert di una transazione "normale" in base a tipo/segno
        $bank = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
        if ($bank) {
            $amt  = (float)$transaction->amount;
            $type = $transaction->transaction_type;

            // effetto originale:
            // income: +amt ; expense: (amt>=0 ? -amt : +abs(amt))
            $effect = ($type === 'income') ? $amt : ($amt >= 0 ? -$amt : abs($amt));

            // revert
            $bank->starting_balance -= $effect;
            $bank->save();
        }

        $transaction->delete();

        return redirect()->route('transactions.index')->with('success', 'Transaction removed successfully.');
    }
}
