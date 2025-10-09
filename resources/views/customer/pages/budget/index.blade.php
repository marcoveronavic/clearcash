@extends('layouts.customer')

@section('content')
    <style>
        .budgetChartWrapper {
            position: relative;
            width: 350px;
            margin: 0 auto;
        }
    </style>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-8">
                    <h1>Budget</h1>
                </div>
                <div class="col-4 d-flex justify-content-end">
                    {{-- <a href="" class="editItemBtn"><i class="fas fa-cog"></i></a> --}}
                </div>
            </div>
        </div>
    </section>

    <section class="budgetTotalBudgetBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h2>Total Budget</h2>
                </div>
            </div>

            <div class="budgetChartWrapper text-center">
                <canvas id="budgetChart" width="300" height="300"></canvas>

                <div class="row mt-3 text-white text-start">
                    <div class="col-9"><h4 class=" fw-bold">Income</h4></div>
                    <div class="col-3"><h4 class=" fw-bold">£{{ number_format($income, 2) }}</h4></div>

                    <div class="col-9"><h4 class=" fw-bold">Expenses</h4></div>
                    {{-- 🔧 QUI mostriamo la somma dei budget (non la spesa) --}}
                    <div class="col-3"><h4 class=" fw-bold">£{{ number_format($totalBudget, 2) }}</h4></div>

                    <div class="col-9"><h4 class=" fw-bold">Clear Cash Balance</h4></div>
                    <div class="col-3">
                        <h4 id="remainingAmount" class="fw-bold text-primary">
                            £{{ number_format($clearCashBalance, 2) }}
                        </h4>
                    </div>
                </div>
            </div>

            <script>
                const categoryLabels = [
                    @foreach ($categoryDetails as $item)
                        "{{ str_replace('_', ' ', $item['budgetItem']->category_name) }}",
                    @endforeach
                        "Remaining"
                ];

                const categorySpentAmounts = [
                    @foreach ($categoryDetails as $item)
                        {{ $item['totalSpent'] }},
                    @endforeach
                        {{ $remainingBudget }}
                ];

                const baseColors = [
                    "#E6194B","#3CB44B","#FFE119","#0082C8","#911EB4","#46F0F0","#F032E6","#D2F53C","#008080",
                    "#AA6E28","#800000","#808000","#000080","#808080","#FFD8B1","#FABED4","#DCBEFF","#A9A9A9",
                    "#9A6324","#469990","#42D4F4","#BFEF45","#F58231","#4363D8","#FABE58","#B80000","#6A5ACD",
                    "#20B2AA","#FF69B4","#000000",
                ];

                const categoryColors = [];
                @foreach ($categoryDetails as $index => $item)
                categoryColors.push(baseColors[{{ $loop->index }} % baseColors.length]);
                @endforeach
                categoryColors.push("#183236"); // Remaining

                const totalAmount = {{ $totalBudget }};
                const amountSpent = {{ $amountSpent }};
                const remainingAmount = totalAmount - amountSpent;

                const ctx = document.getElementById("budgetChart").getContext("2d");
                const budgetChart = new Chart(ctx, {
                    type: "doughnut",
                    data: {
                        labels: categoryLabels,
                        datasets: [{
                            data: categorySpentAmounts,
                            backgroundColor: categoryColors,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: "bottom",
                                labels: { color: "#ffffff", boxWidth: 15, padding: 20 }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const value = context.raw;
                                        const total = context.dataset.data.reduce((a,b)=>a+b,0);
                                        const percentage = ((value/total)*100).toFixed(1);
                                        return `${context.label}: £${value.toFixed(2)} (${percentage}%)`;
                                    }
                                }
                            }
                        }
                    }
                });

                document.getElementById("remainingAmount").style.color = remainingAmount < 0 ? "#D21414" : "#44E0AC";
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

                        {{-- ********* UNCATEGORISED: mostra SOLO se serve ********* --}}
                        @if (!empty($showUncategorised) && $showUncategorised)
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
                        {{-- ********* FINE UNCATEGORISED ********* --}}

                        @foreach ($categoryDetails as $item)
                            <div class="catItem">
                                <button type="button" class="modalBtn" data-bs-toggle="modal"
                                        data-bs-target="#modal-{{ str_replace(' ', '-', $item['budgetItem']->category_name) }}">
                                    <div class="row px-0 align-items-start">
                                        <div class="md:col-8 col-10">
                                            <h5>{{ str_replace('_', ' ', $item['budgetItem']->category_name) }}</h5>
                                            <h6>
                                                @if ($item['totalSpent'] == '0')
                                                    £{{ number_format($item['budget']->amount, 2) }}
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($item['budget']->amount, 2) }}
                                                @elseif($item['totalSpent'] > $item['budget']->amount)
                                                    0.00
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($item['budget']->amount, 2) }}
                                                    <span class="text-danger ms-2">
                                                        (Overspent by £{{ number_format($item['totalSpent'] - $item['budget']->amount, 2) }})
                                                    </span>
                                                @else
                                                    £{{ number_format($item['remainingAmount'], 2) }}
                                                    <span class="px-1 opacity-75"> left of </span>
                                                    £{{ number_format($item['budget']->amount, 2) }}
                                                @endif
                                            </h6>
                                        </div>
                                        <div class="md:col-4 col-2" style="text-align: right;">
                                            <span class="spentAmount"
                                                  @if ($item['totalSpent'] >= $item['budget']->amount) style="color:#D21414;" @endif>
                                                £{{ number_format($item['totalSpent'], 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row px-0">
                                        <div class="col-12">
                                            <div class="progress" role="progressbar" aria-label=""
                                                 aria-valuenow="{{ $item['startingBudgetAmount'] }}" aria-valuemin="0"
                                                 aria-valuemax="{{ $item['startingBudgetAmount'] }}">
                                                <div class="progress-bar"
                                                     style="@if ($item['totalSpent'] >= $item['budget']->amount) background: linear-gradient(to top right,#D21414,#F96565); width:100%; @else width: {{ $item['spentPercentage'] }}% @endif">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                {{-- Modal dettagli categoria --}}
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
                                                                        @if ($item['totalSpent'] == '0')
                                                                            {{ number_format($item['budget']->amount / $daysLeft, 2) }}
                                                                        @elseif($item['totalSpent'] > $item['budget']->amount)
                                                                            0.00
                                                                        @else
                                                                            {{ number_format($item['remainingAmount'] / $daysLeft, 2) }}
                                                                        @endif
                                                                    </span>
                                                                    for this category
                                                                </span>
                                                            </div>
                                                        </li>
                                                    </ul>
                                                </div>

                                                {{-- Transaction List --}}
                                                @if (count($item['transactions']) > 0)
                                                    <div class="transactionList">
                                                        <h4 class="mb-3 fw-semibold text-white">Recent Expenses</h4>
                                                        <ul class="list-group">
                                                            @foreach ($item['transactions'] as $transaction)
                                                                <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                                    style="background-color:#d1f9ff0d;border:none;">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fs-5 fw-semibold text-white">{{ $transaction->name ?? 'No Name' }}</span>
                                                                        <small class="text-white">{{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}</small>
                                                                    </div>
                                                                    @if ($transaction->transaction_type == 'income')
                                                                        <span class="badge bg-success fs-6">£+{{ number_format($transaction->amount, 2) }}</span>
                                                                    @else
                                                                        <span class="badge bg-danger fs-6">£{{ number_format($transaction->amount, 2) }}</span>
                                                                    @endif
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

                                                {{-- Edit Budget --}}
                                                <div class="edit-budget-section mt-4">
                                                    <h4 class="fw-bold text-white mb-3">Edit {{ $item['budgetItem']->category_name }} Budget</h4>
                                                    <form action="{{ route('budget.update', $item['budget']->id) }}" method="post">
                                                        @csrf
                                                        @method('put')
                                                        <div class="mb-3">
                                                            <label for="amount" class="theme_label">Amount (£)</label>
                                                            <input type="number" step="0.01" name="amount" id="amount" class="theme_input"
                                                                   value="{{ old('amount', $item['budget']->amount) }}" required>
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

    {{-- Script legacy opzionale --}}
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
        document.getElementById("remainingAmount").style.color = remainingAmount < 0 ? "#D21414" : "#33BBC5";
    </script>
@endsection
