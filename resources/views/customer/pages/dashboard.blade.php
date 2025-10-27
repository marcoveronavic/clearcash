@extends('layouts.customer')

@section('content')
    @if (Auth::user()->has_completed_setup == 0)
        <script>
            window.location.href = "{{ route('account-setup.step-one') }}";
        </script>
    @endif

    @php
        // Calcolo periodo da CustomerAccountDetails (fallback: mese corrente).
        use Carbon\Carbon;
        use Illuminate\Support\Facades\Auth;
        use App\Models\CustomerAccountDetails;
        use App\Models\Budget;

        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        $periodStart = $now->copy()->startOfMonth();
        $periodEnd   = $now->copy()->endOfMonth();

        $details = CustomerAccountDetails::where('customer_id', Auth::id())->latest('id')->first();

        if ($details) {
            switch ($details->period_selection) {
                case 'last_working':
                    $periodStart = $now->copy()->startOfMonth();
                    $periodEnd   = $now->copy()->endOfMonth();
                    if ($periodEnd->isSaturday())   $periodEnd->subDay();     // venerdì
                    elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);   // venerdì
                    break;

                case 'fixed_date':
                    $day = (int)($details->renewal_date ?? 1);
                    $day = max(1, min($day, $now->daysInMonth));
                    $anchor = Carbon::create($now->year, $now->month, $day, 0, 0, 0, $tz);
                    if ($now->lt($anchor)) { // finestra precedente
                        $periodStart = $anchor->copy()->subMonthNoOverflow();
                        $periodEnd   = $anchor->copy()->subDay();
                    } else { // finestra fino alla prossima ancora - 1
                        $periodStart = $anchor->copy();
                        $periodEnd   = $anchor->copy()->addMonthNoOverflow()->subDay();
                    }
                    break;

                case 'weekly':
                    $periodStart = $now->copy()->startOfWeek(Carbon::MONDAY);
                    $periodEnd   = $now->copy()->endOfWeek(Carbon::SUNDAY);
                    break;

                case 'custom':
                    if (!empty($details->custom_start) && !empty($details->custom_end)) {
                        $periodStart = Carbon::parse($details->custom_start, $tz)->startOfDay();
                        $periodEnd   = Carbon::parse($details->custom_end,   $tz)->endOfDay();
                    } else {
                        // fallback: prova dal budget più recente
                        $b = Budget::where('user_id', Auth::id())->orderByDesc('budget_end_date')->first();
                        if ($b) {
                            $periodStart = Carbon::parse($b->budget_start_date, $tz)->startOfDay();
                            $periodEnd   = Carbon::parse($b->budget_end_date,   $tz)->endOfDay();
                        }
                    }
                    break;

                // 'first_day' o default → già impostato mensile
            }
        }
    @endphp

    <style>
        /* Card periodo con stessa estetica dei box */
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

        /* Testo modale in bianco (come nella pagina Budget) */
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

    {{-- ===== Card periodo largo come "Current Balances" (col-lg-6) ===== --}}
    <section class="mb-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="infoBox periodBox">
                        <div class="periodText">Budget Period</div>

                        <div class="periodDatesPill mb-3">
                            {{ $periodStart->isoFormat('ddd DD MMM') }} – {{ $periodEnd->isoFormat('ddd DD MMM') }}
                        </div>

                        {{-- Clic su "Edit Budget Period" → apre il pop-up di conferma reset --}}
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
    {{-- =============================================================== --}}

    <section class="dashboardInfoBoxesBanner">
        <div class="container">
            <div class="row align-items-end  mb-md-0 mb-2">
                <div class="col-lg-6">
                    <h4 class="mb-3">Current Balances</h4>
                    <div class="infoBox">
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Bank</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    {{ number_format($cashSavings, 2) }}
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Savings</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    @if ($savingsAmount) {{ number_format($savingsAmount, 2) }} @else 0.00 @endif
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Investments</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    @if ($investmentAmountTotal) {{ number_format($investmentAmountTotal, 2) }} @else 0.00 @endif
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Pensions</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    @if ($pensionAccountsTotal) {{ number_format($pensionAccountsTotal, 2) }} @else 0.00 @endif
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Credit Card</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    {{ number_format($credit_card, 2) }}
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="infoBox text-center">
                        <h2>
                            @if ($customer->customerDetails && Auth::user()->has_completed_setup == true)
                                £{{ number_format($networth, 2) }}
                            @else
                                £XXX,XX
                            @endif
                        </h2>
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
                        @if (Auth::user()->has_completed_setup == true && $transactions->isNotEmpty())
                            <div class="col-4">
                                <a href="{{ route('transactions.index') }}" class="viewMoreDetailsBtn">See more</a>
                            </div>
                        @endif
                    </div>
                    <div class="infoBox">
                        @if (Auth::user()->has_completed_setup == true)
                            @if ($transactions->isNotEmpty())
                                <div class="row">
                                    <div class="col-6 px-0"><canvas id="incomeChart"></canvas></div>
                                    <div class="col-6 px-0"><canvas id="expenseChart"></canvas></div>
                                </div>
                            @else
                                <p>
                                    To see a summary of your income and expenses, <a
                                        href="{{ route('transactions.create') }}">add transactions</a> or <a
                                        href="{{ route('recurring-payments.index') }}">recurring payments</a>.
                                </p>
                            @endif
                        @else
                            <p>
                                To see a summary of your income and expenses, <a
                                    href="{{ route('transactions.create') }}">add transactions</a> or <a
                                    href="{{ route('recurring-payments.index') }}">recurring payments</a>.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row px-0 align-items-center mb-md-4">
                        <div class="col-8"><h4>Remaining Budget</h4></div>
                        @if (Auth::user()->has_completed_setup == true && $remainingBudget && $amountSpent)
                            <div class="col-4">
                                <a href="{{ route('budget.index') }}" class="viewMoreDetailsBtn">View details</a>
                            </div>
                        @endif
                    </div>
                    <div class="infoBox text-center">
                        @if (Auth::user()->has_completed_setup == true)
                            @if ($remainingBudget && $amountSpent)
                                <canvas id="myDoughnutChart" data-remaining="{{ $remainingBudget }}"
                                        data-total="{{ $totalBudget }}"></canvas>
                                <h4 class="mb-2">
                                    <span id="remainingAmount" style="color: #44E0AC">
                                        £{{ number_format($remainingBudget, 2) }}
                                    </span> Clearcash left
                                </h4>
                                <h5>£{{ number_format($amountSpent, 2) }} spent out of
                                    £{{ number_format($totalBudget, 2) }}</h5>
                            @else
                                <p>
                                    To see how well you've stuck to budget, <a
                                        href="{{ route('transactions.create') }}">add transactions</a> or <a
                                        href="{{ route('recurring-payments.index') }}">recurring payments</a>.
                                </p>
                            @endif
                        @else
                            <p>
                                To see how well you've stuck to budget, <a href="{{ route('transactions.index') }}">add
                                    transactions</a> or <a href="{{ route('recurring-payments.index') }}">recurring
                                    payments</a>.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- === Modal conferma reset budget (uguale a pagina Budget) === --}}
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
    {{-- ============================================================ --}}

    @if (Auth::user()->has_completed_setup == true)
        <script>
            @if ($totalBudget) var totalAmount = {{ $totalBudget }}; @else var totalAmount = 1000; @endif
            @if ($amountSpent) var amountSpent = {{ $amountSpent }}; @else var amountSpent = 0; @endif

            var remainingAmount = totalAmount - amountSpent;
            var spentPercentage = (remainingAmount < 0) ? 100 : ((amountSpent / totalAmount) * 100);
            spentPercentage = Math.min(Math.max(spentPercentage, 0), 100);

            var ctx = document.getElementById('myDoughnutChart').getContext('2d');

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

            document.getElementById('remainingAmount').style.color = remainingAmount < 0 ? '#ff4d4d' : '#44E0AC';
        </script>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                const income = {{ $income }};
                const expense = {{ $expense }};
                const maxValue = Math.max(income, expense);

                if (document.getElementById('incomeChart')) {
                    renderChart('incomeChart', 'Income', income, '#44E0AC');
                }
                if (document.getElementById('expenseChart')) {
                    renderChart('expenseChart', 'Expense', expense, '#31D2F7');
                }

                function renderChart(canvasId, label, amount, color) {
                    Chart.register(ChartDataLabels);
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
    @endif
@endsection
