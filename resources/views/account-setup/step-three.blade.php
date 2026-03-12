@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        header, aside.sidebar { display:none; }
        main.dashboardMain { padding-top:2rem; width:100%; }
        main.dashboardMain.full { padding-top:2rem; }
        .setupStepsWrapper h1 { font-size:32px; }
        @media (min-width: 1200px) { h1,.h1 { font-size:2.25rem; } }
        .setupStepsWrapper form .expensesWrap {
            background-color:#d1f9ff0d;border-radius:8px;margin-bottom:1.5rem;padding:1.8rem 3.5rem;
        }
        .cat-icon { color:#44E0AC; margin-right:8px; width:20px; text-align:center; }
        .alert-danger {
            background: rgba(210,20,20,0.15) !important;
            border: 1px solid rgba(210,20,20,0.3) !important;
            color: #ff6b6b !important;
            border-radius: 8px;
        }
        .errorsBanner ul li, .errorsBanner ul { color:#ff6b6b !important; font-weight:600; font-size:1rem; }
        .errorsBanner { background:rgba(210,20,20,0.12); border-radius:10px; padding:12px 16px; margin-bottom:1rem; }
        @media only screen and (max-width:767px) {
            main.dashboardMain { padding:3rem; }
            .setupStepsWrapper h1 { font-size:25px; line-height:30px; }
            .setupStepsWrapper p { font-size:.9rem; }
        }
        @media only screen and (max-width:480px) {
            main.dashboardMain { padding:2.5rem; }
            .setupStepsWrapper h1 { font-size:22px; }
            .setupStepsWrapper p { font-size:.85rem; }
        }

        /* ── Currency overlay – dark (default) ── */
        #currencyOverlay {
            position:fixed;inset:0;background:rgba(0,0,0,0.75);
            z-index:99999;display:flex;align-items:center;justify-content:center;
        }
        #currencyCard {
            background:#0f2629;
            border:1px solid rgba(255,255,255,0.08);
            border-radius:20px;width:100%;max-width:420px;margin:16px;overflow:hidden;
        }
        #currencyCardHeader {
            border-bottom:1px solid rgba(255,255,255,0.06);
            padding:20px 24px 16px;display:flex;align-items:center;gap:10px;
        }
        #currencyCardHeader h5 { margin:0;color:#fff;font-weight:800;font-size:1.1rem; }
        #currencyCardHeader .loc-icon { color:#44E0AC; }
        #currencyCardBody { padding:24px; }
        #detectingState { text-align:center;padding:20px 0;color:rgba(255,255,255,0.4);font-size:0.85rem; }
        #detectedState p { text-align:center;color:rgba(255,255,255,0.7);font-size:0.95rem;line-height:1.6;margin-bottom:0; }
        #detectedCountryName { color:#fff; }
        #detectedCurrencyLabel { color:#44E0AC; }
        #currencyIconWrap {
            width:52px;height:52px;border-radius:14px;background:rgba(68,224,172,0.12);
            display:flex;align-items:center;justify-content:center;margin:0 auto 16px;
        }
        #currencyIconWrap i { color:#44E0AC;font-size:1.4rem; }
        #currencyManualWrap {
            margin-top:16px;background:rgba(255,255,255,0.03);
            border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:16px;
        }
        #currencyManualWrap label {
            color:rgba(255,255,255,0.5);font-size:0.78rem;text-transform:uppercase;
            letter-spacing:0.5px;margin-bottom:8px;display:block;
        }
        #currencyModalSelect {
            width:100%;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);
            border-radius:10px;color:#fff;padding:10px 14px;font-size:0.95rem;
        }
        #currencyModalSelect option { background:#0f2629; color:#fff; }
        #overlayFooter {
            display:none;padding:14px 24px;
            border-top:1px solid rgba(255,255,255,0.06);
            justify-content:space-between;align-items:center;
        }
        #skipCurrencyBtn {
            padding:10px 20px;border-radius:12px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.6);
            font-weight:600;font-size:0.9rem;cursor:pointer;transition:all .15s;
        }
        #skipCurrencyBtn:hover { background:rgba(255,255,255,0.12) !important; color:#fff !important; }
        #confirmCurrencyBtn {
            padding:10px 24px;border-radius:12px;border:none;
            background:linear-gradient(90deg,#33BBC5,#44E0AC);color:#04262a;
            font-weight:800;font-size:0.9rem;cursor:pointer;transition:all .15s;
        }
        #confirmCurrencyBtn:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(68,224,172,0.3); }

        /* ── Currency overlay – light mode overrides ── */
        body.light-mode #currencyOverlay { background:rgba(0,0,0,0.35); }
        body.light-mode #currencyCard { background:#ffffff; border:1px solid rgba(0,0,0,0.1); box-shadow:0 20px 60px rgba(0,0,0,0.15); }
        body.light-mode #currencyCardHeader { border-bottom:1px solid rgba(0,0,0,0.08); }
        body.light-mode #currencyCardHeader h5 { color:#0D2020; }
        body.light-mode #currencyCardHeader .loc-icon { color:#0D9488; }
        body.light-mode #detectingState { color:rgba(0,0,0,0.4); }
        body.light-mode #detectedState p { color:rgba(13,32,32,0.65); }
        body.light-mode #detectedCountryName { color:#0D2020; }
        body.light-mode #detectedCurrencyLabel { color:#0D9488; }
        body.light-mode #currencyIconWrap { background:rgba(13,148,136,0.1); }
        body.light-mode #currencyIconWrap i { color:#0D9488; }
        body.light-mode .flagBadge { background:rgba(13,148,136,0.1) !important; border-color:rgba(13,148,136,0.3) !important; color:#0D9488 !important; }
        body.light-mode #currencyManualWrap { background:rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.08); }
        body.light-mode #currencyManualWrap label { color:rgba(13,32,32,0.45); }
        body.light-mode #currencyModalSelect { background:#f8fafa; border:1px solid rgba(0,0,0,0.12); color:#0D2020; }
        body.light-mode #currencyModalSelect option { background:#fff; color:#0D2020; }
        body.light-mode #overlayFooter { border-top:1px solid rgba(0,0,0,0.08); }
        body.light-mode #skipCurrencyBtn { background:#f1f5f5; border:1px solid rgba(0,0,0,0.1); color:#374151; }
        body.light-mode #skipCurrencyBtn:hover { background:#e5eaea !important; color:#0D2020 !important; }
    </style>

    @php
        $iconMap = [
            'affitto'            => 'fa-solid fa-house',
            'mutuo'              => 'fa-solid fa-building-columns',
            'bollette'           => 'fa-solid fa-bolt',
            'spesa alimentare'   => 'fa-solid fa-cart-shopping',
            'ristoranti'         => 'fa-solid fa-utensils',
            'trasporti'          => 'fa-solid fa-bus',
            'carburante'         => 'fa-solid fa-gas-pump',
            'abbigliamento'      => 'fa-solid fa-shirt',
            'salute'             => 'fa-solid fa-heart-pulse',
            'farmacia'           => 'fa-solid fa-prescription-bottle-medical',
            'assicurazioni'      => 'fa-solid fa-shield-halved',
            'telefono e internet'=> 'fa-solid fa-wifi',
            'abbonamenti'        => 'fa-solid fa-rotate',
            'intrattenimento'    => 'fa-solid fa-film',
            'viaggi'             => 'fa-solid fa-plane',
            'istruzione'         => 'fa-solid fa-graduation-cap',
            'cura personale'     => 'fa-solid fa-spa',
            'casa e manutenzione'=> 'fa-solid fa-screwdriver-wrench',
            'regali'             => 'fa-solid fa-gift',
            'donazioni'          => 'fa-solid fa-hand-holding-heart',
            'tasse'              => 'fa-solid fa-file-invoice-dollar',
            'risparmi'           => 'fa-solid fa-piggy-bank',
            'investimenti'       => 'fa-solid fa-chart-line',
            'animali domestici'  => 'fa-solid fa-paw',
            'figli'              => 'fa-solid fa-baby',
            'sport e fitness'    => 'fa-solid fa-dumbbell',
        ];
        $savedCurrency = auth()->user()->base_currency ?? 'GBP';
        $savedSymbol   = match($savedCurrency) { 'GBP' => '£', 'USD' => '$', 'CHF' => 'Fr', default => '€' };
    @endphp

    {{-- ===== CURRENCY OVERLAY ===== --}}
    <div id="currencyOverlay" style="display:none;">
        <div id="currencyCard">
            <div id="currencyCardHeader">
                <i class="fa-solid fa-location-dot loc-icon"></i>
                <h5>{{ __('messages.currency_overlay_title') }}</h5>
            </div>
            <div id="currencyCardBody">
                <div id="detectingState">
                    <i class="fa-solid fa-circle-notch fa-spin" style="margin-right:6px;"></i> {{ __('messages.detecting_location') }}
                </div>
                <div id="detectedState" style="display:none;">
                    <div id="currencyIconWrap">
                        <i class="fa-solid fa-earth-europe"></i>
                    </div>
                    <div id="detectedFlag" style="text-align:center;margin-bottom:12px;"></div>
                    <p>
                        {{ __('messages.currency_detected_intro') }} <strong id="detectedCountryName"></strong>.<br>
                        {{ __('messages.currency_detected_question') }} <strong id="detectedCurrencyLabel"></strong> {{ __('messages.currency_detected_question_post') }}
                    </p>
                    <div id="currencyManualWrap">
                        <label>{{ __('messages.choose_manually') }}</label>
                        <select id="currencyModalSelect">
                            <option value="GBP">{{ __('messages.currency_gb') }}</option>
                            <option value="EUR">{{ __('messages.currency_eu') }}</option>
                            <option value="USD">{{ __('messages.currency_us') }}</option>
                            <option value="CHF">{{ __('messages.currency_ch') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            <div id="overlayFooter">
                <button type="button" id="skipCurrencyBtn">{{ __('messages.skip') }}</button>
                <button type="button" id="confirmCurrencyBtn">
                    <i class="fa-solid fa-check"></i> {{ __('messages.confirm') }}
                </button>
            </div>
        </div>
    </div>

    <section class="setupStepsWrapper">
        <div class="container">

            {{-- Stepper --}}
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">{{ __('messages.setup_step_budget') }}</div><div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_banks') }}</div><div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_investments') }}</div><div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_done') }}</div>
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

            {{-- Title --}}
            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>{{ __('messages.step3_title') }}</h1>
                    <p>{{ __('messages.step3_desc') }}</p>
                </div>
            </div>

            @if ($errors->any())
                <section class="errorsBanner">
                    <div class="container">
                        <div class="row"><div class="col-12">
                                <ul>
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div></div>
                    </div>
                </section>
            @endif

            <div class="row">
                <div class="col-12">
                    <form action="{{ url('/account-setup-step-three-store') }}" method="post">
                        @csrf
                        <input type="hidden" name="base_currency" id="baseCurrencyInput" value="{{ $savedCurrency }}">

                        {{-- Stipendio --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle"><i class="fa-solid fa-wallet cat-icon"></i>{{ __('messages.salary') }}</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6">
                                        <input type="date" name="salary_date" id="salary_date"
                                               value="{{ old('salary_date', $accSetup['salary_date'] ?? '') }}">
                                        @error('salary_date')<div class="alert alert-danger mt-2">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4 col-6">
                                        <div class="input-group ms-auto mt-0">
                                            <label for="salary_amount" class="input-group-text currencySymbolLabel">{{ $savedSymbol }}</label>
                                            <input type="number" class="form-control" name="salary_amount" id="salary_amount"
                                                   placeholder="0.00" min="0" step="any"
                                                   value="{{ old('salary_amount', $accSetup['salary_amount'] ?? '') }}">
                                        </div>
                                        @error('salary_amount')<div class="alert alert-danger mt-2">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Spese --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle">{{ __('messages.expenses') }}</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center gy-md-2 gy-1 gx-5">
                                    @foreach ($defaultBudgetCategories as $dCat)
                                        @php
                                            $slug    = $dCat->slug ?? strtolower(preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $dCat->name)));
                                            $name    = "expense_{$slug}_amount";
                                            $val     = old($name, $accSetup[$name] ?? '');
                                            $iconKey = strtolower($dCat->name);
                                            $icon    = $iconMap[$iconKey] ?? 'fa-solid fa-ellipsis';
                                        @endphp
                                        <div class="col-lg-6">
                                            <div class="row align-items-center">
                                                <div class="col-md-8 col-6">
                                                    <label class="mb-0" for="{{ $name }}">
                                                        <i class="{{ $icon }} cat-icon"></i>{{ $dCat->name }}
                                                    </label>
                                                </div>
                                                <div class="col-md-4 col-6 d-flex justify-content-end">
                                                    <div class="input-group">
                                                        <label class="input-group-text currencySymbolLabel" for="{{ $name }}">{{ $savedSymbol }}</label>
                                                        <input type="number" class="form-control text-end"
                                                               id="{{ $name }}" name="{{ $name }}"
                                                               placeholder="0.00" min="0" step="any"
                                                               value="{{ $val }}">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Altro --}}
                        <div class="row"><div class="col-12"><h6 class="formSectionTitle"><i class="fa-solid fa-ellipsis cat-icon"></i>{{ __('messages.other') }}</h6></div></div>
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                @php
                                    $otherNames   = old('other_name', $accSetup['other_name'] ?? []);
                                    $otherAmounts = old('other_amounts', $accSetup['other_amounts'] ?? []);
                                @endphp
                                <div class="expenseItemInner">
                                    <div class="row align-items-center gy-md-2 gy-1 gx-5" id="otherExpenseItems">
                                        @if(!empty($otherNames))
                                            @foreach($otherNames as $i => $nm)
                                                <div class="col-lg-6 other-row">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-8 col-6">
                                                            <input type="text" name="other_name[]" placeholder="{{ __('messages.description_placeholder') }}" style="width:100%" value="{{ $nm }}">
                                                        </div>
                                                        <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                            <div class="input-group mt-0">
                                                                <label class="input-group-text currencySymbolLabel">{{ $savedSymbol }}</label>
                                                                <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00" value="{{ $otherAmounts[$i] ?? '' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="col-lg-6 other-row">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8 col-6">
                                                        <input type="text" name="other_name[]" placeholder="{{ __('messages.description_placeholder') }}" style="width:100%">
                                                    </div>
                                                    <div class="col-md-4 col-6 d-md-flex justify-content-md-end">
                                                        <div class="input-group mt-0">
                                                            <label class="input-group-text currencySymbolLabel">{{ $savedSymbol }}</label>
                                                            <input type="number" class="form-control" name="other_amounts[]" placeholder="0.00">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <button type="button" class="add-expense">
                                            <i class="fa-solid fa-circle-plus"></i> {{ __('messages.add_another_item') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Totale --}}
                        <div class="expensesWrap">
                            <div class="expenseItem">
                                <div class="row align-items-center">
                                    <div class="col-md-8 col-6"><label>{{ __('messages.total') }}</label></div>
                                    <div class="col-md-4 col-6 d-flex justify-content-end">
                                        <p><span class="currencySymbolLabel">{{ $savedSymbol }}</span> <span class="totalAmount"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Nav buttons --}}
                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                @php($period = data_get($accSetup ?? [], 'period_selection'))
                                @if ($period === 'fixed_date' || $period === 'weekly' || $period === 'custom')
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-two') }}">{{ __('messages.back') }}</a>
                                @else
                                    <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">{{ __('messages.back') }}</a>
                                @endif
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button type="submit" class="twoToneBlueGreenBtn">{{ __('messages.continue') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>

    <script>
        (function () {
            'use strict';

            var STORAGE_KEY = 'cc_currency_confirmed_v1';
            var SYMBOLS     = { GBP: '£', EUR: '€', USD: '$', CHF: 'Fr' };
            var EU          = ['AT','BE','CY','EE','FI','FR','DE','GR','IE','IT','LV','LT','LU','MT','NL','PT','SK','SI','ES'];
            var isLight     = document.body.classList.contains('light-mode');

            var COUNTRY_UK      = @json(__('messages.country_uk'));
            var COUNTRY_US      = @json(__('messages.country_us'));
            var COUNTRY_CH      = @json(__('messages.country_ch'));
            var COUNTRY_EUROPE  = @json(__('messages.country_europe'));
            var LOCATION_UNKNOWN = @json(__('messages.location_not_detected'));

            function updateAllSymbols(sym) {
                document.querySelectorAll('.currencySymbolLabel').forEach(function (el) { el.textContent = sym; });
            }

            function closeOverlay() {
                document.getElementById('currencyOverlay').style.display = 'none';
            }

            function openOverlay() {
                document.getElementById('currencyOverlay').style.display = 'flex';
            }

            function setFlagBadge(code, fallback) {
                var color   = fallback || (isLight ? '#0D9488' : '#44E0AC');
                var bgColor = isLight ? 'rgba(13,148,136,0.1)'  : 'rgba(68,224,172,0.15)';
                var border  = isLight ? 'rgba(13,148,136,0.3)'  : 'rgba(68,224,172,0.3)';
                document.getElementById('detectedFlag').innerHTML =
                    '<span class="flagBadge" style="display:inline-block;background:' + bgColor + ';border:1px solid ' + border + ';border-radius:10px;padding:6px 20px;font-size:1.1rem;font-weight:800;color:' + color + ';letter-spacing:3px;">' + code + '</span>';
            }

            document.addEventListener('DOMContentLoaded', function () {

                var alreadyConfirmed = localStorage.getItem(STORAGE_KEY);
                if (!alreadyConfirmed) {
                    openOverlay();

                    fetch('https://ipapi.co/json/')
                        .then(function (r) { return r.json(); })
                        .then(function (data) {
                            var code = data.country_code || 'GB';
                            var info;

                            if (code === 'GB')             info = { currency: 'GBP', name: COUNTRY_UK,                         displayCode: 'GB' };
                            else if (code === 'US')         info = { currency: 'USD', name: COUNTRY_US,                         displayCode: 'US' };
                            else if (code === 'CH')         info = { currency: 'CHF', name: COUNTRY_CH,                         displayCode: 'CH' };
                            else if (EU.indexOf(code) >= 0) info = { currency: 'EUR', name: data.country_name || COUNTRY_EUROPE, displayCode: code };
                            else                            info = { currency: 'EUR', name: data.country_name || code,           displayCode: code };

                            setFlagBadge(info.displayCode);
                            document.getElementById('detectedCountryName').textContent   = info.name;
                            document.getElementById('detectedCurrencyLabel').textContent = info.currency + ' (' + SYMBOLS[info.currency] + ')';
                            document.getElementById('currencyModalSelect').value         = info.currency;

                            document.getElementById('detectingState').style.display = 'none';
                            document.getElementById('detectedState').style.display  = 'block';
                            document.getElementById('overlayFooter').style.display  = 'flex';
                        })
                        .catch(function () {
                            setFlagBadge('?', isLight ? 'rgba(0,0,0,0.25)' : 'rgba(255,255,255,0.4)');
                            document.getElementById('detectedCountryName').textContent   = LOCATION_UNKNOWN;
                            document.getElementById('detectedCurrencyLabel').textContent = 'GBP (£)';
                            document.getElementById('currencyModalSelect').value         = 'GBP';
                            document.getElementById('detectingState').style.display = 'none';
                            document.getElementById('detectedState').style.display  = 'block';
                            document.getElementById('overlayFooter').style.display  = 'flex';
                        });
                }

                document.getElementById('confirmCurrencyBtn').addEventListener('click', function () {
                    var chosen = document.getElementById('currencyModalSelect').value;
                    document.getElementById('baseCurrencyInput').value = chosen;
                    updateAllSymbols(SYMBOLS[chosen] || '£');
                    localStorage.setItem(STORAGE_KEY, chosen);
                    closeOverlay();
                });

                document.getElementById('skipCurrencyBtn').addEventListener('click', function () {
                    localStorage.setItem(STORAGE_KEY, 'skipped');
                    closeOverlay();
                });

                // ── Totale ───────────────────────────────────────────────
                var totalEl   = document.querySelector('.totalAmount');
                var container = document.getElementById('otherExpenseItems');

                function calcTotal() {
                    var total = 0;
                    document.querySelectorAll('input[type="number"]').forEach(function (inp) {
                        if (inp.name === 'salary_amount') return;
                        var v = parseFloat(inp.value);
                        if (!isNaN(v)) total += v;
                    });
                    totalEl.textContent = total.toFixed(2);
                }

                document.addEventListener('input', function (e) {
                    if (e.target.matches('input[type="number"]')) calcTotal();
                });
                calcTotal();

                // ── Aggiungi voce ────────────────────────────────────────
                var descPlaceholder = @json(__('messages.description_placeholder'));
                document.querySelector('.add-expense').addEventListener('click', function () {
                    var sym  = SYMBOLS[document.getElementById('baseCurrencyInput').value] || '£';
                    var wrap = document.createElement('div');
                    wrap.className = 'col-lg-6 other-row';
                    wrap.innerHTML =
                        '<div class="row align-items-center">' +
                        '<div class="col-md-8 col-6">' +
                        '<input type="text" name="other_name[]" placeholder="' + descPlaceholder + '" style="width:100%">' +
                        '</div>' +
                        '<div class="col-md-4 col-6 d-md-flex justify-content-md-end">' +
                        '<div class="input-group mt-0">' +
                        '<label class="input-group-text currencySymbolLabel">' + sym + '</label>' +
                        '<input type="number" class="form-control" name="other_amounts[]" placeholder="0.00">' +
                        '</div>' +
                        '</div>' +
                        '</div>';
                    container.appendChild(wrap);
                });
            });
        })();
    </script>
@endsection
