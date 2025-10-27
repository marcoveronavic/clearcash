@extends('layouts.customer')

@section('styles_in_head')
    {{-- CSS della pagina --}}
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        /* Nasconde header/sidebar per la pagina setup, come prima */
        header,
        aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }

        /* Radio “invisibile” ma presente per accessibilità */
        .inputWrap input[type="radio"]{
            position:absolute;opacity:0;width:0;height:0;margin:0;
        }
        .inputWrap{
            cursor:pointer!important;border:1px solid #ccc;padding:1rem;margin-bottom:1rem;
            border-radius:6px;display:flex;align-items:center;justify-content:space-between;
            transition:background-color .2s ease;
        }
        .inputWrap:hover{ background:#f0f0f0; }
        .inputWrap.active{ border-color:#0ea5e9; background:#eef8ff; }
        .inputWrap .content h5{ margin:0 0 .25rem 0; }
        .inputWrap .content p{ margin:0; opacity:.8; }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            {{-- STEP BAR (come prima) --}}
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
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                            <div class="box"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Titolo/pitch --}}
            <div class="row mt-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1>Select your period</h1>
                    <p>Your budgeting and reporting will sync with this period.</p>
                </div>
            </div>

            {{-- Contenuto --}}
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

                    @php
                        // Preselezione da sessione o vecchio input
                        $pre = $accSetup['period_selection'] ?? old('period_selection');
                    @endphp

                    <form action="{{ route('account-setup.step-one-store') }}" method="POST" id="periodForm">
                        @csrf

                        {{-- 1) Primo-ultimo giorno del mese --}}
                        <label class="inputWrap {{ $pre === 'first_day' ? 'active' : '' }}" for="first_day_of_month">
                            <input type="radio" name="period_selection" id="first_day_of_month" value="first_day"
                                {{ $pre === 'first_day' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>First to last day of the month</h5>
                                <p>Your period will start on the 1st day of the month and end on the last.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        {{-- 2) Ultimo giorno lavorativo del mese --}}
                        <label class="inputWrap {{ $pre === 'last_working' ? 'active' : '' }}" for="last_working_day">
                            <input type="radio" name="period_selection" id="last_working_day" value="last_working"
                                {{ $pre === 'last_working' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Last working day of the month</h5>
                                <p>Your period will reset on the last working day of the month.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        {{-- 3) Giorno fisso del mese --}}
                        <label class="inputWrap {{ $pre === 'fixed_date' ? 'active' : '' }}" for="fixed_monthly_date">
                            <input type="radio" name="period_selection" id="fixed_monthly_date" value="fixed_date"
                                {{ $pre === 'fixed_date' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Fixed Monthly Date</h5>
                                <p>Your period will reset on the exact day you choose.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        {{-- 4) Settimanale (nuovo in validazione) --}}
                        <label class="inputWrap {{ $pre === 'weekly' ? 'active' : '' }}" for="weekly_period">
                            <input type="radio" name="period_selection" id="weekly_period" value="weekly"
                                {{ $pre === 'weekly' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Weekly</h5>
                                <p>Your period will reset weekly (you'll choose the start day next).</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        {{-- 5) Personalizzato (nuovo in validazione) --}}
                        <label class="inputWrap {{ $pre === 'custom' ? 'active' : '' }}" for="custom_period">
                            <input type="radio" name="period_selection" id="custom_period" value="custom"
                                {{ $pre === 'custom' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Custom</h5>
                                <p>Define a custom period window (we’ll ask details in the next step).</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        {{-- Fallback button (se JS off) --}}
                        <button type="submit" class="btn btn-primary d-none" id="continueBtn">Continue</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('periodForm');
            const wraps = document.querySelectorAll('.inputWrap');

            wraps.forEach(w => {
                w.addEventListener('click', function (e) {
                    // Evita doppi submit se si clicca direttamente sull'input
                    if (e.target && e.target.tagName.toLowerCase() === 'input') return;

                    wraps.forEach(x => x.classList.remove('active'));
                    this.classList.add('active');

                    const radio = this.querySelector('input[type="radio"]');
                    if (!radio) return;

                    // check del radio
                    radio.checked = true;

                    // pulizia eventuale hidden duplicati
                    const old = form.querySelector('input[type="hidden"][name="period_selection"]');
                    if (old) old.remove();

                    // hidden di sicurezza + submit
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'period_selection';
                    hidden.value = radio.value;
                    form.appendChild(hidden);

                    setTimeout(() => form.submit(), 250);
                });
            });
        });
    </script>
@endsection
