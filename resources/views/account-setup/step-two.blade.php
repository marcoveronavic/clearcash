@extends('layouts.customer')
@section('styles_in_head')
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header, aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }
        .singleDateSelect .input-group-text{ border:none; }

        .setupStepsWrapper h4{
            color: rgba(255,255,255,0.75) !important;
        }
        .setupStepsWrapper p,
        .setupStepsWrapper label,
        .setupStepsWrapper .form-label,
        .setupStepsWrapper .text-muted,
        .setupStepsWrapper small{
            color: rgba(255,255,255,0.72) !important;
        }
        .setupStepsWrapper h1{
            color: #ffffff !important;
        }

        .setupStepsWrapper .form-control,
        .setupStepsWrapper .form-select{
            background: rgba(255,255,255,0.06) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255,255,255,0.14) !important;
        }
        .setupStepsWrapper .form-control:focus,
        .setupStepsWrapper .form-select:focus{
            outline: none !important;
            box-shadow: 0 0 0 0.2rem rgba(46,240,179,0.18) !important;
            border-color: rgba(46,240,179,0.55) !important;
        }
        .setupStepsWrapper input[type="date"]::-webkit-calendar-picker-indicator{
            filter: invert(1);
            opacity: .85;
            cursor: pointer;
        }

        .errorsBanner li { color: #ffffff !important; }
    </style>

    @php
        $selection = $accSetup['period_selection'] ?? null;
    @endphp

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

            <div class="mt-md-4 mt-0">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h4>
                        @if($selection === 'first_day') {{ __('messages.period_first_day') }}
                        @elseif($selection === 'last_working') {{ __('messages.period_last_working') }}
                        @elseif($selection === 'fixed_date') {{ __('messages.period_fixed_date') }}
                        @elseif($selection === 'weekly') {{ __('messages.period_weekly_label') }}
                        @elseif($selection === 'custom') {{ __('messages.period_custom_label') }}
                        @else {{ __('messages.select_your_period') }} @endif
                    </h4>

                    <h1>
                        @if($selection === 'fixed_date') {{ __('messages.step2_select_day') }}
                        @elseif($selection === 'weekly') {{ __('messages.step2_select_weekday') }}
                        @elseif($selection === 'custom') {{ __('messages.step2_select_range') }}
                        @else {{ __('messages.step2_select_date') }} @endif
                    </h1>

                    <p>
                        @if($selection === 'fixed_date')
                            {{ __('messages.step2_fixed_date_desc') }}
                        @elseif($selection === 'weekly')
                            {{ __('messages.step2_weekly_desc') }}
                        @elseif($selection === 'custom')
                            {{ __('messages.step2_custom_desc') }}
                        @endif
                    </p>
                </div>
            </div>

            @if($errors->any())
                <section class="errorsBanner">
                    <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <ul>
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <div class="my-2 px-0">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1 pe-0">
                    <form id="stepTwoForm" action="/account-setup-step-two-store" method="post">
                        @csrf

                        @if($selection === 'fixed_date')
                            <div class="dateSelectMainWrapper mb-4">
                                <div class="input-group singleDateSelect">
                                        <?php for ($i = 1; $i <= 31; $i++): ?>
                                    <div class="input-group-text">
                                        <input type="radio" class="btn-check" name="date" value="<?= $i ?>" id="d<?= $i ?>" autocomplete="off">
                                        <label for="d<?= $i ?>"><?= $i ?></label>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                <p>{!! __('messages.step2_reset_on_day') !!}</p>
                            </div>
                        @endif

                        @if($selection === 'weekly')
                            <div class="mb-4">
                                <label for="weekday" class="form-label">{{ __('messages.step2_week_starts_on') }}</label>
                                <select id="weekday" name="weekday" class="form-select">
                                    <option value="1">{{ __('messages.monday') }}</option>
                                    <option value="2">{{ __('messages.tuesday') }}</option>
                                    <option value="3">{{ __('messages.wednesday') }}</option>
                                    <option value="4">{{ __('messages.thursday') }}</option>
                                    <option value="5">{{ __('messages.friday') }}</option>
                                    <option value="6">{{ __('messages.saturday') }}</option>
                                    <option value="7">{{ __('messages.sunday') }}</option>
                                </select>
                                <p class="mt-2 text-muted">{{ __('messages.step2_weekly_covers') }}</p>
                            </div>
                        @endif

                        @if($selection === 'custom')
                            <div class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="custom_start_date" class="form-label">{{ __('messages.step2_start_date') }}</label>
                                        <input type="date" id="custom_start_date" name="custom_start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="custom_end_date" class="form-label">{{ __('messages.step2_end_date') }}</label>
                                        <input type="date" id="custom_end_date" name="custom_end_date" class="form-control" required>
                                    </div>
                                </div>

                                <input type="hidden" id="start_date" name="start_date" value="">
                                <input type="hidden" id="end_date" name="end_date" value="">
                                <input type="hidden" id="dateField" name="date" value="">

                                <p class="mt-2 text-muted">{{ __('messages.step2_end_after_start') }}</p>
                            </div>
                        @endif

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">{{ __('messages.back') }}</a>
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

    @if(($accSetup['period_selection'] ?? null) === 'fixed_date')
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const radios = document.querySelectorAll('input[name="date"]');
                const strong = document.querySelector('p strong');
                radios.forEach(r => r.addEventListener('change', function(){
                    const day = parseInt(this.value, 10);
                    if (strong) strong.textContent = `${day}`;
                }));
            });
        </script>
    @endif

    @if(($accSetup['period_selection'] ?? null) === 'custom')
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const form = document.getElementById('stepTwoForm');
                const start = document.getElementById('custom_start_date');
                const end = document.getElementById('custom_end_date');
                const hiddenStart = document.getElementById('start_date');
                const hiddenEnd = document.getElementById('end_date');
                const dateField = document.getElementById('dateField');

                function syncHiddenFields() {
                    const s = start ? start.value : '';
                    const en = end ? end.value : '';
                    if (hiddenStart) hiddenStart.value = s;
                    if (hiddenEnd) hiddenEnd.value = en;
                    if (dateField) {
                        if (s && s.includes('-')) {
                            const parts = s.split('-');
                            const day = parseInt(parts[2], 10);
                            dateField.value = isNaN(day) ? '' : String(day);
                        } else {
                            dateField.value = '';
                        }
                    }
                }

                if (start) start.addEventListener('change', syncHiddenFields);
                if (end) end.addEventListener('change', syncHiddenFields);

                form.addEventListener('submit', function (e) {
                    syncHiddenFields();
                    const s = start ? start.value : '';
                    const en = end ? end.value : '';
                    if (!s || !en) {
                        e.preventDefault();
                        alert(@json(__('messages.step2_alert_select_dates')));
                        return false;
                    }
                    if (en < s) {
                        e.preventDefault();
                        alert(@json(__('messages.step2_alert_end_after')));
                        return false;
                    }
                    if (!dateField || !dateField.value) {
                        e.preventDefault();
                        alert(@json(__('messages.step2_alert_valid_start')));
                        return false;
                    }
                });
            });
        </script>
    @endif
@endsection
