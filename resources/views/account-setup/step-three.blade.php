@extends('layouts.customer')
@section('styles_in_head')
    {{-- Add your link below --}}
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header,
        aside.sidebar {
            display: none;
        }

        main.dashboardMain {
            padding-top: 2rem;
            width: 100%;
        }

        .setupStepsWrapper h1 {

    font-size: 32px;

}

@media (min-width: 1200px) {
    h1, .h1 {
        font-size: 2.25rem;
    }
}

        main.dashboardMain.full {
            padding-top: 2rem;
        }
        .setupStepsWrapper form .expensesWrap {
    background-color: #d1f9ff0d;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    padding: 1.8rem 3.5rem;
}

       @media only screen and (max-width: 767px) {
    main.dashboardMain {
        padding: 3rem;
    }
    .setupStepsWrapper h1 {

    font-size: 25px;
    line-height: 30px;


}
.setupStepsWrapper p {

    font-size: 0.9rem;

}
}
       @media only screen and (max-width:480px) {
    main.dashboardMain {
        padding: 2.5rem;
    }
    .setupStepsWrapper h1 {

    font-size: 22px;



}
.setupStepsWrapper p {

    font-size: 0.85rem;

}
}


    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Add your investments and pensions</div>
                            <div class="sep"></div>
                            <div class="item">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Select how much you want to spend on each category</h1>
                    <p>
                        Category budgets will help you track your day to day spending.
                    </p>
                </div>
            </div>
            @if ($errors->any())
                <section class="errorsBanner">
                    <div class="container">
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
            <div class="row ">
                <div class="col-12">
                    {{-- <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1"> --}}
                    <form action="{{ route('account-setup-step-three-store') }}" method="post">
                        @csrf

                        <div class="row">
                            <div class="col-12">
                                <h6 class="formSectionTitle">Salary</h6>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6">
                                        <input type="date" name="salary_date" id="salary_date"
                                            value="{{ old('salary_date') }}">
                                        <br>
                                        @error('salary_date')
                                            <div class="alert alert-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="input-group ms-auto mt-0" >
                                            <label for="salary_amount" class="input-group-text">£</label>
                                            <input type="number" class="form-control" name="salary_amount"
                                                placeholder="0.00" min="0" step="any"
                                                value="{{ old('salary_amount') }}">
                                        </div>
                                        @error('salary_amount')
                                            <div class="alert alert-danger">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h6 class="formSectionTitle">Expenses</h6>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center gy-md-2 gy-1 gx-5 ">
                                    @foreach ($defaultBudgetCategories as $dCat)
                                        @php
                                            $sanitizedName = strtolower(
                                                preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $dCat->name)),
                                            );
                                        @endphp

                                        <div class="col-lg-6 ">
                                            <div class="row align-items-center">

                                                <div class="col-md-8 col-6">
                                                    <label for="">{{ $dCat->name }}</label>
                                                </div>

                                                <div class="col-md-4 col-6 d-flex justify-content-end">
                                                    <div class="input-group">
                                                        <label for="{{ $sanitizedName }}_amount"
                                                            class="input-group-text">£</label>
                                                        <input type="number" class="form-control"
                                                            name="expense_{{ $sanitizedName }}_amount" placeholder="0.00"
                                                            min="0" step="any"
                                                            value="{{ old('expense_' . $sanitizedName . '_amount', $accSetup['expense_' . $sanitizedName . '_amount'] ?? '') }}">
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    @endforeach

                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <h6 class="formSectionTitle">Other</h6>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                @php
                                    $otherNames = old('other_name', $accSetup['other_name'] ?? []);
                                    $otherAmounts = old('other_amounts', $accSetup['other_amounts'] ?? []);
                                @endphp

                                @if (!empty($otherNames))
                                    @foreach ($otherNames as $index => $name)
                                        <div class="expenseItemInner">
                                            <div class="row align-items-center g-4 gx-5" id="otherExpenseItem">
                                                <div class="col-lg-6">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8 col-6">
                                                            <input type="text" name="other_name[]"
                                                                placeholder="Description..." style="width: 100%"
                                                                value="{{ $name }}">
                                                        </div>
                                                        <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                            <div class="input-group">
                                                                <label class="input-group-text">£</label>
                                                                <input type="number" class="form-control"
                                                                    name="other_amounts[]" placeholder="0.00"
                                                                    value="{{ $otherAmounts[$index] ?? '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="expenseItemInner">

                                        <div class="row align-items-center gy-md-2 gy-1 gx-5" id="otherExpenseItem">
                                            <div class="col-lg-6">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8 col-6">
                                                        <input type="text" name="other_name[]"
                                                            placeholder="Description..." style="width: 100%">
                                                    </div>
                                                    <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                        <div class="input-group mt-0" >
                                                            <label class="input-group-text">£</label>
                                                            <input type="number" class="form-control"
                                                                name="other_amounts[]" placeholder="0.00">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                @endif

                                <div class="row mt-2">
                                    <div class="col-12">
                                        <button type="button" class="add-expense"><i
                                                class="fa-solid fa-circle-plus"></i> Add another budget item</button>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="expensesWrap">

                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6">
                                        <label for="">Total</label>
                                    </div>
                                    <div class="col-md-4 col-6 d-flex justify-content-end">
                                        <p>
                                            £ <span class="totalAmount"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                @if ($accSetup['period_selection'] == 'first_day')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
                                @elseif($accSetup['period_selection'] == 'last_working')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
                                @elseif($accSetup['period_selection'] == 'fixed_date')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-two') }}">Back</a>
                                @endif

                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        $(document).ready(function() {
            function calculateTotal() {
                let total = 0;
                $('input[type="number"]').each(function() {
                    let name = $(this).attr('name');
                    if (name !== 'salary_amount') { // Exclude salary_amount
                        let value = parseFloat($(this).val());
                        if (!isNaN(value)) {
                            total += value;
                        }
                    }
                });
                $('.totalAmount').text(total.toFixed(2));
            }

            // Run calculation when any input changes
            $(document).on('input', 'input[type="number"]', calculateTotal);

            // Run once on page load in case there are pre-filled values
            calculateTotal();

            // Add new expense item dynamically
            $(document).on('click', '.addExpenseItem', function() {
                let newExpenseItem = `
                <div class="expenseItemInner">
                    <div class="row align-items-center">
                        <div class="col-md-8 col-6">
                            <input type="text" name="other_name[]" placeholder="Description..." style="width: 100%">
                        </div>
                        <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                            <div class="input-group">
                                <label class="input-group-text">£</label>
                                <input type="number" class="form-control" name="other-amounts[]" placeholder="0.00">
                            </div>
                        </div>
                    </div>
                </div>
            `;

                $('.expenseItem').append(newExpenseItem);
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $(".add-expense").click(function() {
                let newItem = `
            <div class="col-lg-6">
                <div class="row align-items-center">
                    <div class="col-md-8 col-6">
                        <input type="text" name="other_name[]" placeholder="Description..." style="width: 100%">
                    </div>
                    <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                        <div class="input-group" style="margin-top: 0;">
                            <label class="input-group-text">£</label>
                            <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00">
                        </div>
                    </div>
                </div>
            </div>`;

                $('#otherExpenseItem').append(newItem);
                // $(this).closest(".expenseItem").find(".expenseItemInner:last").after(newItem);
            });
        });
    </script>
@endsection
