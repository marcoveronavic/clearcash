@extends('layouts.customer')
@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Recurring payments</h1>
                </div>
            </div>
        </div>
    </section>

    @if($errors->any())
        <section class="formErrorsWrap">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    @endif


    @if($recurringPayments->isNotEmpty())
        <section class="addBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="btn-group">
                            <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal" data-bs-target="#addRecurringPaymentModal">
                                Add Recurring Payment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if($recurringPayments->isNotEmpty())
        <section class="recurringPaymentsWrapper">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="paymentList">
                            @foreach($recurringPayments as $payment)
                                <div class="paymentItem">
                                    <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#rpm-{{ $payment->id }}">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <h4>{{ $payment->name }}</h4>
                                                <span class="nextDueDate">Next Due Date: </span>
                                            </div>
                                            <div class="col-md-4">
                                                <h4>
                                                    @if($payment->category)
                                                        {{ $payment->category->category_name }}
                                                    @else
                                                        --
                                                    @endif
                                                </h4>
                                                <span class="accountDetails">{{ $payment->bankAccount->account_name }}</span>
                                            </div>
                                            <div class="col-md-4">
                                                @if($payment->transaction_type == 'expense')
                                                    <h4 style="color: #fff;">-£{{ number_format($payment->amount, 2) }}</h4>
                                                @else
                                                    <h4 style="color: #44E0AC">+£{{ number_format($payment->amount, 2) }}</h4>
                                                @endif
                                                <span class="every">Every
                                                    @if($payment->repeat == 'weekly')
                                                        Week
                                                    @elseif($payment->repeat == 'fortnightly')
                                                        Two Weeks
                                                    @elseif($payment->repeat == 'monthly')
                                                        Month
                                                    @elseif($payment->repeat == 'semi_annually')
                                                        6 Months
                                                    @else
                                                        12 Months
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </button>
                                    <div class="modal fade" id="rpm-{{ $payment->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <h1>Edit Recurring Payment</h1>
                                                    <form action="{{ route('recurring-payments.update', $payment->id) }}" method="post">
                                                        @csrf
                                                        @method('put')
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="name">Name of business/person</label>
                                                                <input type="text" name="name" id="name" value="{{ old('name', $payment->name) }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="date">Date</label>
                                                                <input type="date" name="date" id="date" value="{{ old('date', $payment->start_date) }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="repeat">Repeat</label>
                                                                <select name="repeat" id="repeat">
                                                                    <option value="weekly" @if($payment->repeat == 'weekly') selected @endif>Weekly</option>
                                                                    <option value="fortnightly" @if($payment->repeat == 'fortnightly') selected @endif>Fortnightly</option>
                                                                    <option value="monthly" @if($payment->repeat == 'monthly') selected @endif>Monthly</option>
                                                                    <option value="semi_annually" @if($payment->repeat == 'semi_annually') selected @endif>Semi-annually</option>
                                                                    <option value="annually" @if($payment->repeat == 'annually') selected @endif>Annually</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="category">Category</label>
                                                                <select name="category" id="category">
                                                                    @foreach($categories as $cat)
                                                                        <option value="{{ $cat->id }}" @if($cat->id == $payment->category_id) selected @endif>{{ str_replace('_', ' ', $cat->category_name) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="bank_account">Bank Account</label>
                                                                <select name="bank_account" id="bank_account">

                                                                    @foreach($bankAccounts as $bank)
                                                                        <option value="{{ $bank->id }}" @if( $bank->id == $payment->bank_account_id) selected @endif>{{ $bank->account_name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="amount">Amount</label>
                                                                <input type="number" name="amount" id="amount" step="any" value="{{ old('amount', $payment->amount) }}">
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="">Transaction Type</label>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="expense" value="expense" required @if($payment->transaction_type == 'expense') checked @endif>
                                                                    <label class="form-check-label" for="expense">Expense</label>
                                                                </div>

                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="income" value="income" @if($payment->transaction_type == 'income') checked @endif required>
                                                                    <label class="form-check-label" for="income">Income</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <input type="checkbox" name="internal_transfer" id="internal_transfer" @if($payment->internal_transfer == 1) checked @endif>
                                                                    <label for="internal_transfer">Internal Transfer</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <button type="submit" class="twoToneBlueGreenBtn">Update Recurring Payment</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="noAccountDetailsNotice text-center">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <h2>No recurring payments added yet</h2>
                        <p>
                            Save time by adding recurring payments that automatically get added to your transactions at a frequency of your choosing.
                        </p>
                        <button type="button" class="twoToneBlueGreenBtn" data-bs-toggle="modal" data-bs-target="#addRecurringPaymentModal">
                            Add Recurring Payment
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif


    <div class="modal fade" id="addRecurringPaymentModal" tabindex="-1" aria-labelledby="addRecurringPaymentModal" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h1>Add recurring payment</h1>
                    <form action="{{ route('recurring-payments.store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <label for="name">Name of business/person</label>
                                <input type="text" name="name" id="name">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="date">Date</label>
                                <input type="date" name="date" id="date">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="repeat">Repeat</label>
                                <select name="repeat" id="repeat">
                                    <option value="" selected disabled>Select an option...</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="fortnightly">Fortnightly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="semi_annually">Semi-annually</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="category">Category</label>
                                <select name="category" id="category">
                                    <option value="" selected disabled>Select an option...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ str_replace('_', ' ', $cat->category_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="bank_account">Bank Account</label>
                                <select name="bank_account" id="bank_account">
                                    <option value="" selected disabled>Select an option...</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="amount">Amount</label>
                                <input type="number" name="amount" id="amount" step="any">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <label for="">Transaction Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="expense" value="expense" required >
                                    <label class="form-check-label" for="expense">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="income" value="income"  required>
                                    <label class="form-check-label" for="income">Income</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="internal_transfer">
                                    <label for="internal_transfer">Internal Transfer</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Add Recurring Payment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
