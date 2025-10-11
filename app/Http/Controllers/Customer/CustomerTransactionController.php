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
        $totalExpenseAmountToday     = $todayTransactions->where('transaction_type', 'expense')->sum('amount');

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

        // accetta anche bank_account_id come alias
        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id'];
        }
        $request->merge($data);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'date'               => ['required', 'date'],
            // Arriva l'ID DEL BUDGET dell'utente
            'category'           => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'       => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'             => ['required', 'numeric', 'min:1'],
            'transaction_type'   => ['required', 'string', 'max:255'],
            'internal_transfer'  => ['nullable', 'boolean'],
        ]);

        $isInternalTransfer = $request->boolean('internal_transfer');
        $amount             = abs((float)$validated['amount']); // forziamo positivo

        // Risali dal budget scelto alla categoria VERA (budget_categories.id)
        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;  // <-- FK corretta

        DB::transaction(function () use ($validated, $isInternalTransfer, $categoryName, $categoryId, $amount, $userId) {
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,                   // <-- salva id della tabella budget_categories
                'bank_account_id'   => $validated['bank_account'],
                'amount'            => $amount,                        // positivo
                'transaction_type'  => $validated['transaction_type'],
                'internal_transfer' => $isInternalTransfer,
                'user_id'           => $userId,
            ]);

            // aggiorna saldo conto
            $bankAccount = BankAccount::where('user_id', $userId)->findOrFail($validated['bank_account']);
            if ($validated['transaction_type'] === 'income') {
                $bankAccount->starting_balance += $amount;
            } else {
                $bankAccount->starting_balance -= $amount;
            }
            $bankAccount->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Update the specified resource in storage.
     * Il form invia: category = budgets.id
     * Supporta update parziali (se modifichi solo la banca).
     */
    public function update(Request $request, string $id)
    {
        $userId      = Auth::id();
        $transaction = Transaction::where('user_id',$userId)->findOrFail($id);

        // Non gestiamo l'edit delle coppie di internal transfer (troppo ambigua in UI)
        if ($transaction->internal_transfer) {
            return redirect()->back()->withErrors(['transfer' => 'Editing internal transfers is not supported. Please delete and recreate the transfer.']);
        }

        // accetta anche bank_account_id come alias
        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id'];
        }
        // se 'date' arriva vuota, rimuovila dall'input (così non invalida e non sovrascrive)
        if (array_key_exists('date', $data) && ($data['date'] === '' || $data['date'] === null)) {
            unset($data['date']);
        }
        $request->replace($data);

        // regole: "sometimes" per consentire update parziali
        $rules = [
            'name'               => ['sometimes', 'string', 'max:255'],
            'date'               => ['sometimes', 'date'],
            'amount'             => ['sometimes', 'numeric', 'min:1'],
            'transaction_type'   => ['sometimes', 'string', 'in:income,expense,transfer'],
            // category = budgets.id (non budget_categories.id)
            'category'           => ['sometimes','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'       => ['sometimes','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'internal_transfer'  => ['sometimes', 'boolean'],
        ];

        $validated = $request->validate($rules);

        // Valori nuovi con fallback a quelli esistenti
        $newName   = $validated['name']            ?? $transaction->name;
        $newDate   = $validated['date']            ?? $transaction->date;
        $newAmount = array_key_exists('amount', $validated)
            ? abs((float)$validated['amount'])
            : abs((float)$transaction->amount);
        $newType   = $validated['transaction_type'] ?? $transaction->transaction_type;
        $newBankId = $validated['bank_account']     ?? $transaction->bank_account_id;

        // Se è stato passato un bank_account, deve appartenere all'utente
        if (isset($validated['bank_account'])) {
            BankAccount::where('id', $newBankId)
                ->where('user_id', $userId)
                ->firstOrFail();
        }

        // Categoria: se cambia, risali dal budget alla category vera
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

            // 1) ripristina saldo del vecchio conto (ABS safe: vecchi record potevano avere importi negativi)
            $oldBank  = BankAccount::where('user_id',$userId)->findOrFail($transaction->bank_account_id);
            $oldDelta = abs((float)$transaction->amount);
            if ($transaction->transaction_type === 'income') {
                $oldBank->starting_balance -= $oldDelta;  // annulla un income
            } else {
                $oldBank->starting_balance += $oldDelta;  // annulla un expense
            }
            $oldBank->save();

            // 2) aggiorna transazione
            $transaction->update([
                'name'              => $newName,
                'date'              => $newDate,
                'category_id'       => $newCategoryId,       // <-- FK corretta
                'category_name'     => $newCategoryName,
                'bank_account_id'   => $newBankId,
                'amount'            => $newAmount,           // positivo
                'transaction_type'  => $newType,
                'internal_transfer' => $transaction->internal_transfer, // invariato
                'user_id'           => $userId,
            ]);

            // 3) applica saldo sul nuovo conto (ABS safe)
            $newBank = BankAccount::where('user_id',$userId)->findOrFail($newBankId);
            if ($newType === 'income') {
                $newBank->starting_balance += $newAmount;
            } else {
                $newBank->starting_balance -= $newAmount;
            }
            $newBank->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    /**
     * Global add (dai pulsanti rapidi / modal). Il form invia: category = budgets.id
     */
    public function globalAddTransaction(Request $request)
    {
        $userId = Auth::id();

        // accetta anche bank_account_id come alias
        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id'];
        }
        $request->merge($data);

        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'date'               => ['required', 'date'],
            'category'           => ['required','integer', Rule::exists('budgets','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'bank_account'       => ['required','integer', Rule::exists('bank_accounts','id')->where(fn($q)=>$q->where('user_id',$userId))],
            'amount'             => ['required', 'numeric', 'min:1'],
            'transaction_type'   => ['required', 'string', 'max:255'],
            'internal_transfer'  => ['nullable', 'boolean'],
        ]);

        $isInternalTransfer = $request->boolean('internal_transfer');
        $amount             = abs((float)$validated['amount']); // positivo

        $budget = Budget::with('category')
            ->where('user_id', $userId)
            ->findOrFail($validated['category']);

        $bankAccount = BankAccount::where('user_id', $userId)
            ->findOrFail($validated['bank_account']);

        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        DB::transaction(function () use ($validated, $isInternalTransfer, $categoryName, $categoryId, $bankAccount, $userId, $amount) {

            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => $categoryName,
                'category_id'       => $categoryId,             // <-- FK corretta (budget_categories.id)
                'bank_account_id'   => $validated['bank_account'],
                'amount'            => $amount,
                'transaction_type'  => $validated['transaction_type'],
                'internal_transfer' => $isInternalTransfer,
                'user_id'           => $userId,
            ]);

            if ($validated['transaction_type'] === 'income') {
                $bankAccount->increment('starting_balance', $amount);
            } else {
                $bankAccount->decrement('starting_balance', $amount);
            }
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Fund transfer: crea DUE transazioni (expense + income) marcate come internal_transfer.
     */
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

        $amount = abs((float)$validated['amount']);

        DB::transaction(function () use ($validated, $userId, $amount) {

            // 1) Spesa dal conto origine
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => 'Fund Transfer Out',
                'bank_account_id'   => $validated['from_account'],
                'amount'            => $amount,             // positivo
                'transaction_type'  => 'expense',
                'internal_transfer' => true,
                'user_id'           => $userId,
            ]);

            // 2) Entrata sul conto destinazione
            Transaction::create([
                'name'              => $validated['name'],
                'date'              => $validated['date'],
                'category_name'     => 'Fund Transfer In',
                'bank_account_id'   => $validated['to_account'],
                'amount'            => $amount,             // positivo
                'transaction_type'  => 'income',
                'internal_transfer' => true,
                'user_id'           => $userId,
            ]);

            // 3) Aggiorna i saldi dei conti
            $from = BankAccount::where('user_id',$userId)->findOrFail($validated['from_account']);
            $to   = BankAccount::where('user_id',$userId)->findOrFail($validated['to_account']);

            $from->starting_balance -= $amount;
            $to->starting_balance   += $amount;

            $from->save();
            $to->save();
        });

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

        // Se internal transfer: trova la "coppia" e ripristina entrambi i saldi
        if ($transaction->internal_transfer) {

            if ($transaction->transaction_type === 'expense') {
                // Rimborsa conto "from"
                $from = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
                if ($from) { $from->starting_balance += abs((float)$transaction->amount); $from->save(); }

                // Trova la "income" gemella
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
                    // scala il conto "to"
                    $to = BankAccount::where('user_id',$userId)->find($pair->bank_account_id);
                    if ($to) { $to->starting_balance -= abs((float)$pair->amount); $to->save(); }
                    $pair->delete();
                }

                $transaction->delete();
            } else { // income
                // scala conto "to"
                $to = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
                if ($to) { $to->starting_balance -= abs((float)$transaction->amount); $to->save(); }

                // Trova la "expense" gemella
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
                    // rimborsa conto "from"
                    $from = BankAccount::where('user_id',$userId)->find($pair->bank_account_id);
                    if ($from) { $from->starting_balance += abs((float)$pair->amount); $from->save(); }
                    $pair->delete();
                }

                $transaction->delete();
            }

            return redirect()->route('transactions.index')->with('success', 'Transfer removed successfully.');
        }

        // Transazione normale: ripristina saldo e cancella
        $bank = BankAccount::where('user_id',$userId)->find($transaction->bank_account_id);
        if ($bank) {
            $delta = abs((float)$transaction->amount);
            if ($transaction->transaction_type === 'income') {
                $bank->starting_balance -= $delta;
            } else {
                $bank->starting_balance += $delta;
            }
            $bank->save();
        }

        $transaction->delete();

        return redirect()->route('transactions.index')->with('success', 'Transaction removed successfully.');
    }
}
