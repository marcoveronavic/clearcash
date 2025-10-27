@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        header, aside.sidebar { display:none; }
        main.dashboardMain { padding-top:2rem; width:100%; }

        .setupStepsWrapper h1 { font-size:32px; }
        @media (min-width:1200px){ h1,.h1{ font-size:2.25rem; } }

        /* --- Card riepilogo --- */
        .setupStepsWrapper .cardish{
            background:#0f2629;
            border:1px solid rgba(255,255,255,.12);
            border-radius:12px;
            padding:16px 18px;
            margin-bottom:12px;
            opacity:1 !important;           /* annulla eventuali opacità globali */
        }
        .rowItem{display:flex;justify-content:space-between;align-items:center;
            padding:8px 0;border-bottom:1px dashed rgba(255,255,255,.18)}
        .rowItem:last-child{border-bottom:0}

        /* ---- OVERRIDE CONTRASTO (solo in questa pagina) ---- */
        .setupStepsWrapper .cardish,
        .setupStepsWrapper .cardish *{
            color:#FFFFFF !important;       /* testo bianco ovunque nella card */
            opacity:1 !important;           /* niente testo “sbiadito”         */
        }
        .setupStepsWrapper .cardish h6{ color:#E9FAFF !important; }
        .setupStepsWrapper .cardish .label{ color:#D4EEF6 !important; font-weight:600; }
        .setupStepsWrapper .cardish .value{ color:#FFFFFF !important; font-weight:700; }

        /* anche i blocchi “Expenses/Other” se presenti in questa pagina */
        .setupStepsWrapper .expensesWrap,
        .setupStepsWrapper .expensesWrap *{
            color:#FFFFFF !important;
            opacity:1 !important;
        }
    </style>

    @php
        $acc = $accSetup ?? [];

        $salaryDate   = $acc['salary_date']   ?? null;
        $salaryAmount = isset($acc['salary_amount']) ? (float)$acc['salary_amount'] : 0;

        $expenseRows = [];
        foreach ($acc as $k => $v) {
            if (preg_match('/^expense_(.+)_amount$/', $k, $m)) {
                $expenseRows[] = ['name' => ucwords(str_replace('_',' ', $m[1])), 'amount' => (float)$v];
            }
        }

        $otherRows = [];
        $otherNames   = $acc['other_name']   ?? [];
        $otherAmounts = $acc['other_amounts']?? [];
        if (is_array($otherNames) && is_array($otherAmounts)) {
            foreach ($otherNames as $i => $n) {
                $n = trim((string)$n);
                if ($n === '') continue;
                $otherRows[] = ['name'=>$n, 'amount'=>(float)($otherAmounts[$i] ?? 0)];
            }
        }

        $total = isset($totalAmount) ? (float)$totalAmount : 0.0;
        if ($total === 0.0) {
            $total = array_reduce($expenseRows, fn($c,$r)=>$c+$r['amount'], 0.0)
                   + array_reduce($otherRows,   fn($c,$r)=>$c+$r['amount'], 0.0)
                   + (float)($acc['savings_pension_amount'] ?? 0)
                   + (float)($acc['savings_investments_amount'] ?? 0);
        }
    @endphp

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item active">Add bank accounts</div>
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

            <div class="row mt-md-4 mt-0">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Confirm your budget</h1>
                    <p>Please check the details of your budget are correct. You can change this at any time.</p>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">

                    <div class="cardish">
                        <div class="rowItem">
                            <span class="label">Salary date</span>
                            <span class="value">{{ $salaryDate ? \Carbon\Carbon::parse($salaryDate)->format('d/m/Y') : '—' }}</span>
                        </div>
                        <div class="rowItem">
                            <span class="label">Salary amount</span>
                            <span class="value">£{{ number_format($salaryAmount, 2) }}</span>
                        </div>
                    </div>

                    @if(count($expenseRows))
                        <div class="cardish">
                            <h6 class="mb-2">Expenses</h6>
                            @foreach($expenseRows as $r)
                                <div class="rowItem">
                                    <span class="label">{{ $r['name'] }}</span>
                                    <span class="value">£{{ number_format($r['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(count($otherRows))
                        <div class="cardish">
                            <h6 class="mb-2">Other</h6>
                            @foreach($otherRows as $r)
                                <div class="rowItem">
                                    <span class="label">{{ $r['name'] }}</span>
                                    <span class="value">£{{ number_format($r['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="cardish">
                        <div class="rowItem">
                            <span class="label">Total</span>
                            <span class="value">£{{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a class="setupStepsBackButton" href="{{ route('account-setup.step-three', [], false) }}">Back</a>

                        <form action="/account-setup-step-four-store" method="post" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
@endsection
