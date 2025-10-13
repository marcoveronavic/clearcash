@extends('layouts.customer')

@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    $Budget      = \App\Models\Budget::class;
    $Transaction = \App\Models\Transaction::class;

    $userId = Auth::id();

    // Usa il periodo passato dal controller (NON now())
    $startCarbon = \Illuminate\Support\Carbon::parse($budgetStartDate);
    $endCarbon   = \Illuminate\Support\Carbon::parse($budgetEndDate);
    $startDate   = $startCarbon->toDateString();
    $endDate     = $endCarbon->toDateString();

    // Categorie con budget (utente)
    $budgetCategoryIds = $Budget::query()
        ->where('user_id', $userId)
        ->pluck('category_id')
        ->filter()
        ->all();

    /**
     * Uncategorised (NETTO) — convenzione: expense outflow > 0 ; refund < 0
     */
    $uncatAgg = $Transaction::query()
        ->where('user_id', $userId)
        ->whereBetween('date', [$startCarbon, $endCarbon])
        ->where(function ($q) {
            $q->whereNull('category_id')
              ->orWhereRaw("LOWER(category_name) = 'uncategorised'");
        })
        ->where(function ($q) {
            $q->whereNull('internal_transfer')->orWhere('internal_transfer', false);
        })
        ->where('transaction_type', 'expense')
        ->selectRaw("
            SUM(CASE WHEN amount > 0 THEN  amount ELSE 0 END) AS outflow,
            SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END) AS refunds
        ")
        ->first();

    $uncategorisedSpent = max(0.0, (float)($uncatAgg->outflow ?? 0) - (float)($uncatAgg->refunds ?? 0));
    $showUncategorised  = ($uncategorisedSpent > 0);

    /**
     * Speso NETTO per categoria (dalle transazioni già filtrate dal controller)
     */
    $spentByCatMonth = [];
    $totalOverspent  = 0.0;

    foreach ($categoryDetails as $row) {
        $budgetAmt = isset($row['startingBudgetAmount'])
            ? (float) $row['startingBudgetAmount']
            : (isset($row['budget'])
                ? (float) $row['budget']->amount
                : (isset($row['budgetItem']) ? (float) $row['budgetItem']->amount : 0.0));

        $txMonth = collect($row['transactions'] ?? [])->filter(function ($t) use ($startDate, $endDate) {
            $d = \Carbon\Carbon::parse($t->date)->toDateString();
            return ($d >= $startDate && $d <= $endDate);
        });

        // >>> expense outflow > 0 ; refund < 0
        $outflow = $txMonth->sum(function ($t) { $a = (float)($t->amount ?? 0); return $a > 0 ? $a  : 0; });
        $refunds = $txMonth->sum(function ($t) { $a = (float)($t->amount ?? 0); return $a < 0 ? -$a : 0; });

        $spentNet = max(0.0, $outflow - $refunds);
        $spentByCatMonth[] = $spentNet;

        if ($spentNet > $budgetAmt) {
            $totalOverspent += ($spentNet - $budgetAmt);
        }
    }

    // Dati per donut
    $catLabels = [];
    $catBudgetAmounts = [];
    foreach ($categoryDetails as $row) {
        $label = isset($row['budgetItem']) ? (string) $row['budgetItem']->category_name : 'Category';
        $catLabels[] = str_replace('_', ' ', $label);

        $amt = isset($row['startingBudgetAmount'])
            ? (float) $row['startingBudgetAmount']
            : (isset($row['budget'])
                ? (float) $row['budget']->amount
                : (isset($row['budgetItem']) ? (float) $row['budgetItem']->amount : 0.0));
        $catBudgetAmounts[] = $amt;
    }
@endphp

@section('content')
    <style>
        .budgetChartWrapper { position: relative; width: 350px; margin: 0 auto; }
        .budget-progress { background: rgba(209,249,255,0.05); }
        .budget-progress .progress-bar { background-color: transparent !important; }
        .budget-progress .budget-progress-bar.on-track { background: linear-gradient(90deg,#33BBC5,#44E0AC) !important; }
        .budget-progress .budget-progress-bar.overspent { background: linear-gradient(90deg,#D21414,#F96565) !important; }
        .badge-chip { border-radius: 12px; padding: .25rem .5rem; font-size: .75rem; }
        .badge-refund { background: #0ea5e9; }
        .badge-expense { background: #ef4444; }
    </style>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-8"><h1>Budget</h1></div>
                <div class="col-4 d-flex justify-content-end"></div>
            </div>
        </div>
    </section>

    <section class="budgetTotalBudgetBanner">
        <div class="container">
            <div class="row"><div class="col-12"><h2>Total Budget</h2></div></div>

            <div class="budgetChartWrapper text-center">
                <canvas id="budgetChart" width="300" height="300"></canvas>

                <div class="row mt-3 text-white text-start">
                    <div class="col-9"><h4 class="fw-bold">Income</h4></div>
                    <div class="col-3"><h4 class="fw-bold">£{{ number_format($income, 2) }}</h4></div>

                    <div class="col-9"><h4 class="fw-bold">Expenses (Budgeted)</h4></div>
                    <div class="col-3"><h4 class="fw-bold">£{{ number_format($totalBudget, 2) }}</h4></div>

                    @if($totalOverspent > 0)
                        <div class="col-9"><h4 class="fw-bold text-danger">Overspent</h4></div>
                        <div class="col-3"><h4 class="fw-bold text-danger">£{{ number_format($totalOverspent, 2) }}</h4></div>
                    @endif

                    <div class="col-9"><h4 class="fw-bold">Clear Cash Balance</h4></div>
                    <div class="col-3">
                        <h4 id="remainingAmount" class="fw-bold text-primary">£{{ number_format($clearCashBalance, 2) }}</h4>
                    </div>
                </div>
            </div>

            {{-- ************ DONUT — INCOME vs BUDGETS ************ --}}
            <script>
                (function () {
                    function optionsV4() {
                        return {
                            cutout: '70%',
                            plugins: {
                                legend: { position: "bottom", labels: { color: "#ffffff", boxWidth: 15, padding: 20 } },
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            const income = Number(ctx.chart.config._income || 0);
                                            const label  = ctx.label || '';
                                            const v      = Number(ctx.raw || 0);

                                            if (label === 'Remaining Income') {
                                                const pct = income > 0 ? ((v / income) * 100).toFixed(1) : '0.0';
                                                return `${label}: £${v.toFixed(2)} (${pct}%)`;
                                            }

                                            const rawBudgets = ctx.chart.config._rawBudgets || [];
                                            const idx        = ctx.dataIndex;
                                            const raw        = Number(rawBudgets[idx] || 0);
                                            const pct        = income > 0 ? ((raw / income) * 100).toFixed(1) : '0.0';
                                            return `${label}: £${raw.toFixed(2)} (${pct}% of income)`;
                                        }
                                    }
                                }
                            }
                        };
                    }

                    function renderIncomeDonut() {
                        const income = {{ (float) $income }};
                        const catLabels = @json($catLabels);
                        const catBudgets = @json($catBudgetAmounts);

                        if (!income || income <= 0 || catLabels.length === 0) return;

                        const sumBud = catBudgets.reduce((a,b)=>a+Number(b||0),0);

                        let scaled = catBudgets.slice();
                        let remainingIncome = 0;

                        if (sumBud <= income) {
                            remainingIncome = income - sumBud;
                        } else {
                            const k = income / sumBud;
                            scaled = catBudgets.map(v => Number(v||0) * k);
                            remainingIncome = 0;
                        }

                        const labels = remainingIncome > 0 ? [...catLabels, 'Remaining Income'] : [...catLabels];
                        const data   = remainingIncome > 0 ? [...scaled, remainingIncome]       : [...scaled];

                        if (data.every(v => Number(v) === 0)) return;

                        const baseColors = [
                            "#E6194B","#3CB44B","#FFE119","#0082C8","#911EB4","#46F0F0","#F032E6","#D2F53C","#008080",
                            "#AA6E28","#800000","#808000","#000080","#808080","#FFD8B1","#FABED4","#DCBEFF","#A9A9A9",
                            "#9A6324","#469990","#42D4F4","#BFEF45","#F58231","#4363D8","#FABE58","#B80000","#6A5ACD",
                            "#20B2AA","#FF69B4","#000000"
                        ];
                        const colors = [];
                        for (let i = 0; i < catLabels.length; i++) {
                            colors.push(baseColors[i % baseColors.length]);
                        }
                        if (remainingIncome > 0) colors.push("#2C3E45");

                        const canvas = document.getElementById("budgetChart");
                        if (!canvas) return;
                        const ctx = canvas.getContext("2d");
                        if (!ctx) return;

                        if (window.budgetChart) { try { window.budgetChart.destroy(); } catch(e){} }

                        const cfg = {
                            type: "doughnut",
                            data: { labels, datasets: [{ data, backgroundColor: colors, borderWidth: 0 }] },
                            options: optionsV4(),
                            _income: {{ (float) $income }},
                            _rawBudgets: catBudgets
                        };

                        window.budgetChart = new Chart(ctx, cfg);
                    }

                    function ensureChartAndRender() {
                        if (typeof window.Chart === 'undefined') {
                            const s = document.createElement('script');
                            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js';
                            s.onload = renderIncomeDonut;
                            document.head.appendChild(s);
                        } else {
                            renderIncomeDonut();
                        }
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', ensureChartAndRender);
                    } else {
                        ensureChartAndRender();
                    }
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
                        {{-- UNCATEGORISED solo se serve (NETTO) --}}
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
                                $budgetAmt = isset($item['startingBudgetAmount'])
                                    ? (float) $item['startingBudgetAmount']
                                    : (isset($item['budget'])
                                        ? (float) $item['budget']->amount
                                        : (isset($item['budgetItem']) ? (float) $item['budgetItem']->amount : 0.0));

                                $spentLocal   = $spentByCatMonth[$loop->index] ?? 0.0; // NETTO
                                $hasTx        = $spentLocal > 0;

                                if ($budgetAmt > 0) {
                                    $spentPct      = round(($spentLocal / $budgetAmt) * 100, 2);
                                    $isAtOrOver    = ($spentPct >= 100) || ($budgetAmt - $spentLocal <= 0.00001);
                                    $progressWidth = $hasTx ? min(100, $spentPct) : 0;
                                } else {
                                    $isAtOrOver    = $hasTx;
                                    $progressWidth = $hasTx ? 100 : 0;
                                }

                                $isStrictlyOver = $spentLocal > $budgetAmt; // per messaggio "Overspent by..."
                                $remaining      = max(0.0, $budgetAmt - $spentLocal);
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
                                                @elseif($isStrictlyOver)
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
                                            <span class="spentAmount" @if ($isAtOrOver) style="color:#D21414;" @endif>
                                                £{{ number_format($spentLocal, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row px-0">
                                        <div class="col-12">
                                            <div class="progress budget-progress" role="progressbar"
                                                 aria-valuenow="{{ $progressWidth }}"
                                                 aria-valuemin="0" aria-valuemax="100">
                                                @if ($progressWidth > 0)
                                                    <div class="progress-bar budget-progress-bar {{ $isAtOrOver ? 'overspent' : 'on-track' }}"
                                                         style="width: {{ $progressWidth }}%;"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                {{-- Modal dettaglio --}}
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

                                                @php
                                                    $txMonth = collect($item['transactions'] ?? [])->filter(function ($t) use ($startDate, $endDate) {
                                                        $d = \Carbon\Carbon::parse($t->date)->toDateString();
                                                        return ($d >= $startDate && $d <= $endDate);
                                                    });
                                                @endphp

                                                @if ($txMonth->count() > 0)
                                                    <div class="transactionList">
                                                        <h4 class="mb-3 fw-semibold text-white">Recent Activity</h4>
                                                        <ul class="list-group">
                                                            @foreach ($txMonth->sortByDesc('date')->take(10) as $transaction)
                                                                @php
                                                                    $isRefund = (float)$transaction->amount < 0; // refund = expense negativo
                                                                    $abs      = number_format(abs($transaction->amount), 2);
                                                                @endphp
                                                                <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                                    style="background-color:#d1f9ff0d;border:none;">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fs-5 fw-semibold text-white">{{ $transaction->name ?? 'No Name' }}</span>
                                                                        <small class="text-white">{{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}</small>
                                                                    </div>
                                                                    <div>
                                                                        @if($isRefund)
                                                                            <span class="badge badge-chip badge-refund me-2">Refund</span>
                                                                            <span class="fs-6 text-info">+£{{ $abs }}</span>
                                                                        @else
                                                                            <span class="badge badge-chip badge-expense me-2">Expense</span>
                                                                            <span class="fs-6 text-danger">-£{{ $abs }}</span>
                                                                        @endif
                                                                    </div>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @else
                                                    <ul class="list-group">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center"
                                                            style="background-color:#d1f9ff0d;border:none;">
                                                            <div class="d-flex flex-column">
                                                                <span class="text-white">No activity recorded yet for this category</span>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                @endif

                                                <div class="edit-budget-section mt-4">
                                                    <h4 class="fw-bold text-white mb-3">Edit {{ $item['budgetItem']->category_name }} Budget</h4>
                                                    <form action="{{ route('budget.update', $item['budgetItem']->id) }}" method="post">
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
                                                <form action="{{ route('budget.reset-budget', $item['budgetItem']->id) }}" method="post">
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
