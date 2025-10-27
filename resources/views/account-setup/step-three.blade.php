@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        header, aside.sidebar { display:none; }
        main.dashboardMain { padding-top:2rem; width:100%; }
        main.dashboardMain.full { padding-top:2rem; }

        .setupStepsWrapper h1 { font-size:32px; }
        @media (min-width: 1200px) { h1,.h1 { font-size:2.25rem; } }

        .setupStepsWrapper form .expensesWrap{
            background-color:#d1f9ff0d;border-radius:8px;margin-bottom:1.5rem;padding:1.8rem 3.5rem;
        }
        @media only screen and (max-width: 767px){
            main.dashboardMain{ padding:3rem; }
            .setupStepsWrapper h1{ font-size:25px; line-height:30px; }
            .setupStepsWrapper p{ font-size:.9rem; }
        }
        @media only screen and (max-width:480px){
            main.dashboardMain{ padding:2.5rem; }
            .setupStepsWrapper h1{ font-size:22px; }
            .setupStepsWrapper p{ font-size:.85rem; }
        }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">

            {{-- Stepper --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div><div class="sep"></div>
                            <div class="item">Add bank accounts</div><div class="sep"></div>
                            <div class="item">Add your investments and pensions</div><div class="sep"></div>
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

            {{-- Title --}}
            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Select how much you want to spend on each category</h1>
                    <p>Category budgets will help you track your day to day spending.</p>
                </div>
            </div>

            @if ($errors->any())
                <section class="errorsBanner">
                    <div class="container">
                        <div class="row"><div class="col-12">
                                <ul>
                                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                                </ul>
                            </div></div>
                    </div>
                </section>
            @endif

            <div class="row">
                <div class="col-12">
                    <form action="{{ url('/account-setup-step-three-store') }}" method="post">
                        @csrf

                        {{-- Salary --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle">Salary</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6">
                                        <input type="date" name="salary_date" id="salary_date"
                                               value="{{ old('salary_date', $accSetup['salary_date'] ?? '') }}">
                                        @error('salary_date') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="input-group ms-auto mt-0">
                                            <label for="salary_amount" class="input-group-text">£</label>
                                            <input type="number" class="form-control" name="salary_amount" id="salary_amount"
                                                   placeholder="0.00" min="0" step="any"
                                                   value="{{ old('salary_amount', $accSetup['salary_amount'] ?? '') }}">
                                        </div>
                                        @error('salary_amount') <div class="alert alert-danger mt-2">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Expenses --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle">Expenses</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center gy-md-2 gy-1 gx-5">
                                    @foreach ($defaultBudgetCategories as $dCat)
                                        @php
                                            $slug = $dCat->slug ?? strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $dCat->name)));
                                            $name = "expense_{$slug}_amount";
                                            $val  = old($name, $accSetup[$name] ?? '');
                                        @endphp
                                        <div class="col-lg-6">
                                            <div class="row align-items-center">
                                                <div class="col-md-8 col-6">
                                                    <label class="mb-0" for="{{ $name }}">{{ $dCat->name }}</label>
                                                </div>
                                                <div class="col-md-4 col-6 d-flex justify-content-end">
                                                    <div class="input-group">
                                                        <label class="input-group-text" for="{{ $name }}">£</label>
                                                        <input type="number" class="form-control text-end"
                                                               id="{{ $name }}" name="{{ $name }}"
                                                               placeholder="0.00" min="0" step="any"
                                                               value="{{ $val }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Other --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle">Other</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                @php
                                    $otherNames   = old('other_name', $accSetup['other_name'] ?? []);
                                    $otherAmounts = old('other_amounts', $accSetup['other_amounts'] ?? []);
                                @endphp

                                <div class="expenseItemInner">
                                    <div class="row align-items-center gy-md-2 gy-1 gx-5" id="otherExpenseItems">
                                        @if(!empty($otherNames))
                                            @foreach($otherNames as $i => $nm)
                                                <div class="col-lg-6 other-row">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8 col-6">
                                                            <input type="text" name="other_name[]" placeholder="Description..." style="width:100%"
                                                                   value="{{ $nm }}">
                                                        </div>
                                                        <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                            <div class="input-group mt-0">
                                                                <label class="input-group-text">£</label>
                                                                <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00"
                                                                       value="{{ $otherAmounts[$i] ?? '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-lg-6 other-row">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8 col-6">
                                                        <input type="text" name="other_name[]" placeholder="Description..." style="width:100%">
                                                    </div>
                                                    <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                        <div class="input-group mt-0">
                                                            <label class="input-group-text">£</label>
                                                            <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-2">
                                    <div class="col-12">
                                        <button type="button" class="add-expense">
                                            <i class="fa-solid fa-circle-plus"></i> Add another budget item
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6"><label>Total</label></div>
                                    <div class="col-md-4 col-6 d-flex justify-content-end">
                                        <p>£ <span class="totalAmount"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nav buttons --}}
                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                @php($period = data_get($accSetup ?? [], 'period_selection'))
                                @if ($period === 'first_day' || $period === 'last_working')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
                                @elseif ($period === 'fixed_date')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-two') }}">Back</a>
                                @else
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
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

    {{-- Totale e aggiunta righe Other --}}
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const totalEl = document.querySelector('.totalAmount');
            const container = document.getElementById('otherExpenseItems');

            function calcTotal(){
                let total = 0;
                document.querySelectorAll('input[type="number"]').forEach(inp=>{
                    if (inp.name === 'salary_amount') return; // esclude stipendio
                    const v = parseFloat(inp.value);
                    if (!isNaN(v)) total += v;
                });
                totalEl.textContent = total.toFixed(2);
            }
            document.addEventListener('input', e=>{
                if (e.target.matches('input[type="number"]')) calcTotal();
            });
            calcTotal();

            document.querySelector('.add-expense').addEventListener('click', ()=>{
                const wrap = document.createElement('div');
                wrap.className = 'col-lg-6 other-row';
                wrap.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-md-8 col-6">
                            <input type="text" name="other_name[]" placeholder="Description..." style="width:100%">
                        </div>
                        <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                            <div class="input-group mt-0">
                                <label class="input-group-text">£</label>
                                <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00">
                            </div>
                        </div>
                    </div>`;
                container.appendChild(wrap);
            });
        });
    </script>
@endsection
