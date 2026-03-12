<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Budget;
use App\Models\Transaction;
use App\Services\CurrencyService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerTransactionController extends Controller
{
    private CurrencyService $fx;

    public function __construct(CurrencyService $fx)
    {
        $this->fx = $fx;
    }

    // ---------------------------------------------------------------------------
    // Helper: prepara i campi currency per una transazione
    // ---------------------------------------------------------------------------
    private function resolveCurrencyFields(float $amount, BankAccount $bank): array
    {
        $user         = auth()->user();
        $baseCurrency = $user->base_currency ?? 'GBP';
        $txCurrency   = $bank->currency ?? $baseCurrency;

        if ($txCurrency === $baseCurrency) {
            return [
                'currency'      => $txCurrency,
                'amount_native' => $amount,
                'exchange_rate' => 1.0,
                'amount'        => $amount,
            ];
        }

        $converted = $this->fx->convert(abs($amount), $txCurrency, $baseCurrency);

        return [
            'currency'      => $txCurrency,
            'amount_native' => $amount,
            'exchange_rate' => $converted['rate'],
            'amount'        => $amount >= 0 ? $converted['amount'] : -$converted['amount'],
        ];
    }

    // ---------------------------------------------------------------------------
    // INDEX
    // ---------------------------------------------------------------------------
    public function index(Request $request)
    {
        $userId = Auth::id();

        $currentBankId = $request->query('bank_account_id', 'all');
        $dateFrom      = $request->query('date_from');
        $dateTo        = $request->query('date_to');
        $period        = $request->query('period', 'all');
        $txType        = $request->query('type', 'all');

        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        switch ($period) {
            case 'this_month':
                $dateFrom = $now->copy()->startOfMonth()->toDateString();
                $dateTo   = $now->copy()->endOfMonth()->toDateString();
                break;
            case 'last_month':
                $dateFrom = $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString();
                $dateTo   = $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString();
                break;
            case 'this_year':
                $dateFrom = $now->copy()->startOfYear()->toDateString();
                $dateTo   = $now->copy()->endOfYear()->toDateString();
                break;
            case 'last_30':
                $dateFrom = $now->copy()->subDays(30)->toDateString();
                $dateTo   = $now->toDateString();
                break;
            case 'last_90':
                $dateFrom = $now->copy()->subDays(90)->toDateString();
                $dateTo   = $now->toDateString();
                break;
            case 'custom':
                break;
            default:
                $dateFrom = null;
                $dateTo   = null;
                break;
        }

        $query = Transaction::where('user_id', $userId)->with('category');

        if ($currentBankId !== 'all' && $currentBankId !== null && $currentBankId !== '') {
            $query->where('bank_account_id', (int) $currentBankId);
        }

        if ($dateFrom) $query->whereDate('date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('date', '<=', $dateTo);

        switch ($txType) {
            case 'income':
                $query->where('transaction_type', 'income')
                    ->where(function ($q) {
                        $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                    });
                break;
            case 'expense':
                $query->where('transaction_type', 'expense')
                    ->where(function ($q) {
                        $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
                    });
                break;
            case 'transfer':
                $query->where('internal_transfer', true);
                break;
        }

        $transactions = $query
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($t) {
                $t->kind = ($t->transaction_type === 'expense' && (float) $t->amount < 0)
                    ? 'refund'
                    : $t->transaction_type;
                return $t;
            });

        $groupedTransactions = $transactions->groupBy(fn ($t) =>
        Carbon::parse($t->date)->format('Y-m-d')
        );

        $dailyExpenses = [];
        foreach ($groupedTransactions as $date => $items) {
            $dailyExpenses[$date] = $items->where('transaction_type', 'expense')
                ->sum(fn ($t) => max(0, (float) $t->amount));
        }

        $totalIncome  = $transactions->where('transaction_type', 'income')->sum('amount');
        $totalExpense = $transactions->where('transaction_type', 'expense')->sum(fn ($t) => abs((float) $t->amount));

        $bankAccounts = BankAccount::where('user_id', $userId)
            ->orderBy('account_name', 'asc')
            ->get();

        return view('customer.pages.transactions.index', compact(
            'groupedTransactions',
            'transactions',
            'bankAccounts',
            'dailyExpenses',
            'currentBankId',
            'dateFrom',
            'dateTo',
            'period',
            'txType',
            'totalIncome',
            'totalExpense'
        ));
    }

    // ---------------------------------------------------------------------------
    // FILTER BY BANK
    // ---------------------------------------------------------------------------
    public function filterByBank($bankName)
    {
        $bank = BankAccount::where('user_id', Auth::id())
            ->whereRaw("LOWER(REPLACE(account_name, ' ', '-')) = ?", [strtolower($bankName)])
            ->firstOrFail();

        return redirect()->route('transactions.index', ['bank_account_id' => $bank->id]);
    }

    // ---------------------------------------------------------------------------
    // CREATE
    // ---------------------------------------------------------------------------
    public function create()
    {
        $bankAccounts = BankAccount::where('user_id', Auth::id())
            ->orderBy('account_name', 'asc')
            ->get();

        $categories = Budget::where('user_id', Auth::id())->get();

        return view('customer.pages.transactions.create', compact('bankAccounts', 'categories'));
    }

    // ---------------------------------------------------------------------------
    // STORE
    // ---------------------------------------------------------------------------
    public function store(Request $request)
    {
        $userId = Auth::id();

        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) {
            $data['bank_account'] = $data['bank_account_id'];
        }
        $request->replace($data);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'date'                => ['required', 'date'],
            'category'            => ['required', 'integer', Rule::exists('budgets', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'amount'              => ['required', 'numeric'],
            'transaction_type'    => ['required', 'string', 'in:income,expense'],
            'bank_account'        => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'bank_account_id'     => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'transfer_to_account' => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'internal_transfer'   => ['sometimes', 'boolean'],
        ]);

        $bankId = $validated['bank_account'] ?? $validated['bank_account_id'] ?? null;
        if (!$bankId) {
            return back()->withErrors(['bank_account_id' => 'Please choose a bank account.'])->withInput();
        }

        $amount = (float) $validated['amount'];
        $type   = $validated['transaction_type'];
        $isIT   = $request->boolean('internal_transfer');
        $toId   = isset($validated['transfer_to_account']) ? (int) $validated['transfer_to_account'] : null;

        if ($type === 'income' && $amount < 0) {
            return back()->withErrors(['amount' => 'Income cannot be negative.'])->withInput();
        }

        $budget       = Budget::with('category')->where('user_id', $userId)->findOrFail($validated['category']);
        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        if ($isIT && $toId && $toId !== (int) $bankId) {
            DB::transaction(function () use ($validated, $userId, $bankId, $toId, $categoryName, $categoryId) {
                $amt  = (float) abs($validated['amount']);
                $from = BankAccount::where('user_id', $userId)->findOrFail((int) $bankId);
                $to   = BankAccount::where('user_id', $userId)->findOrFail((int) $toId);

                $fxFrom = $this->resolveCurrencyFields($amt, $from);
                $fxTo   = $this->resolveCurrencyFields($amt, $to);

                Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => $categoryName, 'category_id' => $categoryId, 'bank_account_id' => (int) $bankId, 'transaction_type' => 'expense', 'internal_transfer' => true, 'user_id' => $userId], $fxFrom));
                Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => 'Fund Transfer In', 'bank_account_id' => (int) $toId, 'transaction_type' => 'income', 'internal_transfer' => true, 'user_id' => $userId], $fxTo));

                $from->starting_balance -= $amt; $from->save();
                $to->starting_balance   += $amt; $to->save();
            });
            return redirect()->route('transactions.index')->with('success', 'Internal transfer recorded.');
        }

        DB::transaction(function () use ($validated, $userId, $bankId, $amount, $type, $categoryName, $categoryId) {
            $bank = BankAccount::where('user_id', $userId)->findOrFail((int) $bankId);
            $fx   = $this->resolveCurrencyFields($amount, $bank);

            Transaction::create(array_merge([
                'name'             => $validated['name'],
                'date'             => $validated['date'],
                'category_name'    => $categoryName,
                'category_id'      => $categoryId,
                'bank_account_id'  => (int) $bankId,
                'transaction_type' => $type,
                'internal_transfer'=> request()->boolean('internal_transfer'),
                'user_id'          => $userId,
            ], $fx));

            if ($type === 'income') {
                $bank->starting_balance += $amount;
            } else {
                $bank->starting_balance += ($amount >= 0 ? -$amount : abs($amount));
            }
            $bank->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    // ---------------------------------------------------------------------------
    // UPDATE
    // ---------------------------------------------------------------------------
    public function update(Request $request, string $id)
    {
        $userId      = Auth::id();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);

        if ($transaction->internal_transfer) {
            return redirect()->back()->withErrors(['transfer' => 'Editing internal transfers is not supported.']);
        }

        $data = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) $data['bank_account'] = $data['bank_account_id'];
        if (array_key_exists('date', $data) && ($data['date'] === '' || $data['date'] === null)) unset($data['date']);
        $request->replace($data);

        $validated = $request->validate([
            'name'             => ['sometimes', 'string', 'max:255'],
            'date'             => ['sometimes', 'date'],
            'amount'           => ['sometimes', 'numeric'],
            'transaction_type' => ['sometimes', 'string', 'in:income,expense,transfer'],
            'category'         => ['sometimes', 'integer', Rule::exists('budgets', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'bank_account'     => ['sometimes', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'bank_account_id'  => ['sometimes', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'internal_transfer'=> ['sometimes', 'boolean'],
        ]);

        $newName   = $validated['name']   ?? $transaction->name;
        $newDate   = $validated['date']   ?? $transaction->date;
        $newAmount = array_key_exists('amount', $validated) ? (float) $validated['amount'] : (float) $transaction->amount;
        $newType   = $validated['transaction_type'] ?? $transaction->transaction_type;
        $newBankId = $validated['bank_account'] ?? ($validated['bank_account_id'] ?? $transaction->bank_account_id);

        if ($newType === 'income' && $newAmount < 0) {
            return back()->withErrors(['amount' => 'Income cannot be negative.'])->withInput();
        }

        if (isset($validated['category'])) {
            $budget           = Budget::with('category')->where('user_id', $userId)->findOrFail($validated['category']);
            $newCategoryName  = $budget->category_name ?? optional($budget->category)->name;
            $newCategoryId    = $budget->category_id   ?? optional($budget->category)->id;
        } else {
            $newCategoryName = $transaction->category_name;
            $newCategoryId   = $transaction->category_id;
        }

        DB::transaction(function () use ($transaction, $userId, $newName, $newDate, $newAmount, $newType, $newBankId, $newCategoryId, $newCategoryName) {
            // Reverti effetto vecchio
            $oldBank   = BankAccount::where('user_id', $userId)->findOrFail($transaction->bank_account_id);
            $oldAmount = (float) $transaction->amount;
            $oldType   = $transaction->transaction_type;
            $oldEffect = ($oldType === 'income') ? $oldAmount : ($oldAmount >= 0 ? -$oldAmount : abs($oldAmount));
            $oldBank->starting_balance -= $oldEffect;
            $oldBank->save();

            // Calcola nuovi campi currency
            $newBank = BankAccount::where('user_id', $userId)->findOrFail((int) $newBankId);
            $fx      = $this->resolveCurrencyFields($newAmount, $newBank);

            $transaction->update(array_merge([
                'name'             => $newName,
                'date'             => $newDate,
                'category_id'      => $newCategoryId,
                'category_name'    => $newCategoryName,
                'bank_account_id'  => (int) $newBankId,
                'transaction_type' => $newType,
                'internal_transfer'=> $transaction->internal_transfer,
                'user_id'          => $userId,
            ], $fx));

            $newEffect = ($newType === 'income') ? $newAmount : ($newAmount >= 0 ? -$newAmount : abs($newAmount));
            $newBank->starting_balance += $newEffect;
            $newBank->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    // ---------------------------------------------------------------------------
    // GLOBAL ADD TRANSACTION
    // ---------------------------------------------------------------------------
    public function globalAddTransaction(Request $request)
    {
        $userId = Auth::id();
        $data   = $request->all();
        if (isset($data['bank_account_id']) && !isset($data['bank_account'])) $data['bank_account'] = $data['bank_account_id'];
        $request->replace($data);

        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'date'                => ['required', 'date'],
            'category'            => ['required', 'integer', Rule::exists('budgets', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'amount'              => ['required', 'numeric'],
            'transaction_type'    => ['required', 'string', 'in:income,expense'],
            'bank_account'        => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'bank_account_id'     => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'transfer_to_account' => ['nullable', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'internal_transfer'   => ['sometimes', 'boolean'],
        ]);

        $bankId = $validated['bank_account'] ?? $validated['bank_account_id'] ?? null;
        if (!$bankId) return back()->withErrors(['bank_account_id' => 'Please choose a bank account.'])->withInput();

        $amount = (float) $validated['amount'];
        $type   = $validated['transaction_type'];
        $isIT   = $request->boolean('internal_transfer');
        $toId   = isset($validated['transfer_to_account']) ? (int) $validated['transfer_to_account'] : null;

        if ($type === 'income' && $amount < 0) return back()->withErrors(['amount' => 'Income cannot be negative.'])->withInput();

        $budget       = Budget::with('category')->where('user_id', $userId)->findOrFail($validated['category']);
        $categoryName = $budget->category_name ?? optional($budget->category)->name;
        $categoryId   = $budget->category_id   ?? optional($budget->category)->id;

        if ($isIT && $toId && $toId !== (int) $bankId) {
            DB::transaction(function () use ($validated, $userId, $bankId, $toId, $categoryName, $categoryId) {
                $amt  = (float) abs($validated['amount']);
                $from = BankAccount::where('user_id', $userId)->findOrFail((int) $bankId);
                $to   = BankAccount::where('user_id', $userId)->findOrFail((int) $toId);

                $fxFrom = $this->resolveCurrencyFields($amt, $from);
                $fxTo   = $this->resolveCurrencyFields($amt, $to);

                Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => $categoryName, 'category_id' => $categoryId, 'bank_account_id' => (int) $bankId, 'transaction_type' => 'expense', 'internal_transfer' => true, 'user_id' => $userId], $fxFrom));
                Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => 'Fund Transfer In', 'bank_account_id' => (int) $toId, 'transaction_type' => 'income', 'internal_transfer' => true, 'user_id' => $userId], $fxTo));

                $from->starting_balance -= $amt; $from->save();
                $to->starting_balance   += $amt; $to->save();
            });
            return redirect()->route('transactions.index')->with('success', 'Internal transfer recorded.');
        }

        DB::transaction(function () use ($validated, $userId, $bankId, $amount, $type, $categoryName, $categoryId) {
            $bank = BankAccount::where('user_id', $userId)->findOrFail((int) $bankId);
            $fx   = $this->resolveCurrencyFields($amount, $bank);

            Transaction::create(array_merge([
                'name'             => $validated['name'],
                'date'             => $validated['date'],
                'category_name'    => $categoryName,
                'category_id'      => $categoryId,
                'bank_account_id'  => (int) $bankId,
                'transaction_type' => $type,
                'internal_transfer'=> request()->boolean('internal_transfer'),
                'user_id'          => $userId,
            ], $fx));

            $bankAccount = $bank;
            if ($type === 'income') { $bankAccount->starting_balance += $amount; }
            else { $bankAccount->starting_balance += ($amount >= 0 ? -$amount : abs($amount)); }
            $bankAccount->save();
        });

        return redirect()->route('transactions.index')->with('success', 'Transaction recorded successfully.');
    }

    // ---------------------------------------------------------------------------
    // GLOBAL FUND TRANSFER
    // ---------------------------------------------------------------------------
    public function globalFundTransfer(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'date'         => ['required', 'date'],
            'from_account' => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'to_account'   => ['required', 'integer', Rule::exists('bank_accounts', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
            'amount'       => ['required', 'numeric', 'min:1'],
            'category'     => ['nullable', 'integer', Rule::exists('budgets', 'id')->where(fn ($q) => $q->where('user_id', $userId))],
        ]);

        $amount       = abs((float) $validated['amount']);
        $categoryName = 'Fund Transfer Out';
        $categoryId   = null;

        if (!empty($validated['category'])) {
            $budget       = Budget::with('category')->where('user_id', $userId)->findOrFail($validated['category']);
            $categoryName = $budget->category_name ?? optional($budget->category)->name ?? 'Fund Transfer Out';
            $categoryId   = $budget->category_id   ?? optional($budget->category)->id;
        }

        DB::transaction(function () use ($validated, $userId, $amount, $categoryName, $categoryId) {
            $from = BankAccount::where('user_id', $userId)->findOrFail((int) $validated['from_account']);
            $to   = BankAccount::where('user_id', $userId)->findOrFail((int) $validated['to_account']);

            $fxFrom = $this->resolveCurrencyFields($amount, $from);
            $fxTo   = $this->resolveCurrencyFields($amount, $to);

            Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => $categoryName, 'category_id' => $categoryId, 'bank_account_id' => (int) $validated['from_account'], 'transaction_type' => 'expense', 'internal_transfer' => true, 'user_id' => $userId], $fxFrom));
            Transaction::create(array_merge(['name' => $validated['name'], 'date' => $validated['date'], 'category_name' => 'Fund Transfer In', 'bank_account_id' => (int) $validated['to_account'], 'transaction_type' => 'income', 'internal_transfer' => true, 'user_id' => $userId], $fxTo));

            $from->starting_balance -= $amount; $from->save();
            $to->starting_balance   += $amount; $to->save();
        });

        return redirect()->back()->with('success', 'Fund transferred successfully.');
    }

    // ---------------------------------------------------------------------------
    // DESTROY
    // ---------------------------------------------------------------------------
    public function destroy(string $id)
    {
        $userId      = Auth::id();
        $transaction = Transaction::where('id', $id)->where('user_id', $userId)->firstOrFail();

        if ($transaction->receipt_path && Storage::disk('public')->exists($transaction->receipt_path)) {
            Storage::disk('public')->delete($transaction->receipt_path);
        }

        if ($transaction->internal_transfer) {
            if ($transaction->transaction_type === 'expense') {
                $from = BankAccount::where('user_id', $userId)->find($transaction->bank_account_id);
                if ($from) { $from->starting_balance += abs((float) $transaction->amount); $from->save(); }
                $pair = Transaction::where('user_id', $userId)->where('internal_transfer', true)->where('transaction_type', 'income')->whereDate('date', $transaction->date)->where('name', $transaction->name)->where('amount', $transaction->amount)->where('id', '!=', $transaction->id)->orderBy('id', 'desc')->first();
                if ($pair) { $to = BankAccount::where('user_id', $userId)->find($pair->bank_account_id); if ($to) { $to->starting_balance -= abs((float) $pair->amount); $to->save(); } $pair->delete(); }
            } else {
                $to = BankAccount::where('user_id', $userId)->find($transaction->bank_account_id);
                if ($to) { $to->starting_balance -= abs((float) $transaction->amount); $to->save(); }
                $pair = Transaction::where('user_id', $userId)->where('internal_transfer', true)->where('transaction_type', 'expense')->whereDate('date', $transaction->date)->where('name', $transaction->name)->where('amount', $transaction->amount)->where('id', '!=', $transaction->id)->orderBy('id', 'desc')->first();
                if ($pair) { $from = BankAccount::where('user_id', $userId)->find($pair->bank_account_id); if ($from) { $from->starting_balance += abs((float) $pair->amount); $from->save(); } $pair->delete(); }
            }
            $transaction->delete();
            return redirect()->route('transactions.index')->with('success', 'Transfer removed successfully.');
        }

        $bank = BankAccount::where('user_id', $userId)->find($transaction->bank_account_id);
        if ($bank) {
            $amt    = (float) $transaction->amount;
            $type   = $transaction->transaction_type;
            $effect = ($type === 'income') ? $amt : ($amt >= 0 ? -$amt : abs($amt));
            $bank->starting_balance -= $effect;
            $bank->save();
        }

        $transaction->delete();
        return redirect()->route('transactions.index')->with('success', 'Transaction removed successfully.');
    }

    // ---------------------------------------------------------------------------
    // UPLOAD RECEIPT
    // ---------------------------------------------------------------------------
    public function uploadReceipt(Request $request, string $id)
    {
        \Log::info('uploadReceipt DEBUG', [
            'id'             => $id,
            'method'         => $request->method(),
            'hasFile'        => $request->hasFile('receipt'),
            'files_global'   => !empty($_FILES) ? array_keys($_FILES) : 'empty',
        ]);

        $userId      = Auth::id();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);

        $request->validate([
            'receipt' => ['required', 'file', 'max:10240'],
        ]);

        if ($transaction->receipt_path && Storage::disk('public')->exists($transaction->receipt_path)) {
            Storage::disk('public')->delete($transaction->receipt_path);
        }

        $path = $request->file('receipt')->store('receipts/' . $userId, 'public');
        $transaction->update(['receipt_path' => $path]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'path' => $path]);
        }

        return redirect()->back()->with('success', 'Ricevuta caricata!');
    }

    // ---------------------------------------------------------------------------
    // DELETE RECEIPT
    // ---------------------------------------------------------------------------
    public function deleteReceipt(string $id)
    {
        $userId      = Auth::id();
        $transaction = Transaction::where('user_id', $userId)->findOrFail($id);

        if ($transaction->receipt_path && Storage::disk('public')->exists($transaction->receipt_path)) {
            Storage::disk('public')->delete($transaction->receipt_path);
        }

        $transaction->update(['receipt_path' => null]);
        return redirect()->back()->with('success', 'Ricevuta eliminata.');
    }
}
