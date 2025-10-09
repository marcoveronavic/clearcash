@extends('layouts.customer')
@section('content')
    @if (Auth::user()->has_completed_setup == 0)
        <script>
            window.location.href = "{{ route('account-setup.step-one') }}";
        </script>
    @endif
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>Dashboard</h1>
                </div>
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
                                        <div class="circle"></div>
                                        Add transactions
                                    </a>
                                </div>
                            @endif
                            @if ($recurringPayments->isEmpty())
                                <div class="item">
                                    <a href="{{ route('recurring-payments.index') }}">
                                        <div class="circle"></div>
                                        Add recurring payments
                                    </a>
                                </div>
                            @endif
                            @if ($bankAccounts->isEmpty())
                                <div class="item">
                                    <a href="{{ route('bank-accounts.index') }}">
                                        <div class="circle"></div>
                                        Add bank account
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="dateInfoBanner">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="inner">
                        {{ date('D d M', strtotime($budgetStartDate)) }} - {{ date('D d M', strtotime($budgetEndDate)) }}
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
                                    @if ($savingsAmount)
                                        {{ number_format($savingsAmount, 2) }}
                                    @else
                                        0.00
                                    @endif
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Investments</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    @if ($investmentAmountTotal)
                                        {{ number_format($investmentAmountTotal, 2) }}
                                    @else
                                        0.00
                                    @endif
                                @else
                                    0.00
                                @endif
                            </div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Pensions</strong></div>
                            <div class="col-6 d-flex justify-content-end">£
                                @if (Auth::user()->has_completed_setup == true)
                                    @if ($pensionAccountsTotal)
                                        {{ number_format($pensionAccountsTotal, 2) }}
                                    @else
                                        0.00
                                    @endif
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
                        {{-- <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>Salary</strong></div>
                            <div class="col-6 d-flex justify-content-end">
                                @if (Auth::user()->has_completed_setup == true)
                                @else
                                    0.00
                                @endif
                            </div>
                        </div> --}}
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
                    <div class="row px-0 align-items-center mb-md-4   ">
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
                                    <div class="col-6 px-0">
                                        {{-- <div style="height: 220px;"> --}}
                                            <canvas id="incomeChart"></canvas>
                                        {{-- </div> --}}
                                    </div>
                                    <div class="col-6 px-0">
                                        {{-- <div style="height: 220px;"> --}}
                                            <canvas id="expenseChart"></canvas>
                                        {{-- </div> --}}
                                    </div>
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
                        <div class="col-8">
                            <h4>Remaining Budget</h4>
                        </div>
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
                                <h4 class="mb-2"><span id="remainingAmount"
                                        style="color: #44E0AC">£{{ number_format($remainingBudget, 2) }}</span> Clearcash
                                    left</h4>
                                <h5>£{{ number_format($amountSpent, 2) }} spent out of
                                    £{{ number_format($totalBudget, 2) }} </h5>
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

    @if (Auth::user()->has_completed_setup == true)
        <script>
            @if ($totalBudget)
                var totalAmount = {{ $totalBudget }};
            @else
                var totalAmount = 1000;
            @endif

            @if ($amountSpent)
                var amountSpent = {{ $amountSpent }};
            @else
                var amountSpent = 0;
            @endif

            // Calculate remaining amount
            var remainingAmount = totalAmount - amountSpent;

            // Handle percentage logic
            var spentPercentage = (remainingAmount < 0) ? 100 : ((amountSpent / totalAmount) * 100);
            spentPercentage = Math.min(Math.max(spentPercentage, 0), 100); // Ensure between 0-100

            var ctx = document.getElementById('myDoughnutChart').getContext('2d');

            var myDoughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [spentPercentage, 100 - spentPercentage], // Adjusted data values
                        backgroundColor: ['#44E0AC', 'rgba(209, 249, 255, 0.05)'],
                        borderColor: ['transparent', 'transparent'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%', // Adjusted cutout for better appearance
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true
                        }
                    }
                }
            });

            // Update the text color dynamically
            document.getElementById('remainingAmount').style.color = remainingAmount < 0 ? '#ff4d4d' : '#44E0AC';
        </script>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>

        <script>
            // document.addEventListener("DOMContentLoaded", function() {

            //     if (document.getElementById('incomeChart')) {
            //         incomeChart();
            //     }
            //     if (document.getElementById('expenseChart')) {
            //         expenseChart();
            //     }
            // });

            // function incomeChart() {
            //     // Register the plugin
            //     Chart.register(ChartDataLabels);

            //     const ctx = document.getElementById('incomeChart').getContext('2d');

            //     const income = {{ $income }};

            //     new Chart(ctx, {
            //         type: 'bar',
            //         data: {
            //             labels: ['Income'],
            //             datasets: [{
            //                 label: 'Income',
            //                 data: [income], // Assign value to first label
            //                 backgroundColor: '#44E0AC',
            //                 borderRadius: 10, // Rounded bars
            //                 barThickness: 120 // Adjust bar width
            //             }, ]
            //         },
            //         options: {
            //             responsive: true,
            //             maintainAspectRatio: false, // Allow chart to be responsive and flexible
            //             plugins: {
            //                 legend: {
            //                     display: false
            //                 }, // Hide legend
            //                 datalabels: {
            //                     align: 'top',
            //                     anchor: 'end',
            //                     backgroundColor: 'white',
            //                     borderRadius: 12, // Rounded pill background
            //                     padding: {
            //                         top: 10,
            //                         bottom: 4,
            //                         left: 10,
            //                         right: 10
            //                     }, // Increased top padding for margin
            //                     color: 'black',
            //                     font: {
            //                         weight: 'bold'
            //                     },
            //                     formatter: (value) => `£${value}`
            //                 }
            //             },
            //             scales: {
            //                 x: {
            //                     grid: {
            //                         display: false
            //                     }, // Remove grid
            //                     ticks: {
            //                         color: 'white', // Label color for Income & Expenses
            //                         font: {
            //                             size: 14,
            //                             weight: 'bold'
            //                         },
            //                     }
            //                 },
            //                 y: {
            //                     display: true, // Show y-axis
            //                     min: 0, // Start y-axis from 0
            //                     max: income + 500, // Ensure y-axis is higher than the income value
            //                     grid: {
            //                         display: false
            //                     }, // Remove y-axis grid lines
            //                     display: false,
            //                     ticks: {
            //                         color: 'white', // Color for y-axis ticks
            //                         font: {
            //                             size: 14,
            //                             weight: 'bold'
            //                         },
            //                         stepSize: 500, // Adjust step size for y-axis ticks
            //                     }
            //                 }
            //             },
            //             layout: {
            //                 padding: {
            //                     top: 10, // Extra space at the top to prevent overlap
            //                     bottom: 20 // Space at the bottom to avoid the bars reaching the container's bottom edge
            //                 }
            //             }
            //         }
            //     });
            // }

            // function expenseChart() {
            //     // Register the plugin
            //     Chart.register(ChartDataLabels);

            //     const ctx = document.getElementById('expenseChart').getContext('2d');

            //     const expense = {{ $expense }};

            //     new Chart(ctx, {
            //         type: 'bar',
            //         data: {
            //             labels: ['Expense'],
            //             datasets: [{
            //                 label: 'Expense',
            //                 data: [expense], // Assign value to first label
            //                 backgroundColor: '#31D2F7',
            //                 borderRadius: 10, // Rounded bars
            //                 barThickness: 120 // Adjust bar width
            //             }, ]
            //         },
            //         options: {
            //             responsive: true,
            //             maintainAspectRatio: false, // Allow chart to be responsive and flexible
            //             plugins: {
            //                 legend: {
            //                     display: false
            //                 }, // Hide legend
            //                 datalabels: {
            //                     align: 'top',
            //                     anchor: 'end',
            //                     backgroundColor: 'white',
            //                     borderRadius: 12, // Rounded pill background
            //                     padding: {
            //                         top: 10,
            //                         bottom: 4,
            //                         left: 10,
            //                         right: 10
            //                     }, // Increased top padding for margin
            //                     color: 'black',
            //                     font: {
            //                         weight: 'bold'
            //                     },
            //                     formatter: (value) => `£${value}`
            //                 }
            //             },
            //             scales: {
            //                 x: {
            //                     grid: {
            //                         display: false
            //                     }, // Remove grid
            //                     ticks: {
            //                         color: 'white', // Label color for Income & Expenses
            //                         font: {
            //                             size: 14,
            //                             weight: 'bold'
            //                         },
            //                     }
            //                 },
            //                 y: {
            //                     display: true, // Show y-axis
            //                     min: 0, // Start y-axis from 0
            //                     max: expense + 500, // Ensure y-axis is higher than the income value
            //                     grid: {
            //                         display: false
            //                     }, // Remove y-axis grid lines
            //                     display: false,
            //                     ticks: {
            //                         color: 'white', // Color for y-axis ticks
            //                         font: {
            //                             size: 14,
            //                             weight: 'bold'
            //                         },
            //                         stepSize: 500, // Adjust step size for y-axis ticks
            //                     }
            //                 }
            //             },
            //             layout: {
            //                 padding: {
            //                     top: 10, // Extra space at the top to prevent overlap
            //                     bottom: 20 // Space at the bottom to avoid the bars reaching the container's bottom edge
            //                 }
            //             }
            //         }
            //     });
            // }

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
                                legend: {
                                    display: false
                                },
                                datalabels: {
                                    align: 'top',
                                    anchor: 'end',
                                    backgroundColor: 'white',
                                    borderRadius: 12,
                                    padding: {
                                        top: 10,
                                        bottom: 4,
                                        left: 10,
                                        right: 10
                                    },
                                    color: 'black',
                                    font: {
                                        weight: 'bold'
                                    },
                                    formatter: (value) => `£${value}`
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'white',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        }
                                    }
                                },
                                y: {
                                    display: false,
                                    min: 0,
                                    max: maxValue + 100,
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: 'white',
                                        font: {
                                            size: 14,
                                            weight: 'bold'
                                        },
                                        stepSize: Math.ceil((maxValue + 100) / 5)
                                    }
                                }
                            },
                            layout: {
                                padding: {
                                    top: 40, // Increased top padding to prevent cut-off
                                    bottom: 20
                                }
                            }
                        }
                    });
                }
            });
        </script>
    @endif
@endsection
