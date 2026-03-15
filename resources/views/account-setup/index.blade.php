@extends('customer.layouts.main')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        header, aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }

        .inputWrap input[type="radio"] { position:absolute;opacity:0;width:0;height:0;margin:0; }
        .inputWrap {
            cursor:pointer!important;border:1px solid #ccc;padding:1rem;margin-bottom:1rem;
            border-radius:6px;display:flex;align-items:center;justify-content:space-between;
            transition:background-color .2s ease;
        }
        .inputWrap:hover { background:#f0f0f0; }
        .inputWrap.active { border-color:#0ea5e9; background:#eef8ff; }
        .inputWrap .content h5 { margin:0 0 .25rem 0; }
        .inputWrap .content p { margin:0; opacity:.8; }

        /* ── Lang overlay ── */
        #langOverlay {
            position:fixed;inset:0;background:rgba(0,0,0,0.75);
            z-index:99999;align-items:center;justify-content:center;
        }
        #langCard {
            background:#0f2629;border:1px solid rgba(255,255,255,0.08);
            border-radius:20px;width:100%;max-width:400px;margin:16px;overflow:hidden;
        }
        #langCardHeader {
            padding:20px 24px 16px;border-bottom:1px solid rgba(255,255,255,0.06);
            display:flex;align-items:center;gap:10px;
        }
        #langCardHeader h5 { margin:0;color:#fff;font-weight:800;font-size:1.05rem; }
        #langCardHeader i { color:#44E0AC; }
        #langCardBody { padding:16px; }
        #langCardFooter {
            padding:14px 24px;border-top:1px solid rgba(255,255,255,0.06);
            display:flex;justify-content:flex-end;
        }
        .langOption {
            display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;
            cursor:pointer;transition:background .15s;text-decoration:none;
        }
        .langOption:hover { background:rgba(255,255,255,0.07); }
        .langOption.active { background:rgba(68,224,172,0.12); }
        .langFlag {
            width:34px;height:34px;border-radius:8px;background:rgba(255,255,255,0.06);
            display:flex;align-items:center;justify-content:center;
            font-size:0.75rem;font-weight:800;color:#44E0AC;letter-spacing:1px;flex-shrink:0;
        }
        .langOption.active .langFlag { background:rgba(68,224,172,0.2); }
        .langName { color:#fff;font-size:0.95rem;font-weight:600; }
        .langNative { color:rgba(255,255,255,0.4);font-size:0.8rem; }
        .langCheck { margin-left:auto;color:#44E0AC;display:none; }
        .langOption.active .langCheck { display:block; }
        #skipLangBtn {
            padding:10px 20px;border-radius:12px;
            border:1px solid rgba(255,255,255,0.1);
            background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.6);
            font-weight:600;font-size:0.9rem;cursor:pointer;transition:all .15s;
            text-decoration:none;display:inline-block;
        }
        #skipLangBtn:hover { background:rgba(255,255,255,0.12);color:#fff; }

        body.light-mode #langOverlay { background:rgba(0,0,0,0.35); }
        body.light-mode #langCard { background:#fff;border:1px solid rgba(0,0,0,0.1);box-shadow:0 20px 60px rgba(0,0,0,0.15); }
        body.light-mode #langCardHeader { border-bottom:1px solid rgba(0,0,0,0.08); }
        body.light-mode #langCardHeader h5 { color:#0D2020; }
        body.light-mode #langCardHeader i { color:#0D9488; }
        body.light-mode #langCardFooter { border-top:1px solid rgba(0,0,0,0.08); }
        body.light-mode .langOption:hover { background:rgba(0,0,0,0.04); }
        body.light-mode .langOption.active { background:rgba(13,148,136,0.08); }
        body.light-mode .langFlag { background:rgba(0,0,0,0.05);color:#0D9488; }
        body.light-mode .langOption.active .langFlag { background:rgba(13,148,136,0.15); }
        body.light-mode .langName { color:#0D2020; }
        body.light-mode .langNative { color:rgba(13,32,32,0.4); }
        body.light-mode .langCheck { color:#0D9488; }
        body.light-mode #skipLangBtn { background:#f1f5f5;border:1px solid rgba(0,0,0,0.1);color:#374151; }
        body.light-mode #skipLangBtn:hover { background:#e5eaea;color:#0D2020; }
    </style>

    {{-- ── Language overlay ── --}}
    <div id="langOverlay" style="display:{{ session()->has('locale_chosen') ? 'none' : 'flex' }}">
        <div id="langCard">
            <div id="langCardHeader">
                <i class="fa-solid fa-globe"></i>
                <h5>{{ __('messages.select_your_language') }}</h5>
            </div>
            <div id="langCardBody">
                @php
                    $langs = [
                        'en' => ['name' => 'English',   'native' => 'English',    'code' => 'EN'],
                        'it' => ['name' => 'Italiano',  'native' => 'Italian',    'code' => 'IT'],
                        'fr' => ['name' => 'Français',  'native' => 'French',     'code' => 'FR'],
                        'de' => ['name' => 'Deutsch',   'native' => 'German',     'code' => 'DE'],
                        'es' => ['name' => 'Español',   'native' => 'Spanish',    'code' => 'ES'],
                        'pt' => ['name' => 'Português', 'native' => 'Portuguese', 'code' => 'PT'],
                    ];
                    $current = app()->getLocale();
                @endphp
                @foreach($langs as $locale => $lang)
                    <a href="{{ route('language.switch', $locale) }}"
                       class="langOption {{ $current === $locale ? 'active' : '' }}">
                        <div class="langFlag">{{ $lang['code'] }}</div>
                        <div>
                            <div class="langName">{{ $lang['name'] }}</div>
                            <div class="langNative">{{ $lang['native'] }}</div>
                        </div>
                        <i class="fa-solid fa-check langCheck"></i>
                    </a>
                @endforeach
            </div>
            <div id="langCardFooter">
                <a href="{{ route('language.skip') }}" id="skipLangBtn">{{ __('messages.skip') }}</a>
            </div>
        </div>
    </div>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">{{ __('messages.setup_step_budget') }}</div>
                            <div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_banks') }}</div>
                            <div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_investments') }}</div>
                            <div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_done') }}</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>{{ __('messages.select_your_period') }}</h1>
                    <p>{{ __('messages.period_sync_desc') }}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php $pre = $accSetup['period_selection'] ?? old('period_selection'); @endphp

                    <form action="{{ route('account-setup.step-one-store') }}" method="POST" id="periodForm">
                        @csrf

                        <label class="inputWrap {{ $pre === 'first_day' ? 'active' : '' }}" for="first_day_of_month">
                            <input type="radio" name="period_selection" id="first_day_of_month" value="first_day" {{ $pre === 'first_day' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>{{ __('messages.period_first_day') }}</h5>
                                <p>{{ __('messages.period_first_day_desc') }}</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'last_working' ? 'active' : '' }}" for="last_working_day">
                            <input type="radio" name="period_selection" id="last_working_day" value="last_working" {{ $pre === 'last_working' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>{{ __('messages.period_last_working') }}</h5>
                                <p>{{ __('messages.period_last_working_desc') }}</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'fixed_date' ? 'active' : '' }}" for="fixed_monthly_date">
                            <input type="radio" name="period_selection" id="fixed_monthly_date" value="fixed_date" {{ $pre === 'fixed_date' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>{{ __('messages.period_fixed_date') }}</h5>
                                <p>{{ __('messages.period_fixed_date_desc') }}</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'weekly' ? 'active' : '' }}" for="weekly_period">
                            <input type="radio" name="period_selection" id="weekly_period" value="weekly" {{ $pre === 'weekly' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>{{ __('messages.period_weekly') }}</h5>
                                <p>{{ __('messages.period_weekly_desc') }}</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'custom' ? 'active' : '' }}" for="custom_period">
                            <input type="radio" name="period_selection" id="custom_period" value="custom" {{ $pre === 'custom' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>{{ __('messages.period_custom') }}</h5>
                                <p>{{ __('messages.period_custom_desc') }}</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <button type="submit" class="btn btn-primary d-none" id="continueBtn">{{ __('messages.continue') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('periodForm');
            var wraps = document.querySelectorAll('.inputWrap');
            wraps.forEach(function (w) {
                w.addEventListener('click', function (e) {
                    if (e.target && e.target.tagName.toLowerCase() === 'input') return;
                    wraps.forEach(function (x) { x.classList.remove('active'); });
                    this.classList.add('active');
                    var radio = this.querySelector('input[type="radio"]');
                    if (!radio) return;
                    radio.checked = true;
                    var old = form.querySelector('input[type="hidden"][name="period_selection"]');
                    if (old) old.remove();
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'period_selection';
                    hidden.value = radio.value;
                    form.appendChild(hidden);
                    setTimeout(function () { form.submit(); }, 250);
                });
            });
        });
    </script>
@endsection
