{{-- resources/views/customer/pages/bank-accounts/index.blade.php --}}
@extends('layouts.customer')

@section('styles_in_head')
    <style>
        header, .customerHeader, .contentTopBar, .page-header, .navbar {
            background: #0f2222 !important;
            border: 0 !important;
            box-shadow: none !important;
        }
        main.dashboardMain h1 { margin-top: 0 !important; }

        .ccModalFooterActions{
            width:100%;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:16px;
        }
        .ccModalFooterActions form{ margin:0; }
        .ccCancelBtn{
            padding:10px 18px;
            border-radius:12px;
            font-weight:800;
            height:44px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
        }

        /* ========== EMPTY STATE ========== */
        .emptyStateWrap{
            padding: 40px 20px 50px;
            text-align: center;
        }
        .emptyStateHero{
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .emptyStateHero::before{
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(68,224,172,0.12) 0%, rgba(49,210,247,0.06) 50%, transparent 70%);
            animation: emptyPulse 3s ease-in-out infinite;
        }
        @keyframes emptyPulse{
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.08); opacity: 1; }
        }
        .emptyStateHero .heroIcon{
            font-size: 64px;
            background: linear-gradient(135deg, #44E0AC, #31D2F7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }
        .emptyStateHero .orbitIcon{
            position: absolute;
            font-size: 22px;
            color: rgba(255,255,255,0.5);
            animation: orbit 8s linear infinite;
        }
        .emptyStateHero .orbitIcon:nth-child(2){ animation-delay: 0s; }
        .emptyStateHero .orbitIcon:nth-child(3){ animation-delay: -2.67s; }
        .emptyStateHero .orbitIcon:nth-child(4){ animation-delay: -5.33s; }

        @keyframes orbit{
            0%   { transform: rotate(0deg)   translateX(80px) rotate(0deg);   opacity: 0.4; }
            50%  { opacity: 0.8; }
            100% { transform: rotate(360deg) translateX(80px) rotate(-360deg); opacity: 0.4; }
        }

        .emptyStateTitle{
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            letter-spacing: -0.3px;
        }
        .emptyStateDesc{
            color: rgba(255,255,255,0.6);
            font-size: 1.05rem;
            max-width: 480px;
            margin: 0 auto 36px;
            line-height: 1.6;
        }

        .emptyStateCtas{
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 48px;
        }
        .ctaPrimary{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 28px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            background: linear-gradient(135deg, #44E0AC, #31D2F7);
            color: #04262a;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(68,224,172,0.2);
        }
        .ctaPrimary:hover{
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(68,224,172,0.3);
            color: #04262a;
            text-decoration: none;
        }
        .ctaSecondary{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 28px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            background: rgba(255,255,255,0.06);
            color: #ffffff;
            border: 1px solid rgba(255,255,255,0.12);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .ctaSecondary:hover{
            background: rgba(255,255,255,0.1);
            border-color: rgba(68,224,172,0.3);
            transform: translateY(-2px);
            color: #ffffff;
            text-decoration: none;
        }

        /* Feature cards */
        .featuresGrid{
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            max-width: 720px;
            margin: 0 auto;
        }
        @media (max-width: 768px){
            .featuresGrid{ grid-template-columns: 1fr; max-width: 360px; }
        }
        .featureCard{
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 16px;
            padding: 24px 16px;
            transition: all 0.25s ease;
        }
        .featureCard:hover{
            background: rgba(68,224,172,0.04);
            border-color: rgba(68,224,172,0.15);
            transform: translateY(-3px);
        }
        .featureCard .featureIcon{
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 20px;
        }
        .featureCard:nth-child(1) .featureIcon{ background: rgba(68,224,172,0.12); color: #44E0AC; }
        .featureCard:nth-child(2) .featureIcon{ background: rgba(49,210,247,0.12); color: #31D2F7; }
        .featureCard:nth-child(3) .featureIcon{ background: rgba(250,190,88,0.12); color: #FABE58; }

        .featureCard h6{
            color: #ffffff;
            font-weight: 700;
            font-size: 0.9rem;
            margin-bottom: 6px;
        }
        .featureCard p{
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            margin: 0;
            line-height: 1.4;
        }

        /* Divider */
        .emptyDivider{
            display: flex;
            align-items: center;
            gap: 16px;
            max-width: 400px;
            margin: 0 auto 36px;
        }
        .emptyDivider::before,
        .emptyDivider::after{
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }
        .emptyDivider span{
            color: rgba(255,255,255,0.35);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Security badge */
        .securityBadge{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(68,224,172,0.06);
            border: 1px solid rgba(68,224,172,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 0.8rem;
            margin-top: 32px;
        }
        .securityBadge i{ color: #44E0AC; }
    </style>
@endsection

@php
    $currencyOptions = ['GBP' => '🇬🇧 GBP — £', 'EUR' => '🇪🇺 EUR — €', 'USD' => '🇺🇸 USD — $', 'CHF' => '🇨🇭 CHF', 'JPY' => '🇯🇵 JPY — ¥'];
    $userBaseCurrency = auth()->user()->base_currency ?? 'GBP';
@endphp

@section('content')
    <section class="pageTitleBanner">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1>{{ __('messages.bank_accounts') }}</h1>
                </div>
            </div>
        </div>
    </section>

    @if ($bankAccounts->isNotEmpty())
        <section class="addBanner">
            <div class="container">
                <div class="row">
                    <div class="col-12 d-flex flex-wrap align-items-center gap-3">
                        <button type="button"
                                class="twoToneBlueGreenBtn"
                                data-bs-toggle="modal"
                                data-bs-target="#addBankAccountModal">
                            {{ __('messages.add_account') }}
                        </button>

                        @include('customer.pages.bank-accounts._plaid_link')
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="bankAccountsMainList">
        <div class="container">
            <div class="row">
                <div class="col-12">

                    @if ($bankAccounts->isNotEmpty())
                        @foreach ($bankAccounts as $account)
                            @php $symbol = $account->currencySymbol(); @endphp
                            <div class="bankItem">
                                <button class="modalBtn" type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#acc_{{ $account->id }}">
                                    <div class="row align-items-center">
                                        <div class="col-8">
                                            <h4 class="mb-1">{{ $account->account_name }}</h4>
                                            <h6 class="m-0">{{ str_replace('_', ' ', $account->account_type) }}</h6>
                                        </div>
                                        <div class="col-4 text-end">
                                            <div class="balance">
                                                {{ $symbol }}{{ number_format($account->current_balance ?? $account->starting_balance, 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </button>

                                <div class="modal fade" id="acc_{{ $account->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>

                                            <div class="modal-body">
                                                <h1 class="mb-3">{{ __('messages.edit_account') }} {{ $account->account_name }}</h1>

                                                <form action="{{ route('bank-accounts.update', $account->id) }}" method="post" class="mb-4">
                                                    @csrf
                                                    @method('put')

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="name_of_bank_account_{{ $account->id }}">{{ __('messages.bank_name') }}</label>
                                                            <input type="text"
                                                                   name="name_of_bank_account"
                                                                   id="name_of_bank_account_{{ $account->id }}"
                                                                   value="{{ old('name_of_bank_account', $account->account_name) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="bank_account_type_{{ $account->id }}">{{ __('messages.account_type') }}</label>
                                                            <select name="bank_account_type" id="bank_account_type_{{ $account->id }}">
                                                                <option value="current_account"    @selected($account->account_type == 'current_account')>{{ __('messages.current_account') }}</option>
                                                                <option value="savings_account"    @selected($account->account_type == 'savings_account')>{{ __('messages.savings_account') }}</option>
                                                                <option value="isa_account"        @selected($account->account_type == 'isa_account')>{{ __('messages.isa_account') }}</option>
                                                                <option value="investment_account" @selected($account->account_type == 'investment_account')>{{ __('messages.investment_account') }}</option>
                                                                <option value="pension"            @selected($account->account_type == 'pension')>{{ __('messages.pension') }}</option>
                                                                <option value="investment"         @selected($account->account_type == 'investment')>{{ __('messages.investment') }}</option>
                                                                <option value="credit_card"        @selected($account->account_type == 'credit_card')>{{ __('messages.credit_card') }}</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-3">
                                                        <div class="col-12">
                                                            <label for="currency_{{ $account->id }}">{{ __('messages.currency') }}</label>
                                                            <select name="currency" id="currency_{{ $account->id }}">
                                                                @foreach ($currencyOptions as $code => $label)
                                                                    <option value="{{ $code }}" @selected(($account->currency ?? $userBaseCurrency) === $code)>
                                                                        {{ $label }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-4">
                                                        <div class="col-12">
                                                            <label for="bank_account_starting_balance_{{ $account->id }}">{{ __('messages.balance') }}</label>
                                                            <input type="number"
                                                                   step="any"
                                                                   name="bank_account_starting_balance"
                                                                   id="bank_account_starting_balance_{{ $account->id }}"
                                                                   value="{{ old('bank_account_starting_balance', $account->starting_balance) }}">
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <div class="col-md-4 offset-md-8 d-md-flex justify-content-md-end">
                                                            <button type="submit" class="twoToneBlueGreenBtn text-center py-2">
                                                                {{ __('messages.update') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>

                                                @php $recent = $account->recentTransactions ?? collect(); @endphp

                                                <div class="transactionList">
                                                    <h4 class="mb-3 fw-semibold text-white">{{ __('messages.recent_transactions') }}</h4>
                                                    <ul class="list-group">
                                                        <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                            style="background-color:#d1f9ff0d;border:none;">
                                                            <div class="d-flex flex-column">
                                                                <span class="fs-5 fw-semibold text-white">{{ __('messages.starting_balance') }}</span>
                                                                <small class="text-white">
                                                                    {{ $account->created_at ? \Carbon\Carbon::parse($account->created_at)->format('d M, Y') : '' }}
                                                                </small>
                                                            </div>
                                                            <span class="badge bg-secondary fs-6">
                                                                {{ $symbol }}{{ number_format((float) $account->starting_balance, 2) }}
                                                            </span>
                                                        </li>

                                                        @if ($recent->isNotEmpty())
                                                            @foreach ($recent as $transaction)
                                                                @php
                                                                    $isIncome = $transaction->transaction_type === 'income';
                                                                    $txSymbol = $transaction->currency
                                                                        ? match($transaction->currency) {
                                                                            'GBP' => '£', 'EUR' => '€', 'USD' => '$',
                                                                            'JPY' => '¥', default => $transaction->currency
                                                                          }
                                                                        : $symbol;
                                                                    $amount = number_format((float) $transaction->amount_native ?? $transaction->amount, 2);
                                                                @endphp
                                                                <li class="list-group-item d-flex justify-content-between align-items-center my-1"
                                                                    style="background-color:#d1f9ff0d;border:none;">
                                                                    <div class="d-flex flex-column">
                                                                        <span class="fs-5 fw-semibold text-white">
                                                                            {{ $transaction->name ?? $transaction->description ?? __('messages.unnamed') }}
                                                                        </span>
                                                                        <small class="text-white">
                                                                            {{ \Carbon\Carbon::parse($transaction->date)->format('d M, Y') }}
                                                                        </small>
                                                                    </div>
                                                                    <span class="badge {{ $isIncome ? 'bg-success' : 'bg-danger' }} fs-6">
                                                                        {{ $isIncome ? $txSymbol.'+' : $txSymbol }}{{ $amount }}
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        @else
                                                            <li class="list-group-item d-flex justify-content-between align-items-center"
                                                                style="background-color:#d1f9ff0d;border:none;">
                                                                <div class="d-flex flex-column">
                                                                    <span class="text-white">{{ __('messages.no_transactions_for_account') }}</span>
                                                                </div>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="modal-footer">
                                                <div class="ccModalFooterActions">
                                                    <form id="deleteBankAccountForm-{{ $account->id }}"
                                                          action="{{ route('bank-accounts.destroy', $account->id) }}"
                                                          method="post">
                                                        @csrf
                                                        @method('delete')
                                                        <button type="button"
                                                                class="dangerBtn confirmDeleteBankAccountBtn"
                                                                data-form-id="deleteBankAccountForm-{{ $account->id }}">
                                                            {{ __('messages.delete') }}
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                            class="twoToneBlueGreenBtn ccCancelBtn"
                                                            data-bs-dismiss="modal">
                                                        {{ __('messages.cancel') }}
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- ========== EMPTY STATE ========== --}}
                        <div class="emptyStateWrap">

                            <div class="emptyStateHero">
                                <i class="fa-solid fa-building-columns heroIcon"></i>
                                <i class="fa-solid fa-credit-card orbitIcon"></i>
                                <i class="fa-solid fa-piggy-bank orbitIcon"></i>
                                <i class="fa-solid fa-wallet orbitIcon"></i>
                            </div>

                            <h2 class="emptyStateTitle">{{ __('messages.connect_bank_accounts') }}</h2>
                            <p class="emptyStateDesc">
                                {{ __('messages.connect_bank_desc') }}
                            </p>

                            <div class="emptyStateCtas">
                                <a href="{{ route('powens.connect') }}" class="ctaPrimary">
                                    <i class="fa-solid fa-link"></i> {{ __('messages.connect_your_bank') }}
                                </a>
                                <button type="button" class="ctaSecondary" data-bs-toggle="modal" data-bs-target="#addBankAccountModal">
                                    <i class="fa-solid fa-plus"></i> {{ __('messages.add_manually') }}
                                </button>
                            </div>

                            <div class="emptyDivider">
                                <span>{{ __('messages.why_connect') }}</span>
                            </div>

                            <div class="featuresGrid">
                                <div class="featureCard">
                                    <div class="featureIcon"><i class="fa-solid fa-bolt"></i></div>
                                    <h6>{{ __('messages.auto_sync') }}</h6>
                                    <p>{{ __('messages.auto_sync_desc') }}</p>
                                </div>
                                <div class="featureCard">
                                    <div class="featureIcon"><i class="fa-solid fa-chart-pie"></i></div>
                                    <h6>{{ __('messages.full_overview') }}</h6>
                                    <p>{{ __('messages.full_overview_desc') }}</p>
                                </div>
                                <div class="featureCard">
                                    <div class="featureIcon"><i class="fa-solid fa-shield-halved"></i></div>
                                    <h6>{{ __('messages.safe_secure') }}</h6>
                                    <p>{{ __('messages.safe_secure_desc') }}</p>
                                </div>
                            </div>

                            <div class="securityBadge">
                                <i class="fa-solid fa-lock"></i>
                                {{ __('messages.psd2_badge') }}
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </section>

    {{-- Modal: Aggiungi conto bancario --}}
    <div class="modal fade" id="addBankAccountModal" tabindex="-1" aria-labelledby="addBankAccountModal"
         aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('messages.close') }}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="modal-body">
                    <h1>{{ __('messages.add_bank_account') }}</h1>

                    <form action="{{ route('bank-accounts.store') }}" method="post">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="name_of_bank_account">{{ __('messages.bank_name') }}</label>
                                <input type="text" name="name_of_bank_account" id="name_of_bank_account" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="bank_account_type">{{ __('messages.account_type') }}</label>
                                <select name="bank_account_type" id="bank_account_type" required
                                        onchange="toggleNote(this.value)">
                                    <option value="" disabled selected>{{ __('messages.select_option') }}</option>
                                    <option value="current_account">{{ __('messages.current_account') }}</option>
                                    <option value="savings_account">{{ __('messages.savings_account') }}</option>
                                    <option value="isa_account">{{ __('messages.isa_account') }}</option>
                                    <option value="investment_account">{{ __('messages.investment_account') }}</option>
                                    <option value="pension">{{ __('messages.pension') }}</option>
                                    <option value="investment">{{ __('messages.investment') }}</option>
                                    <option value="credit_card">{{ __('messages.credit_card') }}</option>
                                </select>
                                <small class="text-white creditNote" id="creditNote" style="display:none;">
                                    {{ __('messages.credit_card_note') }}
                                </small>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <label for="currency">{{ __('messages.currency') }}</label>
                                <select name="currency" id="currency" required>
                                    @foreach ($currencyOptions as $code => $label)
                                        <option value="{{ $code }}" @selected($userBaseCurrency === $code)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="bank_account_starting_balance">{{ __('messages.starting_balance') }}</label>
                                <input type="number" step="any" name="bank_account_starting_balance"
                                       id="bank_account_starting_balance" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.add_account') }}</button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <script>
        function toggleNote(value) {
            const note = document.getElementById("creditNote");
            if (note) note.style.display = (value === "credit_card") ? "block" : "none";
        }

        (function wireFloatingPlusToAddBankAccountModal() {
            function hasBootstrapModal() {
                return window.bootstrap && window.bootstrap.Modal;
            }

            function openAddBankModal() {
                const modalEl = document.getElementById('addBankAccountModal');
                if (!modalEl) return false;
                if (!hasBootstrapModal()) return false;
                window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
                return true;
            }

            function findFloatingFab() {
                const candidates = Array.from(document.querySelectorAll('button, a, [role="button"], div'));
                let best = null;
                let bestScore = Infinity;

                for (const el of candidates) {
                    const cs = window.getComputedStyle(el);
                    if (cs.position !== 'fixed') continue;

                    const rect = el.getBoundingClientRect();
                    const distRight  = window.innerWidth  - rect.right;
                    const distBottom = window.innerHeight - rect.bottom;

                    if (distRight < -5 || distBottom < -5) continue;
                    if (distRight > 140 || distBottom > 140) continue;
                    if (rect.width < 30 || rect.height < 30) continue;
                    if (rect.width > 120 || rect.height > 120) continue;

                    const hasIcon =
                        el.querySelector('.fa-plus, .fa-circle-plus, .fa-xmark, .fa-times, .bi-plus') ||
                        /add|plus/i.test((el.getAttribute('aria-label') || '') + ' ' + (el.getAttribute('title') || ''));

                    if (!hasIcon) continue;

                    const score = distRight + distBottom;
                    if (score < bestScore) {
                        bestScore = score;
                        best = el;
                    }
                }

                return best;
            }

            document.addEventListener('DOMContentLoaded', function () {
                const fab = findFloatingFab();
                if (!fab) return;

                if (fab.dataset.ccWired === '1') return;
                fab.dataset.ccWired = '1';

                fab.addEventListener('click', function (e) {
                    const opened = openAddBankModal();
                    if (opened) {
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }, true);
            });
        })();

        document.addEventListener('DOMContentLoaded', function () {
            var swalTitle   = @json(__('messages.are_you_sure'));
            var swalConfirm = @json(__('messages.yes_delete'));
            var swalCancel  = @json(__('messages.no_cancel'));

            document.querySelectorAll('.confirmDeleteBankAccountBtn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();

                    const formId = btn.getAttribute('data-form-id');
                    const form = formId ? document.getElementById(formId) : null;
                    if (!form) return;

                    var message = @json(__('messages.confirm_delete_account'));

                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            icon: 'warning',
                            title: swalTitle,
                            text: message,
                            showCancelButton: true,
                            confirmButtonText: swalConfirm,
                            cancelButtonText: swalCancel,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    } else {
                        if (window.confirm(message)) {
                            form.submit();
                        }
                    }
                });
            });
        });
    </script>
@endsection
