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

        $transactions      = $transactions ?? collect();
        $recurringPayments = $recurringPayments ?? collect();
        $bankAccounts      = $bankAccounts ?? collect();
        $savingGoals       = $savingGoals ?? collect();

        $cashSavings           = $cashSavings ?? 0;
        $savingsAmount         = $savingsAmount ?? 0;
        $investmentAmountTotal = $investmentAmountTotal ?? 0;
        $pensionAccountsTotal  = $pensionAccountsTotal ?? 0;
        $credit_card           = $credit_card ?? 0;
        $networth              = $networth ?? 0;

        $totalBudget     = $totalBudget ?? 0;
        $amountSpent     = $amountSpent ?? 0;
        $remainingBudget = $remainingBudget ?? 0;
        $income          = $income ?? 0;
        $expense         = $expense ?? 0;
        $budgetAlerts    = $budgetAlerts ?? [];

        $userSymbol = auth()->user()->currencySymbol();

        $tz  = config('app.timezone');
        $now = Carbon::now($tz);

        $periodStart = null;
        $periodEnd   = null;

        if (!empty($budgetStartDate) && !empty($budgetEndDate)) {
            try {
                $periodStart = Carbon::parse($budgetStartDate, $tz)->startOfDay();
                $periodEnd   = Carbon::parse($budgetEndDate,   $tz)->endOfDay();
            } catch (\Throwable $e) {
                $periodStart = null;
                $periodEnd   = null;
            }
        }

        $pick = function ($obj, array $fields) {
            foreach ($fields as $f) {
                if ($obj && isset($obj->{$f}) && $obj->{$f} !== null && $obj->{$f} !== '') return $obj->{$f};
            }
            return null;
        };

        if (!$periodStart || !$periodEnd) {
            try {
                $details = \App\Models\CustomerAccountDetails::query()
                    ->where(function ($q) {
                        $q->orWhere('customer_id', Auth::id())
                          ->orWhere('user_id', Auth::id());
                    })
                    ->latest('id')
                    ->first();

                $setupStartRaw = $pick($details, ['custom_start','custom_start_date','period_start','period_start_date','budget_start_date','budget_period_start','start_date']);
                $setupEndRaw   = $pick($details, ['custom_end','custom_end_date','period_end','period_end_date','budget_end_date','budget_period_end','end_date']);

                if (!empty($setupStartRaw) && !empty($setupEndRaw)) {
                    $periodStart = Carbon::parse($setupStartRaw, $tz)->startOfDay();
                    $periodEnd   = Carbon::parse($setupEndRaw,   $tz)->endOfDay();
                } else {
                    $selection   = $pick($details, ['period_selection','period_type','budget_period_type']);
                    $periodStart = $now->copy()->startOfMonth()->startOfDay();
                    $periodEnd   = $now->copy()->endOfMonth()->endOfDay();

                    switch ($selection) {
                        case 'last_working':
                            if ($periodEnd->isSaturday()) $periodEnd->subDay();
                            elseif ($periodEnd->isSunday()) $periodEnd->subDays(2);
                            break;
                        case 'fixed_date':
                            $renewalDay  = (int)($pick($details, ['renewal_date','renewal_day','fixed_day']) ?? 1);
                            $renewalDay  = max(1, min($renewalDay, $now->daysInMonth));
                            $anchor      = Carbon::create($now->year, $now->month, $renewalDay, 0, 0, 0, $tz);
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
                    }
                }
            } catch (\Throwable $e) {
                $periodStart = null;
                $periodEnd   = null;
            }
        }

        if (!$periodStart || !$periodEnd) {
            $periodStart = $now->copy()->startOfMonth()->startOfDay();
            $periodEnd   = $now->copy()->endOfMonth()->endOfDay();
        }

        if ($bankAccounts->isEmpty()) {
            try {
                $bankAccounts = \App\Models\BankAccount::query()
                    ->where(function ($q) {
                        $q->orWhere('user_id', Auth::id())
                          ->orWhere('customer_id', Auth::id());
                    })
                    ->get();
            } catch (\Throwable $e) {
                try {
                    $bankAccounts = \App\Models\BankAccount::where('user_id', Auth::id())->get();
                } catch (\Throwable $e2) {
                    $bankAccounts = collect();
                }
            }
        }

        if ($bankAccounts->isNotEmpty() && ((float)$networth) == 0.0) {
            $typeGroups = [
                'bank'        => ['current_account','current','bank','current account','checking'],
                'savings'     => ['savings_account','savings'],
                'credit_card' => ['credit_card','credit card','card'],
                'pension'     => ['pension','pensions'],
                'investment'  => ['investment','investment_account','investments','isa_account','isa','market','pea','pee'],
            ];
            $sumByTypes = function ($accounts, array $types) {
                return (float) $accounts->whereIn('account_type', $types)->sum('starting_balance');
            };
            $cashSavings           = $sumByTypes($bankAccounts, $typeGroups['bank']);
            $savingsAmount         = $sumByTypes($bankAccounts, $typeGroups['savings']);
            $credit_card           = $sumByTypes($bankAccounts, $typeGroups['credit_card']);
            $pensionAccountsTotal  = $sumByTypes($bankAccounts, $typeGroups['pension']);
            $investmentAmountTotal = $sumByTypes($bankAccounts, $typeGroups['investment']);
            $networth              = (float) $bankAccounts->sum('starting_balance');
        }
    @endphp

    <style>
        .periodBox{ padding: 16px 18px; }
        .periodText{ font-weight:700; font-size:1.05rem; letter-spacing:.2px; color:#e9f6f8; margin-bottom:.85rem; }
        .periodDatesPill{ display:inline-block; padding:.55rem .9rem; border-radius:12px; background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.08); font-weight:600; color:#cfe7eb; }
        .editBudgetPeriodBtn{ display:block; width:100%; padding:.65rem 1rem; border-radius:12px; border:1px solid rgba(255,255,255,.22); color:#ffffff; background:rgba(255,255,255,0.08); text-decoration:none; text-align:center; font-weight:600; font-size:.95rem; line-height:1.2; transition:all .15s ease; }
        .editBudgetPeriodBtn:hover{ color:#04262a; background:linear-gradient(90deg,#33BBC5,#44E0AC); border-color:transparent; text-decoration:none; }
        #resetBudgetModal .modal-title, #resetBudgetModal .modal-body { color:#fff !important; }

        .budgetAlertsWrap{ margin-bottom:8px; }
        .budgetAlert{ border-radius:16px; padding:18px 22px; margin-bottom:10px; animation:alertSlideIn 0.5s ease-out; backdrop-filter:blur(8px); }
        @keyframes alertSlideIn{ from{opacity:0;transform:translateY(-12px) scale(0.98);}to{opacity:1;transform:translateY(0) scale(1);} }
        .budgetAlertDanger{ background:linear-gradient(135deg,rgba(239,68,68,0.1),rgba(239,68,68,0.04)); border:1px solid rgba(239,68,68,0.18); }
        .budgetAlertWarning{ background:linear-gradient(135deg,rgba(250,190,88,0.1),rgba(250,190,88,0.04)); border:1px solid rgba(250,190,88,0.18); }
        .budgetAlertInfo{ background:linear-gradient(135deg,rgba(49,210,247,0.1),rgba(49,210,247,0.04)); border:1px solid rgba(49,210,247,0.18); }
        .alertIconWrap{ width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
        .alertIconDanger{ background:rgba(239,68,68,0.15); color:#ef4444; }
        .alertIconWarning{ background:rgba(250,190,88,0.15); color:#FABE58; }
        .alertIconInfo{ background:rgba(49,210,247,0.15); color:#31D2F7; }
        .alertTitle{ font-weight:700; font-size:0.9rem; color:#ffffff; margin-bottom:2px; }
        .alertDesc{ font-size:0.82rem; color:rgba(255,255,255,0.55); line-height:1.5; }
        .alertDesc a{ color:#31D2F7; text-decoration:underline; }
        .alertDesc strong{ color:rgba(255,255,255,0.8); }
        .alertBadge{ flex-shrink:0; font-weight:800; font-size:0.8rem; padding:5px 12px; border-radius:20px; letter-spacing:0.3px; }
        .alertBadgeDanger{ background:rgba(239,68,68,0.15); color:#ef4444; }
        .alertBadgeWarning{ background:rgba(250,190,88,0.15); color:#FABE58; }
        .alertBadgeInfo{ background:rgba(49,210,247,0.12); color:#31D2F7; }
        .alertProgress{ height:3px; border-radius:2px; margin-top:14px; background:rgba(255,255,255,0.04); overflow:hidden; }
        .alertProgressBar{ height:100%; border-radius:2px; transition:width 0.8s ease; }
        .alertProgressDanger{ background:linear-gradient(90deg,#ef4444,#ff6b6b); }
        .alertProgressWarning{ background:linear-gradient(90deg,#FABE58,#fcd34d); }

        body.light-mode .dashboardNextStepsBanner { background:#F0FDFA; border:1px solid #D1E7E0; border-radius:14px; margin-bottom:1rem; }
        body.light-mode .dashboardNextStepsBanner h4 { color:#1E293B !important; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox { background:#ffffff; border:1px solid #E2E8F0; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox .item { border-color:#E2E8F0 !important; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox .item a { color:#1E293B !important; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox .item .circle { border-color:#94A3B8 !important; background:#F1F5F9 !important; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox .item:hover { background:#F0FDFA; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox .item:hover .circle { border-color:#2DD4BF !important; background:rgba(45,212,191,0.1) !important; }
        body.light-mode main.dashboardMain .row > div { background-color:transparent !important; border:none !important; box-shadow:none !important; border-radius:0 !important; margin-bottom:0 !important; padding:0 !important; }
        body.light-mode main.dashboardMain .row > [class*="col-"] { padding-left:calc(var(--bs-gutter-x) * .5) !important; padding-right:calc(var(--bs-gutter-x) * .5) !important; margin-bottom:8px !important; }
        body.light-mode .mb-3 .infoBox.periodBox { background-color:#ffffff !important; border:1px solid #e5e7eb !important; box-shadow:0 1px 4px rgba(0,0,0,0.05) !important; border-radius:12px !important; padding:16px 18px !important; }
        body.light-mode .dashboardInfoBoxesBanner:not(.dashboardBottomBanner) .infoBox { background-color:#ffffff !important; border:1px solid #e5e7eb !important; box-shadow:0 2px 8px rgba(0,0,0,0.06) !important; border-radius:12px !important; padding:16px 18px !important; }
        body.light-mode .dashboardNextStepsBanner .nextStepsBox { background:#ffffff !important; border:1px solid #E2E8F0 !important; border-radius:12px !important; box-shadow:0 1px 3px rgba(0,0,0,0.05) !important; padding:1rem !important; }
        body.light-mode .dashboardBottomBanner.dashboardInfoBoxesBanner .col-lg-6 > div, body.light-mode .dashboardBottomBanner.dashboardInfoBoxesBanner .col-md-6 > div, body.light-mode .dashboardBottomBanner.dashboardInfoBoxesBanner div[class*="col-"] > div:not(.infoBox) { background-color:transparent !important; background:transparent !important; border:none !important; box-shadow:none !important; border-radius:0 !important; outline:none !important; padding:0 !important; }
        body.light-mode .dashboardBottomBanner .infoBox, body.light-mode .dashboardBottomBanner .infoBox.text-center { background-color:#ffffff !important; background:#ffffff !important; border:1px solid #e5e7eb !important; box-shadow:0 2px 8px rgba(0,0,0,0.06) !important; border-radius:12px !important; padding:18px 22px !important; }
        body.light-mode .dashboardBottomBanner h4 { color:#1E293B !important; }
        body.light-mode .dashboardBottomBanner .infoBox h4 { color:#1E293B !important; }
        body.light-mode .dashboardBottomBanner .infoBox h5 { color:#64748B !important; }
        body.light-mode .dashboardBottomBanner .infoBox p { color:#64748B !important; }
        body.light-mode .dashboardBottomBanner .infoBox p a { color:#0D9488 !important; }
        body.light-mode .dashboardBottomBanner .viewMoreDetailsBtn { color:#0D9488 !important; }
    </style>

    <script>
        (function(){
            function fixBottomBanner() {
                var banner = document.querySelector('.dashboardBottomBanner');
                if (!banner) return;
                var isLight = document.body.classList.contains('light-mode');
                banner.querySelectorAll('.col-lg-6 > div:not(.infoBox), .col-md-6 > div:not(.infoBox)').forEach(function(el){
                    if (isLight) { el.style.setProperty('background-color','transparent','important'); el.style.setProperty('background','transparent','important'); el.style.setProperty('border','none','important'); el.style.setProperty('box-shadow','none','important'); el.style.setProperty('border-radius','0','important'); el.style.setProperty('outline','none','important'); }
                    else { el.style.cssText = ''; }
                });
                banner.querySelectorAll('.infoBox').forEach(function(el){
                    if (isLight) { el.style.setProperty('background-color','#ffffff','important'); el.style.setProperty('background','#ffffff','important'); el.style.setProperty('border','1px solid #e5e7eb','important'); el.style.setProperty('box-shadow','0 2px 8px rgba(0,0,0,0.06)','important'); el.style.setProperty('border-radius','12px','important'); el.style.setProperty('padding','18px 22px','important'); }
                    else { el.style.cssText = ''; }
                });
            }
            if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', fixBottomBanner); } else { fixBottomBanner(); }
            setTimeout(fixBottomBanner, 100);
            setTimeout(fixBottomBanner, 500);
            var obs = new MutationObserver(function(){ fixBottomBanner(); });
            obs.observe(document.body, { attributes:true, attributeFilter:['class'] });
        })();
    </script>

    <section class="pageTitleBanner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-6"><h1>{{ __('messages.dashboard') }}</h1></div>
                <div class="col-6 text-end">
                    @if(Auth::user()->powens_user_token)
                        <form action="{{ route('powens.sync-transactions') }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="twoToneBlueGreenBtn">
                                <i class="fa-solid fa-rotate"></i> {{ __('messages.sync_transactions') }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Budget Alerts --}}
    @if(!empty($budgetAlerts))
        <section class="budgetAlertsWrap">
            <div class="container">
                @foreach($budgetAlerts as $alert)
                    @if($alert['level'] === 'danger')
                        <div class="budgetAlert budgetAlertDanger">
                            <div class="d-flex align-items-center gap-3">
                                <div class="alertIconWrap alertIconDanger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                                <div class="flex-grow-1">
                                    <div class="alertTitle">{{ ucfirst($alert['category']) }}</div>
                                    <div class="alertDesc">
                                        {{ $userSymbol }}{{ number_format($alert['spent'], 2) }} {{ __('messages.spent_on') }} {{ $userSymbol }}{{ number_format($alert['budget'], 2) }}
                                        — {{ __('messages.over_by') }} <strong>{{ $userSymbol }}{{ number_format($alert['over'] ?? 0, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="alertBadge alertBadgeDanger">{{ $alert['pct'] }}%</div>
                            </div>
                            <div class="alertProgress"><div class="alertProgressBar alertProgressDanger" style="width:100%"></div></div>
                        </div>
                    @elseif($alert['level'] === 'warning')
                        <div class="budgetAlert budgetAlertWarning">
                            <div class="d-flex align-items-center gap-3">
                                <div class="alertIconWrap alertIconWarning"><i class="fa-solid fa-bell"></i></div>
                                <div class="flex-grow-1">
                                    <div class="alertTitle">{{ ucfirst($alert['category']) }}</div>
                                    <div class="alertDesc">
                                        {{ $userSymbol }}{{ number_format($alert['spent'], 2) }} {{ __('messages.spent_on') }} {{ $userSymbol }}{{ number_format($alert['budget'], 2) }}
                                        — {{ __('messages.left') }} <strong>{{ $userSymbol }}{{ number_format($alert['remaining'] ?? 0, 2) }}</strong>
                                    </div>
                                </div>
                                <div class="alertBadge alertBadgeWarning">{{ $alert['pct'] }}%</div>
                            </div>
                            <div class="alertProgress"><div class="alertProgressBar alertProgressWarning" style="width:{{ min(100, $alert['pct']) }}%"></div></div>
                        </div>
                    @elseif($alert['level'] === 'info')
                        <div class="budgetAlert budgetAlertInfo">
                            <div class="d-flex align-items-center gap-3">
                                <div class="alertIconWrap alertIconInfo"><i class="fa-solid fa-circle-info"></i></div>
                                <div class="flex-grow-1">
                                    <div class="alertTitle">{{ ucfirst($alert['category']) }}</div>
                                    <div class="alertDesc">
                                        {{ $userSymbol }}{{ number_format($alert['spent'], 2) }} {{ __('messages.uncategorized_expenses') }}
                                        <a href="{{ route('transactions.index') }}">{{ __('messages.categorize_them') }}</a> {{ __('messages.for_better_tracking') }}
                                    </div>
                                </div>
                                <div class="alertBadge alertBadgeInfo">{{ $userSymbol }}{{ number_format($alert['spent'], 2) }}</div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    @if ($transactions->isEmpty() || $bankAccounts->isEmpty())
        <section class="dashboardNextStepsBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h4 class="mb-3">{{ __('messages.next_steps') }}</h4>
                        <div class="nextStepsBox">
                            @if ($transactions->isEmpty())
                                <div class="item"><a href="{{ route('transactions.index') }}"><div class="circle"></div> {{ __('messages.add_transactions') }}</a></div>
                            @endif
                            @if ($bankAccounts->isEmpty())
                                <div class="item"><a href="{{ route('bank-accounts.index') }}"><div class="circle"></div> {{ __('messages.add_bank_account') }}</a></div>
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
                        <div class="periodText">{{ __('messages.budget_period') }}</div>
                        <div class="periodDatesPill mb-3">
                            {{ $periodStart->isoFormat('ddd DD MMM') }} – {{ $periodEnd->isoFormat('ddd DD MMM') }}
                        </div>
                        <a href="#" class="editBudgetPeriodBtn" data-bs-toggle="modal" data-bs-target="#resetBudgetModal" title="{{ __('messages.edit_budget_period') }}">
                            {{ __('messages.edit_budget_period') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboardInfoBoxesBanner">
        <div class="container">
            <div class="row align-items-end mb-md-0 mb-2">
                <div class="col-lg-6">
                    <h4 class="mb-3">{{ __('messages.current_balances') }}</h4>
                    <div class="infoBox">
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>{{ __('messages.bank') }}</strong></div>
                            <div class="col-6 d-flex justify-content-end">{{ $userSymbol }} {{ number_format((float)$cashSavings, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>{{ __('messages.savings') }}</strong></div>
                            <div class="col-6 d-flex justify-content-end">{{ $userSymbol }} {{ number_format((float)$savingsAmount, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>{{ __('messages.investments') }}</strong></div>
                            <div class="col-6 d-flex justify-content-end">{{ $userSymbol }} {{ number_format((float)$investmentAmountTotal, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>{{ __('messages.pensions') }}</strong></div>
                            <div class="col-6 d-flex justify-content-end">{{ $userSymbol }} {{ number_format((float)$pensionAccountsTotal, 2) }}</div>
                        </div>
                        <div class="row align-items-center mb-lg-0 mb-2">
                            <div class="col-6"><strong>{{ __('messages.credit_card') }}</strong></div>
                            <div class="col-6 d-flex justify-content-end">{{ $userSymbol }} {{ number_format((float)$credit_card, 2) }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="infoBox text-center">
                        <h2>{{ $userSymbol }}{{ number_format((float)$networth, 2) }}</h2>
                        <h5>{{ __('messages.net_worth') }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('customer.pages.partials.saving-goals-widget')

    <section class="dashboardInfoBoxesBanner dashboardBottomBanner">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-md-0 mb-2">
                    <div class="row px-0 align-items-center mb-md-4">
                        <div class="col-8"><h4>{{ __('messages.income_and_expenses') }}</h4></div>
                        @if ($transactions->isNotEmpty())
                            <div class="col-4"><a href="{{ route('transactions.index') }}" class="viewMoreDetailsBtn">{{ __('messages.view_all') }}</a></div>
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
                                {{ __('messages.income_expenses_empty') }}
                                <a href="{{ route('transactions.create') }}">{{ __('messages.add_transactions') }}</a>
                                {{ __('messages.or') }} <a href="{{ route('recurring-payments.index') }}">{{ __('messages.recurring_payments') }}</a>.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="row px-0 align-items-center mb-md-4">
                        <div class="col-8"><h4>{{ __('messages.remaining_budget') }}</h4></div>
                        @if ($remainingBudget && $amountSpent)
                            <div class="col-4"><a href="{{ route('budget.index') }}" class="viewMoreDetailsBtn">{{ __('messages.details') }}</a></div>
                        @endif
                    </div>
                    <div class="infoBox text-center">
                        @if ($remainingBudget && $amountSpent)
                            <canvas id="myDoughnutChart" data-remaining="{{ $remainingBudget }}" data-total="{{ $totalBudget }}"></canvas>
                            <h4 class="mb-2">
                                <span id="remainingAmount" style="color:#44E0AC">
                                    {{ $userSymbol }}{{ number_format((float)$remainingBudget, 2) }}
                                </span> {{ __('messages.clearcash_remaining') }}
                            </h4>
                            <h5>{{ $userSymbol }}{{ number_format((float)$amountSpent, 2) }} {{ __('messages.spent_of_total') }} {{ $userSymbol }}{{ number_format((float)$totalBudget, 2) }}</h5>
                        @else
                            <p>
                                {{ __('messages.budget_empty') }}
                                <a href="{{ route('transactions.create') }}">{{ __('messages.add_transactions') }}</a>
                                {{ __('messages.or') }} <a href="{{ route('recurring-payments.index') }}">{{ __('messages.recurring_payments') }}</a>.
                            </p>
                        @endif
                    </div>
                </div>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

    <script>
        if (typeof Chart !== 'undefined' && typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }

        var currencySymbol = @json($userSymbol);
        var totalAmount    = {{ $totalBudget ? (float)$totalBudget : 1000 }};
        var amountSpent    = {{ $amountSpent ? (float)$amountSpent : 0 }};

        if (totalAmount <= 0) totalAmount = 1;

        var remainingAmount  = totalAmount - amountSpent;
        var spentPercentage  = (remainingAmount < 0) ? 100 : ((amountSpent / totalAmount) * 100);
        spentPercentage      = Math.min(Math.max(spentPercentage, 0), 100);

        var doughnutEl = document.getElementById('myDoughnutChart');
        if (doughnutEl && typeof Chart !== 'undefined') {
            var ctx = doughnutEl.getContext('2d');
            new Chart(ctx, {
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
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true },
                        datalabels: { display: false }
                    }
                }
            });

            var remainingEl = document.getElementById('remainingAmount');
            if (remainingEl) remainingEl.style.color = remainingAmount < 0 ? '#ff4d4d' : '#44E0AC';
        }

        document.addEventListener("DOMContentLoaded", function() {
            if (typeof Chart === 'undefined') return;

            const income   = {{ (float)$income }};
            const expense  = {{ (float)$expense }};
            const maxValue = Math.max(income, expense);

            var incomeLabel  = @json(__('messages.income'));
            var expenseLabel = @json(__('messages.expenses'));

            if (document.getElementById('incomeChart'))  renderChart('incomeChart',  incomeLabel,  income,  '#44E0AC');
            if (document.getElementById('expenseChart')) renderChart('expenseChart', expenseLabel, expense, '#31D2F7');

            function renderChart(canvasId, label, amount, color) {
                const ctx = document.getElementById(canvasId).getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: [label],
                        datasets: [{ label: label, data: [amount], backgroundColor: color, borderRadius: 10, barThickness: 120 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            datalabels: {
                                align: 'top', anchor: 'end',
                                backgroundColor: 'white', borderRadius: 12,
                                padding: { top: 10, bottom: 4, left: 10, right: 10 },
                                color: 'black', font: { weight: 'bold' },
                                formatter: (value) => currencySymbol + value.toLocaleString('it-IT', { minimumFractionDigits: 2 })
                            }
                        },
                        scales: {
                            x: { grid: { display: false }, ticks: { color: 'white', font: { size: 14, weight: 'bold' } } },
                            y: { display: false, min: 0, max: maxValue + 100, grid: { display: false }, ticks: { color: 'white', font: { size: 14, weight: 'bold' }, stepSize: Math.ceil((maxValue + 100) / 5) } }
                        },
                        layout: { padding: { top: 40, bottom: 20 } }
                    }
                });
            }
        });
    </script>
@endsection
