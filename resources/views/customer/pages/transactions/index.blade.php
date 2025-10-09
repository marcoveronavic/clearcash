@extends('layouts.customer')
@section('content')
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
                            <a class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i
                                    class="fa-solid fa-filter"></i></a>
                            <div class="dropdown-menu">
                                <h6>Accounts</h6>
                                <div class="bankItemsWrapper">
                                    <div class="item">
                                        <a href="{{ route('transactions.index') }}"
                                            class="{{ Route::is('transactions.index') ? 'active' : '' }}">
                                            <div class="square"></div>
                                            All Accounts
                                        </a>
                                    </div>
                                    @foreach ($bankAccounts as $account)
                                        @php
                                            $slug = strtolower(str_replace(' ', '-', $account->account_name));
                                            $isActive =
                                                request()->routeIs('transactions.filter-by-bank') &&
                                                request()->route('bank') === $slug;
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
{{-- 
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
                </div> --}}
            </div>
        </div>
    </section>

    @if ($transactions->isNotEmpty())
        @foreach ($groupedTransactions as $date => $transactionsOnDate)
            <section class="transactionGroup">
                <div class="transaction-date-header">

                    <h4 class="total-expense-date">{{ \Carbon\Carbon::parse($transactionsOnDate->first()->date)->format('F j, Y') }}</h4>
                    {{-- {{ \Carbon\Carbon::parse($date)->format('F j, Y') }} --}}
                    {{-- <span class="badge bg-danger ms-2"> --}}
                       <h4 class="total-expense">Total Expense: £{{ number_format($dailyExpenses[$date], 2) }}</h4> 
                    {{-- </span> --}}

                </div>

                <div class="transactionList">
                    @foreach ($transactionsOnDate as $transaction)
                        @if ($transaction->transaction_type == 'fundtransfer')
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#{{ str_replace(' ', '_', $transaction->name) }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5" style="text-align: right">
                                            <div class="amount">
                                                <span style="color: #ffc107;">
                                                    £{{ number_format($transaction->amount, 2) }}</span>
                                            </div>
                                            <div class="accountType">Fund Transfer</div>
                                        </div>
                                    </div>
                                </button>

                                <div class="modal fade" id="{{ str_replace(' ', '_', $transaction->name) }}" tabindex="-1"
                                    aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <h1>Edit {{ $transaction->name }} Fund Transfer</h1>
                                                <form action="{{ route('transactions.update', $transaction->id) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('put')

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="name">Name</label>
                                                            <input type="text" name="name"
                                                                value="{{ old('name', $transaction->name) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="date">Date</label>
                                                            <input type="date" name="date"
                                                                value="{{ old('date', $transaction->date) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="from_account">From Account</label>
                                                            <select name="from_account" required>
                                                                @foreach ($bankAccounts as $account)
                                                                    <option value="{{ $account->id }}"
                                                                        @if ($account->id == $transaction->from_account) selected @endif>
                                                                        {{ $account->account_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="to_account">To Account</label>
                                                            <select name="to_account" required>
                                                                @foreach ($bankAccounts as $account)
                                                                    <option value="{{ $account->id }}"
                                                                        @if ($account->id == $transaction->to_account) selected @endif>
                                                                        {{ $account->account_name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="amount">Amount</label>
                                                            <input type="number" name="amount" step="any"
                                                                value="{{ old('amount', $transaction->amount) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <button type="submit" class="twoToneBlueGreenBtn">Update
                                                                Transfer</button>
                                                        </div>
                                                    </div>
                                                </form>

                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <form
                                                            action="{{ route('transactions.destroy', $transaction->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="dangerBtn">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="transaction">
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                    data-bs-target="#{{ str_replace(' ', '_', $transaction->name) }}">
                                    <div class="row">
                                        <div class="col-md-8 col-7">
                                            <h5>{{ $transaction->name }}</h5>
                                            <h6>{{ str_replace('_', ' ', $transaction->category_name) }}</h6>
                                        </div>
                                        <div class="col-md-4 col-5" style="text-align: right">
                                            <div class="amount">
                                                @if ($transaction->transaction_type == 'expense')
                                                    <span
                                                        style="color: #fff;">-£{{ number_format($transaction->amount, 2) }}</span>
                                                @else
                                                    <span
                                                        style="color: #44E0AC">+£{{ number_format($transaction->amount, 2) }}</span>
                                                @endif
                                            </div>
                                            <div class="accountType">Bank Account</div>
                                        </div>
                                    </div>
                                </button>
                                <div class="modal fade" id="{{ str_replace(' ', '_', $transaction->name) }}"
                                    tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content" style="margin-top:168px">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                    aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <h1>Edit {{ $transaction->name }} transaction</h1>
                                                <form action="{{ route('transactions.update', $transaction->id) }}"
                                                    method="post">
                                                    @csrf
                                                    @method('put')
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="name">Name of Business/Person</label>
                                                            <input type="text" name="name" id="name"
                                                                value="{{ old('name', $transaction->name) }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="date">Date</label>
                                                            <input type="date" name="date" id="date"
                                                                value="{{ old('date', $transaction->date) }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="category">Category</label>
                                                            <select name="category" id="category">
                                                                @foreach ($categories as $cat)
                                                                    <option value="{{ $cat->id }}"
                                                                        @if ($cat->id == $transaction->category_id) selected @endif
                                                                        style="text-transform: capitalize !important;">
                                                                        {{ str_replace('_', ' ', $cat->category_name) }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="bank_account">Bank Account</label>
                                                            <select name="bank_account" id="">

                                                                @foreach ($bankAccounts as $account)
                                                                    <option value="{{ $account->id }}"
                                                                        @if ($account->id == $transaction->bank_account_id) selected @endif>
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

                                                        label {
                                                            color: #ffffff !important;
                                                        }
                                                    </style>

                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="amount">Amount</label>
                                                            <input type="number" name="amount" id="amount"
                                                                step="any"
                                                                value="{{ old('amount', $transaction->amount) }}">
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <label for="">Transaction Type</label>
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="transaction_type" id="expense"
                                                                    value="expense" required
                                                                    @if ($transaction->transaction_type == 'expense') checked @endif>
                                                                <label class="form-check-label"
                                                                    for="expense">Expense</label>
                                                            </div>

                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="radio"
                                                                    name="transaction_type" id="income" value="income"
                                                                    @if ($transaction->transaction_type == 'income') checked @endif
                                                                    required>
                                                                <label class="form-check-label"
                                                                    for="income">Income</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <div class="input-group">
                                                                <input type="checkbox" name="internal_transfer"
                                                                    id="internal_transfer"
                                                                    @if ($transaction->internal_transfer == 1) checked @endif>
                                                                <label for="internal_transfer">Internal Transfer</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-12">
                                                            <button type="submit" class="twoToneBlueGreenBtn">Update
                                                                Transaction</button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <div class="row mt-2">
                                                    <div class="col-md-6">
                                                        <form
                                                            action="{{ route('transactions.destroy', $transaction->id) }}"
                                                            method="post">
                                                            @csrf
                                                            @method('delete')
                                                            <button type="submit" class="dangerBtn">Delete</button>
                                                        </form>
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
                                <label for="name">Name of Business/Person *</label>
                                <input type="text" name="name" id="name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="date">Date *</label>
                                <input type="date" name="date" id="date">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="category">Category *</label>
                                <select name="category" id="category">
                                    <option value="" disabled selected>Select a category...</option>
                                    @foreach ($categories as $cat)
                                        <option value="{{ $cat->id }}"
                                            style="text-transform: capitalize !important;">
                                            {{ str_replace('_', ' ', $cat->category_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account">Bank Account *</label>
                                <select name="bank_account" id="">
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach ($bankAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="amount">Amount *</label>
                                <input type="number" name="amount" id="amount" step="any">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">Transaction Type *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type"
                                        id="expense" value="expense" required>
                                    <label class="form-check-label" for="expense">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type"
                                        id="income" value="income" required>
                                    <label class="form-check-label" for="income">Income</label>
                                </div>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="internal_transfer">
                                    <label for="internal_transfer">Internal Transfer</label>
                                </div>
                            </div>
                        </div> --}}
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
