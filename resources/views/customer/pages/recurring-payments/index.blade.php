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
                                @php $modalId = 'rpm-'.$payment->id; @endphp
                                <div class="paymentItem">
                                    <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
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
                                                    <h4 style="color:#fff;">-£{{ number_format($payment->amount, 2) }}</h4>
                                                @else
                                                    <h4 style="color:#44E0AC">+£{{ number_format($payment->amount, 2) }}</h4>
                                                @endif
                                                <span class="every">
                                                    @if($payment->repeat == 'weekly')
                                                        Every Week
                                                    @elseif($payment->repeat == 'fortnightly')
                                                        Every Two Weeks
                                                    @elseif($payment->repeat == 'monthly')
                                                        Every Month
                                                    @elseif($payment->repeat == 'semi_annually')
                                                        Every 6 Months
                                                    @else
                                                        Every 12 Months
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </button>

                                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
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
                                                                <label for="name_{{ $payment->id }}">Name of business/person</label>
                                                                <input type="text" name="name" id="name_{{ $payment->id }}" value="{{ old('name', $payment->name) }}">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="date_{{ $payment->id }}">Date</label>
                                                                <input type="date" name="date" id="date_{{ $payment->id }}" value="{{ old('date', $payment->start_date) }}">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="repeat_{{ $payment->id }}">Repeat</label>
                                                                <select name="repeat" id="repeat_{{ $payment->id }}">
                                                                    <option value="weekly"        @selected($payment->repeat == 'weekly')>Weekly</option>
                                                                    <option value="fortnightly"   @selected($payment->repeat == 'fortnightly')>Fortnightly</option>
                                                                    <option value="monthly"       @selected($payment->repeat == 'monthly')>Monthly</option>
                                                                    <option value="semi_annually" @selected($payment->repeat == 'semi_annually')>Semi-annually</option>
                                                                    <option value="annually"      @selected($payment->repeat == 'annually')>Annually</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="category_{{ $payment->id }}">Category</label>
                                                                <select name="category" id="category_{{ $payment->id }}">
                                                                    @foreach($categories as $cat)
                                                                        <option value="{{ $cat->id }}" @selected($cat->id == $payment->category_id)>
                                                                            {{ str_replace('_', ' ', $cat->category_name) }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="bank_account_{{ $payment->id }}">Bank Account</label>
                                                                <select name="bank_account" id="bank_account_{{ $payment->id }}">
                                                                    @foreach($bankAccounts as $bank)
                                                                        <option value="{{ $bank->id }}" @selected($bank->id == $payment->bank_account_id)>
                                                                            {{ $bank->account_name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label for="amount_{{ $payment->id }}">Amount</label>
                                                                <input type="number" name="amount" id="amount_{{ $payment->id }}" step="any" value="{{ old('amount', $payment->amount) }}">
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <label>Transaction Type</label>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="expense_{{ $payment->id }}" value="expense" @checked($payment->transaction_type == 'expense') required>
                                                                    <label class="form-check-label" for="expense_{{ $payment->id }}">Expense</label>
                                                                </div>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="transaction_type" id="income_{{ $payment->id }}" value="income" @checked($payment->transaction_type == 'income') required>
                                                                    <label class="form-check-label" for="income_{{ $payment->id }}">Income</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <div class="input-group">
                                                                    <input type="checkbox" name="internal_transfer" id="internal_transfer_{{ $payment->id }}" @checked($payment->internal_transfer == 1)>
                                                                    <label for="internal_transfer_{{ $payment->id }}">Internal Transfer</label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="row">
                                                            <div class="col-12">
                                                                <button type="submit" class="twoToneBlueGreenBtn">Update Recurring Payment</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div> <!-- /.modal-body -->
                                            </div>
                                        </div>
                                    </div>
                                </div> <!-- /.paymentItem -->
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
                        <p>Save time by adding recurring payments that automatically get added to your transactions at a frequency of your choosing.</p>
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
                                <label for="rp_name">Name of business/person</label>
                                <input type="text" name="name" id="rp_name" value="{{ old('name') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="rp_date">Date</label>
                                <input type="date" name="date" id="rp_date" value="{{ old('date') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="rp_repeat">Repeat</label>
                                <select name="repeat" id="rp_repeat">
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
                                <label for="rp_category">Category</label>
                                <select name="category" id="rp_category">
                                    <option value="" selected disabled>Select an option...</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ str_replace('_', ' ', $cat->category_name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="rp_bank_account">Bank Account</label>
                                <select name="bank_account" id="rp_bank_account">
                                    <option value="" selected disabled>Select an option...</option>
                                    @foreach($bankAccounts as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label for="rp_amount">Amount</label>
                                <input type="number" name="amount" id="rp_amount" step="any" value="{{ old('amount') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <label>Transaction Type</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="rp_expense" value="expense" required >
                                    <label class="form-check-label" for="rp_expense">Expense</label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="transaction_type" id="rp_income" value="income" required>
                                    <label class="form-check-label" for="rp_income">Income</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="input-group">
                                    <input type="checkbox" name="internal_transfer" id="rp_internal_transfer" {{ old('internal_transfer') ? 'checked' : '' }}>
                                    <label for="rp_internal_transfer">Internal Transfer</label>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">Add Recurring Payment</button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.modal-body -->
            </div>
        </div>
    </div>
@endsection
