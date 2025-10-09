@extends('layouts.customer')
@section('styles_in_head')
    {{-- Add your link below --}}
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
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

        main.dashboardMain.full {
            padding-top: 2rem;
        }

        .setupStepsWrapper form .expensesWrap .expenseItem {
            border-bottom: 1px solid rgba(255, 255, 255, .05);
            padding: 1rem 0;
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
                            <div class="box active "></div>
                            <div class="box active"></div>
                            <div class="box "></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row ">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Confirm your budget</h1>
                    <p>
                        Please check the details of your budget are correct. You can change this at any time.
                    </p>
                </div>
            </div>
            <div class="row mt-md-4 mt-0">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <form action="{{ route('account-setup-step-four-store') }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-12">
                                <h6 class="formSectionTitle">Expenses</h6>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">

                                <div class="row align-items-center gy-md-2 gy-1 gx-5">
                                    @foreach (['mortgage', 'rent', 'utilities', 'groceries', 'loans', 'credit_card', 'transport', 'insurance', 'eating_out', 'entertainment', 'home__family', 'shopping', 'gifts', 'education', 'charity', 'other'] as $expense)
                                        <div class="col-lg-6 ">
                                            <div class="row align-items-center">
                                                <div class="col-8">
                                                    <label
                                                        for="">{{ ucfirst(str_replace('_', ' ', $expense)) }}</label>
                                                </div>
                                                <div class="col-4 d-flex justify-content-end">
                                                    <span class="confirmAmount">£
                                                        {{ $accSetup['expense_' . $expense . '_amount'] ?? 0.0 }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        {{-- <div class="row">
                            <div class="col-12">
                                <h6 class="formSectionTitle">Savings</h6>
                            </div>
                        </div>
                        <div class="expensesWrap">

                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <label for="">Investments</label>
                                    </div>
                                    <div class="col-4 d-flex justify-content-end">
                                        <span class="confirmAmount">£{{ $accSetup['savings_investments_amount'] ?? 0.00 }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <label for="">Pension</label>
                                    </div>
                                    <div class="col-4 d-flex justify-content-end">
                                        <span class="confirmAmount">£{{ $accSetup['savings_pension_amount'] ?? 0.00 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div> --}}
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
                                        <div class="expenseItem">
                                            <div class="row align-items-center">
                                                <div class="col-md-8 col-6">
                                                    <label for="">{{ $name }}</label>

                                                </div>
                                                <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                    <span class="confirmAmount">£{{ $otherAmounts[$index] ?? '' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="expenseItemInner">
                                        <div class="row align-items-center">
                                            <div class="col-md-8 col-6">
                                                <input type="text" name="other_name[]" placeholder="Description..."
                                                    style="width: 100%">
                                            </div>
                                            <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                <div class="input-group">
                                                    <label class="input-group-text">£</label>
                                                    <input type="number" class="form-control" name="other_amounts[]"
                                                        placeholder="0.00">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row mt-2">
                                    <div class="col-12">
                                        <button type="button" class="add-expense"><i class="fa-solid fa-circle-plus"></i>
                                            Add another budget item</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-4" style="margin-bottom: 1rem;">
                            <div class="col-12 text-center">
                                <a href="{{ route('account-setup.step-three') }}" class="editBudgetItemButton">
                                    <i class="fas fa-pencil"></i> Edit budget
                                </a>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-8">
                                        <label for="">Total Budget</label>
                                    </div>
                                    <div class="col-4 d-flex justify-content-end">
                                        <span class="confirmAmount">£{{ $totalAmount }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-7 col-4">
                                        <label for="">Period</label>
                                    </div>
                                    <div class="col-md-5 d-flex justify-content-end col-8">
                                        <span class="confirmAmount">
                                            @if ($accSetup['period_selection'] == 'first_day')
                                                First to last day of the month
                                            @elseif($accSetup['period_selection'] == 'last_working')
                                                Last working day of the month
                                            @elseif($accSetup['period_selection'] == 'fixed_date')
                                                Fixed monthly date
                                            @endif

                                            <a href="{{ route('account-setup.step-one') }}" class="editBtn">
                                                <i class="fas fa-pencil"></i>
                                            </a>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-three') }}">Back</a>
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
@endsection
