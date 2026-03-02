@extends('layouts.customer')

@section('content')
    @if (Auth::user()->has_completed_setup == 0)
        <script>
            window.location.href = "{{ route('account-setup.step-one') }}";
        </script>
    @endif

    @php
        use Carbon\Carbon;
        use Illuminate\Support\Facades\Auth;

        // Fallback safe se il controller non passa qualcosa
        $transactions = $transactions ?? collect();
        $recurringPayments = $recurringPayments ?? collect();
        $bankAccounts = $bankAccounts ?? collect();

        $cashSavings = $cashSavings ?? 0;
        $savingsAmount = $savingsAmount ?? 0;
        $investmentAmountTotal = $investmentAmountTotal ?? 0;
        $pensionAccountsTotal = $pensionAccountsTotal ?? 0;
        $credit_card = $credit_card ?? 0;
        $networth = $networth ?? 0;

        $totalBudget = $totalBudget ?? 0;
        $amountSpent = $amountSpent ?? 0;
        $remainingBudget = $remainingBudget ?? 0;
        $income = $income ?? 0;
        $expense = $expense ?? 0;

        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        // =========================================================
        // ✅ PERIOD: prima dal controller, se assente/sbagliato dal SETUP
        // =========================================================
        $periodStart = null;
        $periodEnd = null;

        // 1) Dal controller
        if (!empty($budgetStartDate) && !empty($budgetEndDate)) {
            try {
                $periodStart = Carbon::parse($budgetStartDate, $tz)->startOfDay();
                $periodEnd   = Carbon::parse($budgetEndDate,   $tz)->endOfDay();
            } catch (\Throwable $e) {
                $periodStart = null;
                $periodEnd = null;
            }
        }

        // helper pick
        $pick = function ($obj, array $fields) {
            foreach ($fields as $f) {
                if ($obj && isset($obj->{$f}) && $obj->{$f} !== null && $obj->{$f} !== '') return $obj->{$f};
            }
            return null;
        };

        // 2) Se non c’è (o è nullo), prova DAL SETUP (customer_account_details)
        if (!$periodStart || !$periodEnd) {
            try {
                $details = \App\Models\CustomerAccountDetails::query()
                    ->where(function ($q) {
                        // prova entrambi: customer_id e user_id
                        $q->orWhere('customer_id', Auth::id())
                          ->orWhere('user_id', Auth::id());
                    })
                    ->latest('id')
                    ->first();

                // priorità assoluta: custom start/end
                $setupStartRaw = $pick($details, [
                    'custom_start', 'custom_start_date',
                    'period_start', 'period_start_date',
                    'budget_start_date', 'budget_period_start',
                    'start_date'
                ]);
                $setupEndRaw = $pick($details, [
                    'custom_end', 'custom_end_date',
                    'period_end', 'period_end_date',
                    'budget_end_date', 'budget_period_end',
                    'end_date'
                ]);

                if (!empty($setupStartRaw) && !empty($setupEndRaw)) {
                    $periodStart = Carbon::parse($setupStartRaw, $tz)->startOfDay();
                    $periodEnd   = Carbon::parse($setupEndRaw,   $tz)->endOfDay();
                } else {
                    $selection = $pick($details, ['period_selection', 'period_type', 'budget_period_type']);

                    // fallback logica selection
                    $periodStart = $now->copy()->startOfMonth()->startOfDay();
                    $periodEnd   = $now->copy()->endOfMonth()->endOfDay();

                    switch ($selection) {
                        case 'last_working':
                            if ($periodEnd->isSaturday()) $periodEnd->subDay();
                            elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);
                            break;

                        case 'fixed_date':
                            $renewalDay = (int)($pick($details, ['renewal_date', 'renewal_day', 'fixed_day']) ?? 1);
                            $renewalDay = max(1, min($renewalDay, $now->daysInMonth));

                            $anchor = Carbon::create($now->year, $now->month, $renewalDay, 0, 0, 0, $tz);

                            if ($now->lt($anchor)) {
                                $periodStart = $anchor->copy()->subMonthNoOverflow()->startOfDay();
                                $periodEnd   = $anchor->copy()->subDay()->endOfDay();
                            } else {
                                $periodStart = $anchor->copy()->startOfDay();
                                $periodEnd   = $anchor->copy()->addMonthNoOverflow()->subDay()->endOfDay();
                            }
                            break;

                        case 'weekly':
                            $periodStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                            $periodEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                            break;

                        // default => mese corrente
                    }
                }
            } catch (\Throwable $e) {
                // se CustomerAccountDetails non esiste/colonne non esistono ecc.
                $periodStart = null;
                $periodEnd = null;
            }
        }

        // 3) Ultimo fallback: mese corrente
        if (!$periodStart || !$periodEnd) {
            $periodStart = $now->copy()->startOfMonth()->startOfDay();
            $periodEnd   = $now->copy()->endOfMonth()->endOfDay();
        }

        // =========================================================
        // ✅ BANK ACCOUNTS FALLBACK: se il controller passa vuoto, prova query qui
        // =========================================================
        if ($bankAccounts->isEmpty()) {
            try {
                $bankAccounts = \App\Models\BankAccount::query()
                    ->where(function ($q) {
                        $q->orWhere('user_id', Auth::id())
                          ->orWhere('customer_id', Auth::id());
                    })
                    ->get();
            } catch (\Throwable $e) {
                // se customer_id non esiste ecc., prova solo user_id
                try {
                    $bankAccounts = \App\Models\BankAccount::where('user_id', Auth::id())->get();
                } catch (\Throwable $e2) {
                    $bankAccounts = collect();
                }
            }
        }

        // Se i totali dal controller sono tutti 0 ma ho conti, ricalcolali da $bankAccounts
        if ($bankAccounts->isNotEmpty() && ((float)$networth) == 0.0) {

            $typeGroups = [
                'bank'        => ['current_account', 'current', 'bank', 'current account'],
                'savings'     => ['savings_account', 'savings'],
                'credit_card' => ['credit_card', 'credit card'],
                'pension'     => ['pension', 'pensions'],
                'investment'  => ['investment', 'investment_account', 'investments', 'isa_account', 'isa'],
            ];

            $sumByTypes = function ($accounts, array $types) {
                return (float) $accounts->whereIn('account_type', $types)->sum('starting_balance');
            };

            $cashSavings           = $sumByTypes($bankAccounts, $typeGroups['bank']);
            $savingsAmount         = $sumByTypes($bankAccounts, $typeGroups['savings']);
            $credit_card           = $sumByTypes($bankAccounts, $typeGroups['credit_card']);
            $pensionAccountsTotal  = $sumByTypes($bankAccounts, $typeGroups['pension']);
            $investmentAmountTotal = $sumByTypes($bankAccounts, $typeGroups['investment']);

            $networth = (float) $bankAccounts->sum('starting_balance');
        }
    @endphp

    <style>
        .periodBox{ padding: 16px 18px; }
        .periodText{
            font-weight: 700; font-size: 1.05rem; letter-spacing: .2px;
            color: #e9f6f8; margin-bottom: .85rem;
        }
        .periodDatesPill{
            display:inline-block; padding:.55rem .9rem; border-radius:12px;
            background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08);
            font-weight:600; color:#cfe7eb;
        }
        .editBudgetPeriodBtn{
            display:block; width:100%; padding:.65rem 1rem; border-radius:12px;
            border:1px solid rgba(255,255,255,.22); color:#ffffff;
            background:rgba(255,255,255,0.08); text-decoration:none; text-align:center;
            font-weight:600; font-size:.95rem; line-height:1.2; transition:all .15s ease;
        }
        .editBudgetPeriodBtn:hover{
            color:#04262a; background:linear-gradient(90deg,#33BBC5,#44E0AC);
            border-color:transparent; text-decoration:none;
        }
        #resetBudgetModal .modal-title,
        #resetBudgetModal .modal-body { color:#fff !important; }
    </style>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12"><h1>Dashboard</h1></div>
            </div>
        </div>
    </section>

    @if ($transactions->isEmpty() || $recurringPayments->isEmpty() || $bankAccounts->isEmpty())
        <section class="dashboardNextStepsBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-3">Next Steps</h4>
                        <div class="nextStepsBox">
                            @if ($transactions->isEmpty())
                                <div class="item">
                                    <a href="{{ route('transactions.index') }}">
                                        <div class="circle"></div> Add transactions
                                    </a>
                                </div>
                            @endif
                            @if ($recurringPayments->isEmpty())
                                <div class="item">
                                    <a href="{{ route('recurring-payments.index') }}">
                                        <div class="circle"></div> Add recurring payments
                                    </a>
                                </div>
                            @endif
                            @if ($bankAccounts->isEmpty())
                                <div class="item">
                                    <a href="{{ route('bank-accounts.index') }}">
                                        <div class="circle"></div> Add bank account
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="mb-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="infoBox periodBox">
                        <div class="periodText">Budget Period</div>

                        <div class="periodDatesPill mb-3">
                            {{ $periodStart->isoFormat('ddd DD MMM') }} – {{ $periodEnd->isoFormat('ddd DD MMM') }}
                        </div>

                        <a href="#"
                           class="editBudgetPeriodBtn"
                           data-bs-toggle="modal"
                           data-bs-target="#resetBudgetModal"
                           title="Edit the budget period">
                            Edit Budget Period
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboardInfoBoxesBanner">
        <div class="container">
            <div class="row align-items-end  mb-md-0 mb-2">
                <div class="col-lg-6">
                    <h4 class="mb-3">Current Balances</h4>
                    <div class="infoBox">
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Bank</strong></div>
                            <div class="col-6 d-flex justify-content-end">£ {{ number_format((float)$cashSavings, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Savings</strong></div>
                            <div class="col-6 d-flex justify-content-end">£ {{ number_format((float)$savingsAmount, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Investments</strong></div>
                            <div class="col-6 d-flex justify-content-end">£ {{ number_format((float)$investmentAmountTotal, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Pensions</strong></div>
                            <div class="col-6 d-flex justify-content-end">£ {{ number_format((float)$pensionAccountsTotal, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Credit Card</strong></div>
                            <div class="col-6 d-flex justify-content-end">£ {{ number_format((float)$credit_card, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="infoBox text-center">
                        <h2>£{{ number_format((float)$networth, 2) }}</h2>
                        <h5>Net Worth</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboardInfoBoxesBanner">
        <div class="container">
            <div class="row">
                <div class="col-lg-6  mb-md-0 mb-2">
                    <div class="row px-0 align-items-center mb-md-4">
                        <div class="col-8">
                            <h4>Income and Expenses</h4>
                        </div>
                        @if ($transactions->isNotEmpty())
                            <div class="col-4">
                                <a href="{{ route('transactions.index') }}" class="viewMoreDetailsBtn">See more</a>
                            </div>
                        @endif
                    </div>
                    <div class="infoBox">
                        @if ($transactions->isNotEmpty())
                            <div class="row">
                                <div class="col-6 px-0"><canvas id="incomeChart"></canvas></div>
                                <div class="col-6 px-0"><canvas id="expenseChart"></canvas></div>
                            </div>
                        @else
                            <p>
                                To see a summary of your income and expenses, <a href="{{ route('transactions.create') }}">add transactions</a>
                                or <a href="{{ route('recurring-payments.index') }}">recurring payments</a>.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row px-0 align-items-center mb-md-4">
                        <div class="col-8"><h4>Remaining Budget</h4></div>
                        @if ($remainingBudget && $amountSpent)
                            <div class="col-4">
                                <a href="{{ route('budget.index') }}" class="viewMoreDetailsBtn">View details</a>
                            </div>
                        @endif
                    </div>
                    <div class="infoBox text-center">
                        @if ($remainingBudget && $amountSpent)
                            <canvas id="myDoughnutChart" data-remaining="{{ $remainingBudget }}" data-total="{{ $totalBudget }}"></canvas>
                            <h4 class="mb-2">
                                <span id="remainingAmount" style="color: #44E0AC">
                                    £{{ number_format((float)$remainingBudget, 2) }}
                                </span> Clearcash left
                            </h4>
                            <h5>£{{ number_format((float)$amountSpent, 2) }} spent out of £{{ number_format((float)$totalBudget, 2) }}</h5>
                        @else
                            <p>
                                To see how well you've stuck to budget, <a href="{{ route('transactions.create') }}">add transactions</a>
                                or <a href="{{ route('recurring-payments.index') }}">recurring payments</a>.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Modal conferma reset --}}
    <div class="modal fade" id="resetBudgetModal" tabindex="-1" aria-labelledby="resetBudgetModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white" id="resetBudgetModalLabel">Reset budget</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-white">
                    Do you want to reset your budget?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="{{ route('account-setup.step-one', [], false) }}" class="btn btn-danger">Yes, reset</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

    <script>
        // Doughnut
        var totalAmount = {{ $totalBudget ? (float)$totalBudget : 1000 }};
        var amountSpent = {{ $amountSpent ? (float)$amountSpent : 0 }};

        if (totalAmount <= 0) totalAmount = 1;

        var remainingAmount = totalAmount - amountSpent;
        var spentPercentage = (remainingAmount < 0) ? 100 : ((amountSpent / totalAmount) * 100);
        spentPercentage = Math.min(Math.max(spentPercentage, 0), 100);

        var doughnutEl = document.getElementById('myDoughnutChart');
        if (doughnutEl && typeof Chart !== 'undefined') {
            var ctx = doughnutEl.getContext('2d');
            new Chart(ctx, {
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

            var remainingEl = document.getElementById('remainingAmount');
            if (remainingEl) remainingEl.style.color = remainingAmount < 0 ? '#ff4d4d' : '#44E0AC';
        }

        // Bars
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof Chart === 'undefined') return;

            const income = {{ (float)$income }};
            const expense = {{ (float)$expense }};
            const maxValue = Math.max(income, expense);

            if (typeof ChartDataLabels !== 'undefined') {
                Chart.register(ChartDataLabels);
            }

            if (document.getElementById('incomeChart')) renderChart('incomeChart', 'Income', income, '#44E0AC');
            if (document.getElementById('expenseChart')) renderChart('expenseChart', 'Expense', expense, '#31D2F7');

            function renderChart(canvasId, label, amount, color) {
                const ctx = document.getElementById(canvasId).getContext('2d');

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [label],
                        datasets: [{
                            label: label,
                            data: [amount],
                            backgroundColor: color,
                            borderRadius: 10,
                            barThickness: 120
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            datalabels: {
                                align: 'top',
                                anchor: 'end',
                                backgroundColor: 'white',
                                borderRadius: 12,
                                padding: { top: 10, bottom: 4, left: 10, right: 10 },
                                color: 'black',
                                font: { weight: 'bold' },
                                formatter: (value) => `£${value}`
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: 'white', font: { size: 14, weight: 'bold' } } },
                            y: {
                                display: false, min: 0, max: maxValue + 100, grid: { display: false },
                                ticks: { color: 'white', font: { size: 14, weight: 'bold' }, stepSize: Math.ceil((maxValue + 100) / 5) }
                            }
                        },
                        layout: { padding: { top: 40, bottom: 20 } }
                    }
                });
            }
        });
    </script>
@endsection
