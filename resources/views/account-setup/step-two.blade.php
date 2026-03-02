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

        /* ✅ FIX: testi grigi invisibili su sfondo scuro (palette app) */
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

        /* ✅ FIX: input date leggibile */
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

        /* ✅ error text leggibile */
        .errorsBanner li { color: #ffffff !important; }
    </style>

    @php
        // Protezione lato view: se manca, fallback a null
        $selection = $accSetup['period_selection'] ?? null;
    @endphp

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item active">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Add your investments and pensions</div>
                            <div class="sep"></div>
                            <div class="item">Done</div>
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
                        @if($selection === 'first_day') First to last day of the month
                        @elseif($selection === 'last_working') Last Working Day of the month
                        @elseif($selection === 'fixed_date') Fixed Monthly Date
                        @elseif($selection === 'weekly') Weekly period
                        @elseif($selection === 'custom') Custom period
                        @else Select your period @endif
                    </h4>

                    <h1>
                        @if($selection === 'fixed_date') Select the date
                        @elseif($selection === 'weekly') Select the weekday
                        @elseif($selection === 'custom') Select the range
                        @else Select the date @endif
                    </h1>

                    <p>
                        @if($selection === 'fixed_date')
                            Choose the day of the month you would like your budgeting and reporting period to start.
                        @elseif($selection === 'weekly')
                            Choose which weekday your budgeting week starts.
                        @elseif($selection === 'custom')
                            Pick the exact start and end dates for your budgeting window.
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

                        {{-- FIXED DATE --}}
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
                                <p>Your period will reset on the <strong>Date Selected</strong></p>
                            </div>
                        @endif

                        {{-- WEEKLY --}}
                        @if($selection === 'weekly')
                            <div class="mb-4">
                                <label for="weekday" class="form-label">Week starts on</label>
                                <select id="weekday" name="weekday" class="form-select">
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="7">Sunday</option>
                                </select>
                                <p class="mt-2 text-muted">Your period will cover 7 days starting from this weekday.</p>
                            </div>
                        @endif

                        {{-- CUSTOM --}}
                        @if($selection === 'custom')
                            <div class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="custom_start_date" class="form-label">Start date</label>
                                        <input type="date" id="custom_start_date" name="custom_start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="custom_end_date" class="form-label">End date</label>
                                        <input type="date" id="custom_end_date" name="custom_end_date" class="form-control" required>
                                    </div>
                                </div>

                                {{-- ✅ compatibilità con validazioni diverse --}}
                                <input type="hidden" id="start_date" name="start_date" value="">
                                <input type="hidden" id="end_date" name="end_date" value="">

                                {{-- ✅ IMPORTANT: controller si aspetta "date" come giorno 1-31 --}}
                                <input type="hidden" id="dateField" name="date" value="">

                                <p class="mt-2 text-muted">End date must be the same or after the start date.</p>
                            </div>
                        @endif

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Back</a>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button type="submit" class="twoToneBlueGreenBtn">Continue</button>
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
                    let suffix = 'th';
                    if (day % 10 === 1 && day !== 11) suffix = 'st';
                    else if (day % 10 === 2 && day !== 12) suffix = 'nd';
                    else if (day % 10 === 3 && day !== 13) suffix = 'rd';
                    if (strong) strong.textContent = `${day}${suffix} of each month`;
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

                    // date deve essere un numero 1-31: prendo il giorno dalla start date YYYY-MM-DD
                    if (dateField) {
                        if (s && s.includes('-')) {
                            const parts = s.split('-'); // [YYYY, MM, DD]
                            const day = parseInt(parts[2], 10); // 1..31
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
                        alert('Please select both start and end dates.');
                        return false;
                    }
                    if (en < s) {
                        e.preventDefault();
                        alert('End date must be the same or after the start date.');
                        return false;
                    }

                    // ultima sicurezza: se dateField è vuoto, blocco
                    if (!dateField || !dateField.value) {
                        e.preventDefault();
                        alert('Please select a valid start date.');
                        return false;
                    }
                });
            });
        </script>
    @endif
@endsection
