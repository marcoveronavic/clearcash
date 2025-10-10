@extends('layouts.customer')

@php
    use Illuminate\Support\Facades\DB;

    $Budget      = \App\Models\Budget::class;
    $Transaction = \App\Models\Transaction::class;

    // Limiti di periodo (solo mese corrente, confronto a livello di data 'YYYY-MM-DD')
    $startCarbon = now()->startOfMonth();
    $endCarbon   = now()->endOfMonth();
    $startDate   = $startCarbon->toDateString();
    $endDate     = $endCarbon->toDateString();

    // Categorie con budget (se servono filtri user/bank_account, aggiungili qui)
    $budgetCategoryIds = $Budget::query()->pluck('category_id')->filter()->all();

    // Spese "uncategorised" del mese (uscite senza categoria o in categorie senza budget)
    $uncategorisedSpent = $Transaction::query()
        ->whereBetween('date', [$startCarbon, $endCarbon])
        ->where('amount', '<', 0)
        ->where(function ($q) use ($budgetCategoryIds) {
            $q->whereNull('category_id');
            if (count($budgetCategoryIds)) {
                $q->orWhereNotIn('category_id', $budgetCategoryIds);
            }
        })
        ->sum(DB::raw('ABS(amount)'));
    $showUncategorised = ($uncategorisedSpent > 0);

    /**
     * Calcolo SPESO REALE DEL MESE per ciascuna categoria usando
     * le transazioni già fornite in $categoryDetails[*]['transactions'].
     * (Così non dipendiamo da eventuali mismatch di category_id.)
     */
    $spentByCatMonth = [];
    $totalOverspend  = 0.0;

    foreach ($categoryDetails as $row) {
        $budgetAmt = isset($row['budget']) ? (float) $row['budget']->amount : 0.0;

        // Filtra SOLO le transazioni-uscita del mese corrente
        $txMonth = collect($row['transactions'] ?? [])->filter(function ($t) use ($startDate, $endDate) {
            // data normalizzata a YYYY-MM-DD per evitare problemi di timezone
            $d   = \Carbon\Carbon::parse($t->date)->toDateString();
            $amt = (float) ($t->amount ?? 0);
            $type = strtolower(trim((string)($t->transaction_type ?? '')));

            // Considero "spesa" se importo < 0 OPPURE se il tipo non è 'income'
            $isExpense = ($amt < 0) || ($type !== '' && $type !== 'income');

            return $isExpense && ($d >= $startDate && $d <= $endDate);
        });

        // Somma in valore assoluto SOLO delle uscite selezionate
        $spent = $txMonth->sum(function ($t) {
            return abs((float) $t->amount);
        });

        $spentByCatMonth[] = $spent;

        if ($spent > $budgetAmt) {
            $totalOverspend += ($spent - $budgetAmt);
        }
    }
@endphp

@section('content')
    <style>
        .budgetChartWrapper { position: relative; width: 350px; margin: 0 auto; }
        .budget-progress { background: rgba(209,249,255,0.05); }
        .budget-progress .progress-bar { background-color: transparent !important; }
        .budget-progress .budget-progress-bar.on-track { background: linear-gradient(90deg,#33BBC5,#44E0AC) !important; }
        .budget-progress .budget-progress-bar.overspent { background: linear-gradient(90deg,#D21414,#F96565) !important; }
    </style>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-8"><h1>Budget</h1></div>
                <div class="col-4 d-flex justify-content-end">
                    {{-- <a href="" class="editItemBtn"><i class="fas fa-cog"></i></a> --}}
                </div>
            </div>
        </div>
    </section>

    <section class="budgetTotalBudgetBanner">
        <div class="container">
            <div class="row"><div class="col-12"><h2>Total Budget</h2></div></div>

            <div class="budgetChartWrapper text-center">
                <canvas id="budgetChart" width="300" height="300"></canvas>

                <div class="row mt-3 text-white text-start">
                    <div class="col-9"><h4 class=" fw-bold">Income</h4></div>
                    <div class="col-3"><h4 class=" fw-bold">£{{ number_format($income, 2) }}</h4></div>

                    <div class="col-9"><h4 class=" fw-bold">Expenses (Budgeted)</h4></div>
                    <div class="col-3"><h4 class=" fw-bold">£{{ number_format($totalBudget, 2) }}</h4></div>

                    @if($totalOverspend > 0)
                        <div class="col-9"><h4 class=" fw-bold text-danger">Overspend</h4></div>
                        <div class="col-3"><h4 class=" fw-bold text-danger">£{{ number_format($totalOverspend, 2) }}</h4></div>
                    @endif

                    <div class="col-9"><h4 class=" fw-bold">Clear Cash Balance</h4></div>
                    <div class="col-3">
                        <h4 id="remainingAmount" class="fw-bold text-primary">£{{ number_format($clearCashBalance, 2) }}</h4>
                    </div>
                </div>
            </div>

            {{-- DONUT — usa lo speso reale del mese; "Remaining" solo se > 0 --}}
            <script>
                (function () {
                    function makeOptions(isV2) {
                        if (isV2) {
                            return {
                                cutoutPercentage: 70,
                                legend: { position: "bottom", labels: { fontColor: "#ffffff", boxWidth: 15, padding: 20 } },
                                tooltips: { callbacks: {
                                        label: function (item, data) {
                                            var val = Number(data.datasets[0].data[item.index] || 0);
                                            var tot = data.datasets[0].data.reduce(function(a,b){return Number(a)+Number(b)},0) || 1;
                                            var pct = ((val/tot)*100).toFixed(1);
                                            return data.labels[item.index] + ": £" + val.toFixed(2) + " (" + pct + "%)";
                                        }
                                    }}
                            };
                        }
                        return {
                            cutout: '70%',
                            plugins: {
                                legend: { position: "bottom", labels: { color: "#ffffff", boxWidth: 15, padding: 20 } },
                                tooltip: { callbacks: {
                                        label: function (ctx) {
                                            const val = Number(ctx.raw || 0);
                                            const tot = ctx.dataset.data.reduce((a,b)=>Number(a)+Number(b),0) || 1;
                                            const pct = ((val/tot)*100).toFixed(1);
                                            return `${ctx.label}: £${val.toFixed(2)} (${pct}%)`;
                                        }
                                    }}
                            }
                        };
                    }

                    function renderBudgetChart() {
                        const catLabels = [
                            @foreach ($categoryDetails as $item)
                                "{{ str_replace('_', ' ', $item['budgetItem']->category_name) }}",
                            @endforeach
                        ];

                        // Speso reale del mese per categoria (già calcolato in PHP nello stesso ordine)
                        const catSpent = [
                            @foreach ($spentByCatMonth as $spent)
                                {{ (float) $spent }},
                            @endforeach
                        ];

                        const remainingRaw = {{ (float) $remainingBudget }};
                        const remaining = remainingRaw > 0 ? remainingRaw : 0;

                        const labels = remaining > 0 ? [...catLabels, "Remaining"] : [...catLabels];
                        const data   = remaining > 0 ? [...catSpent, remaining]   : [...catSpent];

                        if (!data.length || data.every(v => Number(v) === 0)) return;

                        const baseColors = [
                            "#E6194B","#3CB44B","#FFE119","#0082C8","#911EB4","#46F0F0","#F032E6","#D2F53C","#008080",
                            "#AA6E28","#800000","#808000","#000080","#808080","#FFD8B1","#FABED4","#DCBEFF","#A9A9A9",
                            "#9A6324","#469990","#42D4F4","#BFEF45","#F58231","#4363D8","#FABE58","#B80000","#6A5ACD",
                            "#20B2AA","#FF69B4","#000000"
                        ];
                        const colors = [];
                        @foreach ($categoryDetails as $index => $item)
                        colors.push(baseColors[{{ $loop->index }} % baseColors.length]);
                        @endforeach
                        if (remaining > 0) colors.push("#183236");

                        const canvas = document.getElementById("budgetChart");
                        if (!canvas) return;
                        const ctx = canvas.getContext("2d");
                        if (!ctx) return;

                        const isV2 = !!(window.Chart && window.Chart.defaults && window.Chart.defaults.global);
                        if (window.budgetChart) { try { window.budgetChart.destroy(); } catch(e){} }

                        window.budgetChart = new Chart(ctx, {
                            type: "doughnut",
                            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
                            options: makeOptions(isV2)
                        });

                        const ccBalance = {{ (float) $clearCashBalance }};
                        const el = document.getElementById("remainingAmount");
                        if (el) el.style.color = ccBalance < 0 ? "#D21414" : "#44E0AC";
                    }

                    function ensureChartAndRender() {
                        if (typeof window.Chart === 'undefined') {
                            const s = document.createElement('script');
                            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
                            s.onload = renderBudgetChart;
                            document.head.appendChild(s);
                        } else {
                            renderBudgetChart();
                        }
                    }
                    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ensureChartAndRender);
                    else ensureChartAndRender();
                })();
            </script>
        </div>
    </section>

    <section class="categoryBudgetsWrapper">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="row align-items-center">
                        <div class="col-9"><h2>Category Budgets</h2></div>
                        <div class="col-3 d-md-flex justify-content-md-end">
                            <a class="editCatListBtn" href="{{ route('budget.edit-category-list') }}"><i class="fas fa-pencil"></i>Edit</a>
                        </div>
                    </div>

                    <div class="inner">
                        {{-- UNCATEGORISED solo se serve --}}
                        @if ($showUncategorised)
                            <div class="catItem">
                                <div class="row px-0 align-items-start">
                                    <div class="md:col-8 col-10">
                                        <h5>Uncategorised</h5>
                                        <h6><span class="inline-block me-2">Total Spent</span>
                                            £{{ number_format($uncategorisedSpent, 2) }}
                                        </h6>
                                    </div>
                                    <div class="md:col-4 col-2" style="text-align:right;">
                                        <span class="spentAmount">£{{ number_format($uncategorisedSpent, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @foreach ($categoryDetails as $item)
                            @php
                                // Speso reale del mese per questa categoria (ricavato dall'array calcolato sopra)
                                $spentLocal = $spentByCatMonth[$loop->index] ?? 0.0;

                                $budgetAmt     = (float) $item['budget']->amount;
                                $hasTx         = $spentLocal > 0;
                                $isOverspent   = $spentLocal > $budgetAmt;
                                $remaining     = max(0.0, $budgetAmt - $spentLocal);
                                $progressWidth = $isOverspent
                                    ? 100
                                    : ($hasTx ? min(100, round(($spentLocal / $budgetAmt) * 100, 2)) : 0);

                                // Per il modale: mostro SOLO le spese del mese in corso
                                $txMonth = collect($item['transactions'] ?? [])->filter(function ($t) use ($startDate, $endDate) {
                                    $d = \Carbon\Carbon::parse($t->date)->toDateString();
                                    $amt = (float) ($t->amount ?? 0);
                                    $type = strtolower(trim((string)($t->transaction_type ?? '')));
                                    $isExpense = ($amt < 0) || ($type !== '' && $type !== 'income');
                                    return $isExpense && ($d >= $startDate && $d <= $endDate);
                                });
                            @endphp

                            <div class="catItem">
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                        data-bs-target="#modal-{{ str_replace(' ', '-', $item['budgetItem']->category_name) }}">
                                    <div class="row px-0 align-items-start">
                                        <div class="md:col-8 col-10">
                                            <h5>{{ str_replace('_', ' ', $item['budgetItem']->category_name) }}</h5>
                                            <h6>
                                                @if (!$hasTx)
                                                    £{{ number_format($budgetAmt, 2) }}
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($budgetAmt, 2) }}
                                                @elseif($isOverspent)
                                                    0.00
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($budgetAmt, 2) }}
                                                    <span class="text-danger ms-2">
                                                        (Overspent by £{{ number_format($spentLocal - $budgetAmt, 2) }})
                                                    </span>
                                                @else
                                                    £{{ number_format($remaining, 2) }}
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($budgetAmt, 2) }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="md:col-4 col-2" style="text-align: right;">
                                            <span class="spentAmount" @if ($isOverspent) style="color:#D21414;" @endif>
                                                £{{ number_format($spentLocal, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row px-0">
                                        <div class="col-12">
                                            <div class="progress budget-progress" role="progressbar"
                                                 aria-valuenow="{{ $isOverspent ? 100 : $progressWidth }}"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                {{-- Disegno la barra SOLO se > 0% → traccia vuota quando non ci sono spese --}}
                                                @if ($progressWidth > 0)
                                                    <div class="progress-bar budget-progress-bar {{ $isOverspent ? 'overspent' : 'on-track' }}"
                                                         style="width: {{ $progressWidth }}%;"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                {{-- Modal --}}
                                <div class="modal fade"
                                     id="modal-{{ str_replace(' ', '-', $item['budgetItem']->category_name) }}"
                                     tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content ">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                            <div class="modal-body">

                                                <div class="transactionList mb-3 pt-0 mt-0">
                                                    <ul class="list-group mt-0 pt-0">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                            style="background-color:#d1f9ff0d;border:none;">
                                                            <div class="d-flex flex-column">
                                                                <span class="fs-5 fw-semibold text-white">
                                                                    You have daily budget of
                                                                    <span style="color:#31d2f7"> £
                                                                        @if (!$hasTx)
                                                                            {{ number_format($budgetAmt / $daysLeft, 2) }}
                                                                        @elseif($isOverspent)
                                                                            0.00
                                                                        @else
                                                                            {{ number_format($remaining / $daysLeft, 2) }}
                                                                        @endif
                                                                    </span>
                                                                    for this category
                                                                </span>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>

                                                @if ($txMonth->count() > 0)
                                                    <div class="transactionList">
                                                        <h4 class="mb-3 fw-semibold text-white">Recent Expenses</h4>
                                                        <ul class="list-group">
                                                            @foreach ($txMonth->sortByDesc('date')->take(10) as $transaction)
                                                                <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                                    style="background-color:#d1f9ff0d;border:none;">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fs-5 fw-semibold text-white">{{ $transaction->name ?? 'No Name' }}</span>
                                                                        <small class="text-white">{{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}</small>
                                                                    </div>
                                                                    <span class="badge bg-danger fs-6">£{{ number_format(abs($transaction->amount), 2) }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @else
                                                    <ul class="list-group">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center"
                                                            style="background-color:#d1f9ff0d;border:none;">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-white">No expenses recorded yet for this category</span>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                @endif

                                                <div class="edit-budget-section mt-4">
                                                    <h4 class="fw-bold text-white mb-3">Edit {{ $item['budgetItem']->category_name }} Budget</h4>
                                                    <form action="{{ route('budget.update', $item['budget']->id) }}" method="post">
                                                        @csrf
                                                        @method('put')
                                                        <div class="mb-3">
                                                            <label for="amount" class="theme_label">Amount (£)</label>
                                                            <input type="number" step="0.01" name="amount" id="amount" class="theme_input"
                                                                   value="{{ old('amount', $budgetAmt) }}" required>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <button type="submit" class="twoToneBlueGreenBtn text-center py-2">Update Budget</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <form action="{{ route('budget.reset-budget', $item['budget']->id) }}" method="post">
                                                    @csrf
                                                    @method('put')
                                                    <button type="submit" class="twoToneBlueGreenBtn text-center py-2">Reset</button>
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

    {{-- Script legacy opzionale (non interferisce col donut) --}}
    <script>
        @if ($totalBudget) var totalAmount = {{ $totalBudget }}; @else var totalAmount = 1000; @endif
        @if ($amountSpent) var amountSpent = {{ $amountSpent }}; @else var amountSpent = 0; @endif

        var remainingAmount = totalAmount - amountSpent;
        var spentPercentage = (remainingAmount < 0) ? 100 : ((amountSpent / totalAmount) * 100);
        spentPercentage = Math.min(Math.max(spentPercentage, 0), 100);

        var el = document.getElementById('myDoughnutChart');
        if (el) {
            var ctx = el.getContext('2d');
            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [spentPercentage, 100 - spentPercentage],
                        backgroundColor: ['#44E0AC', 'rgba(209, 249, 255, 0.05)'],
                        borderColor: ['transparent', 'transparent'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: { legend: { display: false }, tooltip: { enabled: true } }
                }
            });
        }
        document.getElementById("remainingAmount").style.color = ({{ $clearCashBalance }}) < 0 ? "#D21414" : "#33BBC5";
    </script>
@endsection
