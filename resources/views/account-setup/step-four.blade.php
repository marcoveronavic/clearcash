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

        .setupStepsWrapper .cardish{
            background:#0f2629;
            border:1px solid rgba(255,255,255,.12);
            border-radius:12px;
            padding:16px 18px;
            margin-bottom:12px;
            opacity:1 !important;
        }
        .rowItem{display:flex;justify-content:space-between;align-items:center;
            padding:8px 0;border-bottom:1px dashed rgba(255,255,255,.18)}
        .rowItem:last-child{border-bottom:0}

        .setupStepsWrapper .cardish,
        .setupStepsWrapper .cardish *{
            color:#FFFFFF !important;
            opacity:1 !important;
        }
        .setupStepsWrapper .cardish h6{ color:#E9FAFF !important; }
        .setupStepsWrapper .cardish .label{ color:#D4EEF6 !important; font-weight:600; }
        .setupStepsWrapper .cardish .value{ color:#FFFFFF !important; font-weight:700; }

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
                            <div class="item active">Crea il tuo budget</div>
                            <div class="sep"></div>
                            <div class="item active">Aggiungi conti bancari</div>
                            <div class="sep"></div>
                            <div class="item">Investimenti e pensioni</div>
                            <div class="sep"></div>
                            <div class="item">Fatto</div>
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
                    <h1>Conferma il tuo budget</h1>
                    <p>Verifica che i dettagli del tuo budget siano corretti. Puoi modificarli in qualsiasi momento.</p>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">

                    <div class="cardish">
                        <div class="rowItem">
                            <span class="label">Data stipendio</span>
                            <span class="value">{{ $salaryDate ? \Carbon\Carbon::parse($salaryDate)->format('d/m/Y') : '—' }}</span>
                        </div>
                        <div class="rowItem">
                            <span class="label">Importo stipendio</span>
                            <span class="value">€{{ number_format($salaryAmount, 2) }}</span>
                        </div>
                    </div>

                    @if(count($expenseRows))
                        <div class="cardish">
                            <h6 class="mb-2">Spese</h6>
                            @foreach($expenseRows as $r)
                                <div class="rowItem">
                                    <span class="label">{{ $r['name'] }}</span>
                                    <span class="value">€{{ number_format($r['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if(count($otherRows))
                        <div class="cardish">
                            <h6 class="mb-2">Altro</h6>
                            @foreach($otherRows as $r)
                                <div class="rowItem">
                                    <span class="label">{{ $r['name'] }}</span>
                                    <span class="value">€{{ number_format($r['amount'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="cardish">
                        <div class="rowItem">
                            <span class="label">Totale</span>
                            <span class="value">€{{ number_format($total, 2) }}</span>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a class="setupStepsBackButton" href="{{ route('account-setup.step-three', [], false) }}">Indietro</a>

                        <form action="/account-setup-step-four-store" method="post" class="m-0 p-0">
                            @csrf
                            <button type="submit" class="twoToneBlueGreenBtn">Continua</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </section>
    <script>
        (function(){
            var btn = document.createElement('button');
            btn.innerHTML = document.body.classList.contains('light-mode') ? '🌙 Dark' : '☀️ Light';
            btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:20px;border:1px solid #d1d5db;background:#fff;color:#111;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(btn);
            btn.addEventListener('click', function(){
                if (document.body.classList.contains('light-mode')) {
                    document.body.classList.remove('light-mode');
                    localStorage.setItem('cc-theme','dark');
                } else {
                    document.body.classList.add('light-mode');
                    localStorage.setItem('cc-theme','light');
                }
                window.location.reload();
            });
        })();
    </script>
@endsection

