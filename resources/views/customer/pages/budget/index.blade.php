@extends('layouts.customer')

@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;

    $Budget      = \App\Models\Budget::class;
    $Transaction = \App\Models\Transaction::class;

    $userId     = Auth::id();
    $userSymbol = auth()->user()->currencySymbol();

    $startCarbon = \Illuminate\Support\Carbon::parse($budgetStartDate);
    $endCarbon   = \Illuminate\Support\Carbon::parse($budgetEndDate);
    $startDate   = $startCarbon->toDateString();
    $endDate     = $endCarbon->toDateString();

    $periodOptions = [];
    $cursor = $startCarbon->copy()->endOfMonth();
    for ($i = 0; $i < 18; $i++) {
        $periodOptions[] = $cursor->format('Y-m');
        $cursor->subMonth();
    }
    $currentPeriodYm = $startCarbon->format('Y-m');

    $categoryIcons = DB::table('budget_categories')->whereNotNull('icon')->pluck('icon', 'name')->toArray();

    $budgetCategoryIds = $Budget::query()
        ->where('user_id', $userId)
        ->pluck('category_id')
        ->filter()
        ->all();

    $uncategorisedSpent = $extraExpenses ?? 0;
    $showUncategorised  = ($uncategorisedSpent > 0);

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

        $outflow  = $txMonth->sum(function ($t) { $a = (float)($t->amount ?? 0); return $a > 0 ? $a  : 0; });
        $refunds  = $txMonth->sum(function ($t) { $a = (float)($t->amount ?? 0); return $a < 0 ? -$a : 0; });
        $spentNet = max(0.0, $outflow - $refunds);
        $spentByCatMonth[] = $spentNet;

        if ($spentNet > $budgetAmt) {
            $totalOverspent += ($spentNet - $budgetAmt);
        }
    }

    $catLabels        = [];
    $catBudgetAmounts = [];
    foreach ($categoryDetails as $row) {
        $label = isset($row['budgetItem']) ? (string) $row['budgetItem']->category_name : __('messages.category');
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
        .small-muted { font-size:.9rem; color:#9bb0b6; }
        :root{ --cc-cyan-400:#31D2F7; --cc-mint-500:#44E0AC; --cc-text-dk:#04262a; }
        .ccGradientBtn{ display:inline-flex; align-items:center; justify-content:center; padding:.55rem 1rem; border-radius:12px; border:none; background:linear-gradient(90deg,var(--cc-cyan-400),var(--cc-mint-500)); color:var(--cc-text-dk); font-weight:800; letter-spacing:.2px; box-shadow:0 6px 16px rgba(68,224,172,.18); transition:.15s ease box-shadow, .06s ease transform, .15s ease filter; text-transform:none; white-space:nowrap; }
        .ccGradientBtn:hover{ filter:saturate(1.06); box-shadow:0 10px 24px rgba(68,224,172,.28); transform:translateY(-1px); text-decoration:none; color:var(--cc-text-dk); }
        .resetBox { background:#0f2629; border:1px solid rgba(255,255,255,0.06); border-radius:14px; padding:10px 12px; }
        .periodForm .form-select { min-width: 180px; }
        #resetBudgetModal .modal-title, #resetBudgetModal .modal-body { color:#fff !important; }

        .catModal .modal-content{ background:#0f2629;border:1px solid rgba(255,255,255,0.08);border-radius:20px; }
        .catModalHeader{ border-bottom:1px solid rgba(255,255,255,0.06);padding:20px 24px 16px;display:flex;align-items:center;justify-content:space-between; }
        .catModalHeaderLeft{ display:flex;align-items:center;gap:12px; }
        .catModalIcon{ width:42px;height:42px;border-radius:12px;background:rgba(68,224,172,0.12);display:flex;align-items:center;justify-content:center; }
        .catModalIcon i{ color:#44E0AC;font-size:1.1rem; }
        .catModalTitle{ margin:0;color:#fff;font-weight:800;font-size:1.1rem; }
        .catModalSubtitle{ color:rgba(255,255,255,0.45);font-size:0.8rem; }
        .catModalBody{ padding:20px 24px; }
        .catModalProgressWrap{ margin-bottom:24px; }
        .catModalProgressHeader{ display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px; }
        .catModalProgressLabel{ color:rgba(255,255,255,0.6);font-size:0.85rem; }
        .catModalProgressPct{ color:#fff;font-weight:800;font-size:1.1rem; }
        .catModalProgressBar{ height:8px;border-radius:4px;background:rgba(255,255,255,0.06);overflow:hidden; }
        .catModalProgressFill{ height:100%;border-radius:4px;transition:width 0.6s ease; }
        .catModalProgressFill.on-track{ background:linear-gradient(90deg,#33BBC5,#44E0AC); }
        .catModalProgressFill.overspent{ background:linear-gradient(90deg,#ef4444,#ff6b6b); }
        .catModalProgressRange{ display:flex;justify-content:space-between;margin-top:6px; }
        .catModalProgressRange span{ color:rgba(255,255,255,0.4);font-size:0.78rem; }
        .catModalStats{ display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:24px; }
        .catModalStatCard{ background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:12px;padding:14px 12px;text-align:center; }
        .catModalStatLabel{ color:rgba(255,255,255,0.45);font-size:0.72rem;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px; }
        .catModalStatValue{ color:#fff;font-weight:800;font-size:1rem; }
        .catModalStatValue.danger{ color:#ef4444; }
        .catModalStatValue.success{ color:#44E0AC; }
        .catModalTxTitle{ color:rgba(255,255,255,0.5);font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px; }
        .catModalTxItem{ display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,0.02);margin-bottom:6px;border:1px solid rgba(255,255,255,0.04);transition:background 0.15s ease; }
        .catModalTxItem:hover{ background:rgba(255,255,255,0.04); }
        .catModalTxName{ color:#fff;font-weight:600;font-size:0.9rem; }
        .catModalTxDate{ color:rgba(255,255,255,0.4);font-size:0.75rem;margin-top:2px; }
        .catModalTxAmount{ font-weight:700;font-size:0.9rem; }
        .catModalTxAmount.expense{ color:#ef4444; }
        .catModalTxAmount.refund{ color:#31D2F7; }
        .catModalTxBadge{ font-size:0.65rem;font-weight:700;padding:3px 8px;border-radius:10px;margin-right:8px; }
        .catModalTxBadge.expense{ background:rgba(239,68,68,0.15);color:#ef4444; }
        .catModalTxBadge.refund{ background:rgba(49,210,247,0.15);color:#31D2F7; }
        .catModalNoTx{ text-align:center;padding:20px;color:rgba(255,255,255,0.35);font-size:0.9rem; }
        .catModalEditSection{ margin-top:20px;padding-top:20px;border-top:1px solid rgba(255,255,255,0.06); }
        .catModalEditTitle{ color:rgba(255,255,255,0.5);font-size:0.78rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px; }
        .catModalEditRow{ display:flex;gap:10px;align-items:flex-end; }
        .catModalEditInput{ flex:1;background:rgba(255,255,255,0.04) !important;border:1px solid rgba(255,255,255,0.1) !important;border-radius:10px;color:#fff !important;padding:10px 14px;font-size:0.95rem; }
        .catModalEditInput:focus{ border-color:rgba(68,224,172,0.4) !important;box-shadow:0 0 0 0.2rem rgba(68,224,172,0.12) !important;outline:none; }
        .catModalEditBtn{ display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:10px;border:none;font-weight:700;font-size:0.85rem;cursor:pointer;transition:all 0.15s ease; }
        .catModalSaveBtn{ background:linear-gradient(90deg,#33BBC5,#44E0AC);color:#04262a; }
        .catModalSaveBtn:hover{ transform:translateY(-1px);box-shadow:0 4px 12px rgba(68,224,172,0.25); }
        .catModalResetBtn{ background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1); }
        .catModalResetBtn:hover{ background:rgba(255,255,255,0.1);color:#fff; }
        .catModalFooter{ padding:14px 24px;border-top:1px solid rgba(255,255,255,0.06);display:flex;justify-content:space-between;align-items:center; }
    </style>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-6"><h1>{{ __('messages.budget') }}</h1></div>
                <div class="col-6 d-flex justify-content-end">
                    <form method="GET" action="/budget" class="periodForm d-flex align-items-center gap-2">
                        <label class="small-muted me-2">{{ __('messages.period') }}:</label>
                        <select name="period" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($periodOptions as $ym)
                                <option value="{{ $ym }}" {{ $ym === $currentPeriodYm ? 'selected' : '' }}>
                                    {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y') }}
                                </option>
                            @endforeach
                        </select>
                        <noscript><button class="btn btn-sm btn-outline-light" type="submit">{{ __('messages.go') }}</button></noscript>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-2">
        <div class="container">
            <div class="resetBox d-flex flex-wrap align-items-center justify-content-end gap-2">
                <button type="button" class="ccGradientBtn" data-bs-toggle="modal" data-bs-target="#resetBudgetModal">
                    {{ __('messages.reset_your_budget') }}
                </button>
            </div>
        </div>
    </section>

    <div class="modal fade" id="resetBudgetModal" tabindex="-1" aria-labelledby="resetBudgetModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="resetBudgetModalLabel">{{ __('messages.reset_budget') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.cancel') }}"></button>
                </div>
                <div class="modal-body text-white">{{ __('messages.reset_budget_confirm') }}</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                    <a href="{{ route('account-setup.step-one', [], false) }}" class="btn btn-danger">{{ __('messages.yes_reset') }}</a>
                </div>
            </div>
        </div>
    </div>

    <section class="budgetTotalBudgetBanner">
        <div class="container">
            <div class="row"><div class="col-12"><h2>{{ __('messages.total_budget') }}</h2></div></div>

            <div class="budgetChartWrapper text-center">
                <canvas id="budgetChart" width="300" height="300"></canvas>

                <div class="row mt-3 text-white text-start">
                    <div class="col-9"><h4 class="fw-bold">{{ __('messages.income') }}</h4></div>
                    <div class="col-3"><h4 class="fw-bold">{{ $userSymbol }}{{ number_format($income, 2) }}</h4></div>

                    <div class="col-9"><h4 class="fw-bold">{{ __('messages.planned_budget') }}</h4></div>
                    <div class="col-3"><h4 class="fw-bold">{{ $userSymbol }}{{ number_format($totalBudget, 2) }}</h4></div>

                    <div class="col-9"><h4 class="fw-bold">{{ __('messages.budgeted_expenses') }}</h4></div>
                    <div class="col-3"><h4 class="fw-bold">{{ $userSymbol }}{{ number_format($totalBudgetedSpent ?? 0, 2) }}</h4></div>

                    @if($totalOverspent > 0)
                        <div class="col-9"><h4 class="fw-bold text-danger">{{ __('messages.overspend') }}</h4></div>
                        <div class="col-3"><h4 class="fw-bold text-danger">{{ $userSymbol }}{{ number_format($totalOverspent, 2) }}</h4></div>
                    @endif

                    @if(($extraExpenses ?? 0) > 0)
                        <div class="col-9"><h4 class="fw-bold" style="color:#FABE58;">{{ __('messages.extra_expenses') }}</h4></div>
                        <div class="col-3"><h4 class="fw-bold" style="color:#FABE58;">{{ $userSymbol }}{{ number_format($extraExpenses, 2) }}</h4></div>
                    @endif

                    <div class="col-9"><h4 class="fw-bold">{{ __('messages.clearcash_balance') }}</h4></div>
                    <div class="col-3">
                        <h4 id="remainingAmount" class="fw-bold text-primary">{{ $userSymbol }}{{ number_format($clearCashBalance, 2) }}</h4>
                    </div>
                    <div class="col-12 small-muted">{{ __('messages.period') }}: {{ $startDate }} – {{ $endDate }}</div>
                </div>
            </div>

            <script>
                (function () {
                    var currencySymbol       = @json($userSymbol);
                    var remainingIncomeLabel = @json(__('messages.remaining_income'));

                    function optionsV4() {
                        return {
                            cutout: '70%',
                            plugins: {
                                legend: { position: "bottom", labels: { color: document.body.classList.contains('light-mode') ? "#374151" : "#ffffff", boxWidth: 15, padding: 20 } },
                                tooltip: {
                                    callbacks: {
                                        label: function (ctx) {
                                            const income = Number(ctx.chart.config._income || 0);
                                            const label  = ctx.label || '';
                                            const v      = Number(ctx.raw || 0);

                                            if (label === remainingIncomeLabel) {
                                                const pct = income > 0 ? ((v / income) * 100).toFixed(1) : '0.0';
                                                return `${label}: ${currencySymbol}${v.toFixed(2)} (${pct}%)`;
                                            }

                                            const rawBudgets = ctx.chart.config._rawBudgets || [];
                                            const idx        = ctx.dataIndex;
                                            const raw        = Number(rawBudgets[idx] || 0);
                                            const pct        = income > 0 ? ((raw / income) * 100).toFixed(1) : '0.0';
                                            return `${label}: ${currencySymbol}${raw.toFixed(2)} (${pct}% ` + @json(__('messages.of_income')) + `)`;
                                        }
                                    }
                                }
                            }
                        };
                    }

                    function renderIncomeDonut() {
                        const income     = {{ (float) $income }};
                        const catLabels  = @json($catLabels);
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

                        const labels = remainingIncome > 0 ? [...catLabels, remainingIncomeLabel] : [...catLabels];
                        const data   = remainingIncome > 0 ? [...scaled, remainingIncome]         : [...scaled];

                        if (data.every(v => Number(v) === 0)) return;

                        const baseColors = [
                            "#E6194B","#3CB44B","#FFE119","#0082C8","#911EB4","#46F0F0","#F032E6","#D2F53C","#008080",
                            "#AA6E28","#800000","#808000","#000080","#808080","#FFD8B1","#FABED4","#DCBEFF","#A9A9A9",
                            "#9A6324","#469990","#42D4F4","#BFEF45","#F58231","#4363D8","#FABE58","#B80000","#6A5ACD",
                            "#20B2AA","#FF69B4","#000000"
                        ];
                        const colors = [];
                        for (let i = 0; i < catLabels.length; i++) colors.push(baseColors[i % baseColors.length]);
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
                        <div class="col-9"><h2>{{ __('messages.budget_by_category') }}</h2></div>
                        <div class="col-3 d-md-flex justify-content-md-end">
                            <a class="editCatListBtn" href="{{ route('budget.edit-category-list') }}"><i class="fas fa-pencil"></i>{{ __('messages.edit') }}</a>
                        </div>
                    </div>

                    <div class="inner">
                        @if ($showUncategorised)
                            <div class="catItem">
                                <div class="row px-0 align-items-start">
                                    <div class="md:col-8 col-10">
                                        <h5>
                                            <i class="fa-solid fa-question" style="margin-right:8px; color:#FABE58;"></i>
                                            {{ __('messages.extra_expenses') }}
                                        </h5>
                                        <h6><span class="inline-block me-2">{{ __('messages.unbudgeted_expenses') }}</span></h6>
                                    </div>
                                    <div class="md:col-4 col-2" style="text-align:right;">
                                        <span class="spentAmount" style="color:#FABE58;">{{ $userSymbol }}{{ number_format($uncategorisedSpent, 2) }}</span>
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

                                $spentLocal     = $spentByCatMonth[$loop->index] ?? 0.0;
                                $hasTx          = $spentLocal > 0;

                                if ($budgetAmt > 0) {
                                    $spentPct      = round(($spentLocal / $budgetAmt) * 100, 2);
                                    $isAtOrOver    = ($spentPct >= 100) || ($budgetAmt - $spentLocal <= 0.00001);
                                    $progressWidth = $hasTx ? min(100, $spentPct) : 0;
                                } else {
                                    $isAtOrOver    = $hasTx;
                                    $progressWidth = $hasTx ? 100 : 0;
                                }

                                $isStrictlyOver = $spentLocal > $budgetAmt;
                                $remaining      = max(0.0, $budgetAmt - $spentLocal);
                                $modalId        = 'modal-' . str_replace([' ', '_'], '-', $item['budgetItem']->category_name);
                            @endphp

                            <div class="catItem">
                                <button type="button" class="modalBtn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <div class="row px-0 align-items-start">
                                        <div class="md:col-8 col-10">
                                            <h5>
                                                @if(!empty($categoryIcons[$item['budgetItem']->category_name]))
                                                    <i class="{{ $categoryIcons[$item['budgetItem']->category_name] }}" style="margin-right:8px; color:#44E0AC;"></i>
                                                @endif
                                                {{ str_replace('_', ' ', $item['budgetItem']->category_name) }}
                                            </h5>
                                            <h6>
                                                @if (!$hasTx)
                                                    {{ $userSymbol }}{{ number_format($budgetAmt, 2) }}
                                                    <span class="px-1 opacity-75"> {{ __('messages.remaining_of') }} </span>
                                                    {{ $userSymbol }}{{ number_format($budgetAmt, 2) }}
                                                @elseif($isStrictlyOver)
                                                    0.00
                                                    <span class="px-1 opacity-75"> {{ __('messages.remaining_of') }} </span>
                                                    {{ $userSymbol }}{{ number_format($budgetAmt, 2) }}
                                                    <span class="text-danger ms-2">
                                                        ({{ __('messages.over_by') }} {{ $userSymbol }}{{ number_format($spentLocal - $budgetAmt, 2) }})
                                                    </span>
                                                @else
                                                    {{ $userSymbol }}{{ number_format($remaining, 2) }}
                                                    <span class="px-1 opacity-75"> {{ __('messages.remaining_of') }} </span>
                                                    {{ $userSymbol }}{{ number_format($budgetAmt, 2) }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="md:col-4 col-2" style="text-align:right;">
                                            <span class="spentAmount" @if ($isAtOrOver) style="color:#D21414;" @endif>
                                                {{ $userSymbol }}{{ number_format($spentLocal, 2) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="row px-0">
                                        <div class="col-12">
                                            <div class="progress budget-progress" role="progressbar"
                                                 aria-valuenow="{{ $progressWidth }}" aria-valuemin="0" aria-valuemax="100">
                                                @if ($progressWidth > 0)
                                                    <div class="progress-bar budget-progress-bar {{ $isAtOrOver ? 'overspent' : 'on-track' }}"
                                                         style="width:{{ $progressWidth }}%;"></div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                {{-- Category detail modal --}}
                                <div class="modal fade catModal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="catModalHeader">
                                                <div class="catModalHeaderLeft">
                                                    @if(!empty($categoryIcons[$item['budgetItem']->category_name]))
                                                        <div class="catModalIcon">
                                                            <i class="{{ $categoryIcons[$item['budgetItem']->category_name] }}"></i>
                                                        </div>
                                                    @endif
                                                    <div>
                                                        <h5 class="catModalTitle">{{ ucfirst(str_replace('_', ' ', $item['budgetItem']->category_name)) }}</h5>
                                                        <small class="catModalSubtitle">
                                                            @if($hasTx)
                                                                {{ $userSymbol }}{{ number_format($spentLocal, 2) }} {{ __('messages.spent_on') }} {{ $userSymbol }}{{ number_format($budgetAmt, 2) }}
                                                            @else
                                                                {{ __('messages.no_expenses_recorded') }}
                                                            @endif
                                                        </small>
                                                    </div>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.cancel') }}" style="filter:invert(1);opacity:0.5;"></button>
                                            </div>

                                            <div class="catModalBody">
                                                @if($budgetAmt > 0)
                                                    <div class="catModalProgressWrap">
                                                        <div class="catModalProgressHeader">
                                                            <span class="catModalProgressLabel">{{ __('messages.spent') }}</span>
                                                            <span class="catModalProgressPct">{{ $budgetAmt > 0 ? round(($spentLocal / $budgetAmt) * 100) : 0 }}%</span>
                                                        </div>
                                                        <div class="catModalProgressBar">
                                                            <div class="catModalProgressFill {{ $isAtOrOver ? 'overspent' : 'on-track' }}"
                                                                 style="width:{{ min(100, $budgetAmt > 0 ? ($spentLocal / $budgetAmt) * 100 : 0) }}%"></div>
                                                        </div>
                                                        <div class="catModalProgressRange">
                                                            <span>{{ $userSymbol }}0</span>
                                                            <span>{{ $userSymbol }}{{ number_format($budgetAmt, 2) }}</span>
                                                        </div>
                                                    </div>

                                                    <div class="catModalStats">
                                                        <div class="catModalStatCard">
                                                            <div class="catModalStatLabel">{{ __('messages.budget') }}</div>
                                                            <div class="catModalStatValue">{{ $userSymbol }}{{ number_format($budgetAmt, 2) }}</div>
                                                        </div>
                                                        <div class="catModalStatCard">
                                                            <div class="catModalStatLabel">{{ __('messages.spent') }}</div>
                                                            <div class="catModalStatValue {{ $isAtOrOver ? 'danger' : '' }}">{{ $userSymbol }}{{ number_format($spentLocal, 2) }}</div>
                                                        </div>
                                                        <div class="catModalStatCard">
                                                            <div class="catModalStatLabel">{{ __('messages.remaining') }}</div>
                                                            <div class="catModalStatValue success">{{ $userSymbol }}{{ number_format($remaining, 2) }}</div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @php
                                                    $txMonth = collect($item['transactions'] ?? [])->filter(function ($t) use ($startDate, $endDate) {
                                                        $d = \Carbon\Carbon::parse($t->date)->toDateString();
                                                        return ($d >= $startDate && $d <= $endDate);
                                                    });
                                                @endphp

                                                @if ($txMonth->count() > 0)
                                                    <div style="margin-bottom:20px;">
                                                        <h6 class="catModalTxTitle">{{ __('messages.recent_activity') }}</h6>
                                                        @foreach ($txMonth->sortByDesc('date')->take(8) as $transaction)
                                                            @php
                                                                $isRefund = (float)$transaction->amount < 0;
                                                                $abs      = number_format(abs($transaction->amount), 2);
                                                            @endphp
                                                            <div class="catModalTxItem">
                                                                <div>
                                                                    <div class="catModalTxName">{{ $transaction->name ?? __('messages.unnamed') }}</div>
                                                                    <div class="catModalTxDate">{{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}</div>
                                                                </div>
                                                                <div style="display:flex;align-items:center;">
                                                                    @if($isRefund)
                                                                        <span class="catModalTxBadge refund">{{ __('messages.refund') }}</span>
                                                                        <span class="catModalTxAmount refund">+{{ $userSymbol }}{{ $abs }}</span>
                                                                    @else
                                                                        <span class="catModalTxBadge expense">{{ __('messages.type_expense') }}</span>
                                                                        <span class="catModalTxAmount expense">-{{ $userSymbol }}{{ $abs }}</span>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="catModalNoTx">
                                                        <i class="fa-solid fa-receipt" style="font-size:1.5rem;margin-bottom:8px;display:block;opacity:0.3;"></i>
                                                        {{ __('messages.no_activity_category') }}
                                                    </div>
                                                @endif

                                                <div class="catModalEditSection">
                                                    <h6 class="catModalEditTitle">{{ __('messages.edit_budget') }}</h6>
                                                    <form action="{{ route('budget.update', $item['budgetItem']->id) }}" method="post">
                                                        @csrf
                                                        @method('put')
                                                        <div class="catModalEditRow">
                                                            <div style="position:relative;flex:1;">
                                                                <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#44E0AC;font-weight:700;">{{ $userSymbol }}</span>
                                                                <input type="number" step="0.01" name="amount"
                                                                       class="catModalEditInput" style="padding-left:30px;"
                                                                       value="{{ old('amount', $budgetAmt) }}" required>
                                                            </div>
                                                            <button type="submit" class="catModalEditBtn catModalSaveBtn">
                                                                <i class="fa-solid fa-check"></i> {{ __('messages.update') }}
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="catModalFooter">
                                                <form action="{{ route('budget.reset-budget', $item['budgetItem']->id) }}" method="post">
                                                    @csrf
                                                    @method('put')
                                                    <button type="submit" class="catModalEditBtn catModalResetBtn">
                                                        <i class="fa-solid fa-arrow-rotate-left"></i> {{ __('messages.reset') }}
                                                    </button>
                                                </form>
                                                <button type="button" class="catModalEditBtn catModalResetBtn" data-bs-dismiss="modal">
                                                    {{ __('messages.close') }}
                                                </button>
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
                        backgroundColor: ['#44E0AC', 'rgba(209,249,255,0.05)'],
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
