@extends('layouts.customer')

@section('content')
    @php
        // Fallback: se $categories non è stato passato dal controller, recuperalo qui
        $categories = $categories ?? \App\Models\Budget::where('user_id', auth()->id())->get();
    @endphp

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Transactions</h1>
                </div>
            </div>
        </div>
    </section>

    @if ($errors->any())
        <section class="formErrorsWrap">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="addTransactionFilterBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="btn-group">
                        <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal"
                                data-bs-target="#addTransactionModal">
                            Add Transaction
                        </button>

                        <div class="dropdown">
                            <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-filter"></i>
                            </a>
                            <div class="dropdown-menu p-3" style="min-width:260px">
                                <h6 class="mb-2">Accounts</h6>
                                <div class="bankItemsWrapper">
                                    <div class="item mb-2">
                                        <a href="{{ route('transactions.index') }}"
                                           class="d-block px-3 py-2 rounded {{ Route::is('transactions.index') ? 'bg-dark text-white fw-bold' : 'bg-secondary text-white' }}"
                                           style="text-decoration:none;">
                                            <div class="square d-inline-block me-2"
                                                 style="width: 10px; height: 10px; background-color: white;"></div>
                                            All Accounts
                                        </a>
                                    </div>
                                    @foreach ($bankAccounts as $account)
                                        @php
                                            $slug = strtolower(str_replace(' ', '-', $account->account_name));
                                            $isActive = request()->routeIs('transactions.filter-by-bank') && request()->route('bank') === $slug;
                                        @endphp
                                        <div class="item mb-2">
                                            <a href="{{ route('transactions.filter-by-bank', $slug) }}"
                                               class="d-block px-3 py-2 rounded {{ $isActive ? 'bg-dark text-white fw-bold' : 'bg-secondary text-white' }}"
                                               style="text-decoration: none;">
                                                <div class="square d-inline-block me-2"
                                                     style="width: 10px; height: 10px; background-color: white;"></div>
                                                {{ $account->account_name }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- KPI boxes (facoltativi)
                <div class="col-12 mt-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="card text-white h-100 shadow-sm p-3 rounded" style="background-color:#d1f9ff0d">
                                <h5 class="mb-1">Total Transactions Today</h5>
                                <p class="fs-4 m-0">£{{ number_format($totalTransactionAmountToday, 2) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card  text-white h-100 shadow-sm p-3 rounded" style="background-color:#dc354575">
                                <h5 class="mb-1">Total Expenses Today</h5>
                                <p class="fs-4 m-0">£{{ number_format($totalExpenseAmountToday, 2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                --}}
            </div>
        </div>
    </section>

    @if ($transactions->isNotEmpty())
        @foreach ($groupedTransactions as $date => $transactionsOnDate)
            <section class="transactionGroup">
                <div class="transaction-date-header d-flex align-items-center justify-content-between">
                    <h4 class="m-0">{{ \Carbon\Carbon::parse($transactionsOnDate->first()->date)->format('F j, Y') }}</h4>
                    <h4 class="m-0 total-expense">Total Expense: £{{ number_format($dailyExpenses[$date] ?? 0, 2) }}</h4>
                </div>

                <div class="transactionList">
                    @foreach ($transactionsOnDate as $transaction)
                        @php
                            $modalId = \Illuminate\Support\Str::slug($transaction->name.'-'.$transaction->id, '_');
                        @endphp

                        {{-- CASE: Internal transfer --}}
                        @if (!!$transaction->internal_transfer)
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5 text-end">
                                            <div class="amount">
                                                <span style="color:#ffc107;">
                                                    £{{ number_format($transaction->amount, 2) }}
                                                </span>
                                            </div>
                                            <div class="accountType">Fund Transfer</div>
                                        </div>
                                    </div>
                                </button>

                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <h1 class="mb-3">Fund Transfer: {{ $transaction->name }}</h1>

                                                <div class="alert alert-warning">
                                                    Editing internal transfers is not supported. Please delete and recreate the transfer.
                                                </div>

                                                <div class="row mt-3">
                                                    <div class="col-md-6">
                                                        <form action="{{ route('transactions.destroy', $transaction->id) }}" method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="dangerBtn">Delete</button>
                                                        </form>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- CASE: Normal transaction --}}
                        @else
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5 text-end">
                                            <div class="amount">
                                                @if ($transaction->transaction_type === 'expense')
                                                    <span style="color:#fff;">-£{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span style="color:#44E0AC;">+£{{ number_format($transaction->amount, 2) }}</span>
                                                @endif
                                            </div>
                                            <div class="accountType">Bank Account</div>
                                        </div>
                                    </div>
                                </button>

                                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <h1 class="mb-3">Edit {{ $transaction->name }} transaction</h1>

                                                <form action="{{ route('transactions.update', $transaction->id) }}" method="post">
                                                    @csrf
                                                    @method('put')

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="name_{{ $transaction->id }}">Name of Business/Person</label>
                                                            <input type="text" name="name" id="name_{{ $transaction->id }}"
                                                                   value="{{ old('name', $transaction->name) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="date_{{ $transaction->id }}">Date</label>
                                                            <input type="date" name="date" id="date_{{ $transaction->id }}"
                                                                   value="{{ old('date', $transaction->date) }}">
                                                        </div>
                                                    </div>

                                                    @php
                                                        // Prova a selezionare il BUDGET (budgets.id) che mappa la categoria attuale della transazione
                                                        $selectedBudgetId = optional($categories->firstWhere('category_id', $transaction->category_id))->id
                                                            ?? optional($categories->firstWhere('category_name', $transaction->category_name))->id
                                                            ?? null;
                                                    @endphp
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="category_{{ $transaction->id }}">Category</label>
                                                            <select name="category" id="category_{{ $transaction->id }}">
                                                                @foreach ($categories as $cat)
                                                                    <option value="{{ $cat->id }}"
                                                                            {{ (int)$selectedBudgetId === (int)$cat->id ? 'selected' : '' }}
                                                                            style="text-transform: capitalize !important;">
                                                                        {{ str_replace('_', ' ', $cat->category_name) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="bank_account_id_{{ $transaction->id }}">Bank Account</label>
                                                            <select name="bank_account_id" id="bank_account_id_{{ $transaction->id }}">
                                                                @foreach ($bankAccounts as $account)
                                                                    <option value="{{ $account->id }}"
                                                                        {{ (int)$account->id === (int)$transaction->bank_account_id ? 'selected' : '' }}>
                                                                        {{ str_replace('_', ' ', $account->account_name) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <style>
                                                        select {
                                                            background-color: #1D373B !important;
                                                            color: #ffffff !important;
                                                            border: 1px solid #444 !important;
                                                            border-radius: 6px;
                                                            padding: 10px;
                                                        }
                                                        select option {
                                                            background-color: #1D373B !important;
                                                            color: #ffffff !important;
                                                            text-transform: capitalize !important;
                                                        }
                                                        label { color: #ffffff !important; }
                                                    </style>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="amount_{{ $transaction->id }}">Amount</label>
                                                            <input type="number" name="amount" id="amount_{{ $transaction->id }}" step="any"
                                                                   value="{{ old('amount', $transaction->amount) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label>Transaction Type</label>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                       name="transaction_type"
                                                                       id="expense_{{ $transaction->id }}"
                                                                       value="expense"
                                                                       {{ $transaction->transaction_type === 'expense' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="expense_{{ $transaction->id }}">Expense</label>
                                                            </div>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                       name="transaction_type"
                                                                       id="income_{{ $transaction->id }}"
                                                                       value="income"
                                                                       {{ $transaction->transaction_type === 'income' ? 'checked' : '' }} required>
                                                                <label class="form-check-label" for="income_{{ $transaction->id }}">Income</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="input-group">
                                                                <input type="checkbox" name="internal_transfer"
                                                                       id="internal_transfer_{{ $transaction->id }}"
                                                                    {{ $transaction->internal_transfer ? 'checked' : '' }}>
                                                                <label for="internal_transfer_{{ $transaction->id }}">Internal Transfer</label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <button type="submit" class="twoToneBlueGreenBtn">
                                                                Update Transaction
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>

                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <form action="{{ route('transactions.destroy', $transaction->id) }}" method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="dangerBtn">Delete</button>
                                                        </form>
                                                    </div>
                                                    <div class="col-md-6 text-end">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    @else
        <section class="transactionsMainBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="inner noTransactions text-center">
                            <i class="fa-solid fa-right-left"></i>
                            <h2>No transactions added yet</h2>
                            <p>
                                Add transactions from your bank accounts to see how your spending aligns with your budget.
                            </p>

                            <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal"
                                    data-bs-target="#addTransactionModal">
                                Add Transaction
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ADD TRANSACTION MODAL --}}
    <div class="modal fade" id="addTransactionModal" tabindex="-1" aria-labelledby="addTransactionModal"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="margin-top: 170px">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Add Transaction</h1>
                    <form action="{{ route('transactions.store') }}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <label for="name_create">Name of Business/Person *</label>
                                <input type="text" name="name" id="name_create" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="date_create">Date *</label>
                                <input type="date" name="date" id="date_create" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="category_create">Category *</label>
                                <select name="category" id="category_create" required>
                                    <option value="" disabled selected>Select a category...</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}" style="text-transform: capitalize !important;">
                                            {{ str_replace('_', ' ', $cat->category_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account_id_create">Bank Account *</label>
                                <select name="bank_account_id" id="bank_account_id_create" required>
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ str_replace('_', ' ', $account->account_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="amount_create">Amount *</label>
                                <input type="number" name="amount" id="amount_create" step="any" min="0.01" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label>Transaction Type *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type"
                                           id="expense_create" value="expense" required>
                                    <label class="form-check-label" for="expense_create">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type"
                                           id="income_create" value="income" required>
                                    <label class="form-check-label" for="income_create">Income</label>
                                </div>
                            </div>
                        </div>

                        {{-- Internal transfer (se vuoi abilitarlo alla creazione)
                        <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="internal_transfer_create">
                                    <label for="internal_transfer_create">Internal Transfer</label>
                                </div>
                            </div>
                        </div>
                        --}}

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Add Transaction</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
