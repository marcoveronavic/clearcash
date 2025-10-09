@extends('layouts.customer')
@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Create Transaction</h1>
                </div>
            </div>
        </div>
    </section>

    <section class="createTransactionsMainWrap">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <form action="{{ route('transactions.store') }}" method="post">
                        @csrf
                        <div class="row lg:g-4 g-3 ">
                            <div class="col-12 col-lg-6">
                                <label for="name">Name of Business/Person</label>
                                <input type="text" name="name" id="name">
                            </div>

                            <div class="col-12 col-lg-6">
                                <label for="date">Date</label>
                                <input type="date" name="date" id="date">
                            </div>

                            <div class="col-12 col-lg-6">
                                <label for="category">Category</label>
                                <select name="category" id="category">
                                    <option value="" disabled selected>Select a category...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ str_replace('_', ' ',$cat->category_name) }}</option>
                                        {{-- <option value="{{ $cat->id }}">{{ str_replace('_', ' ',$cat->category->name) }}</option> --}}
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-lg-6">
                                <label for="bank_account">Bank Account</label>
                                <select name="bank_account" id="">
                                    <option value="" disabled selected>Select a bank account...</option>
                                    @foreach($bankAccounts as $account)
                                        <option value="{{ $account->id }}">{{ str_replace('_', ' ', $account->account_name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12 col-lg-6">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" step="any">
                            </div>

                            <div class="col-12 col-lg-6">
                                <label for="">Transaction Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="expense" value="expense" required>
                                    <label class="form-check-label" for="expense">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="income" value="income" required>
                                    <label class="form-check-label" for="income">Income</label>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="internal_transfer">
                                    <label for="internal_transfer">Internal Transfer</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Add Transaction</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection





