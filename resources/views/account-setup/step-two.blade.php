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
                            <div class="item active">Crea il tuo budget</div>
                            <div class="sep"></div>
                            <div class="item">Aggiungi conti bancari</div>
                            <div class="sep"></div>
                            <div class="item">Investimenti e pensioni</div>
                            <div class="sep"></div>
                            <div class="item">Fatto</div>
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
                        @if($selection === 'first_day') Dal primo all'ultimo giorno del mese
                        @elseif($selection === 'last_working') Ultimo giorno lavorativo del mese
                        @elseif($selection === 'fixed_date') Data fissa mensile
                        @elseif($selection === 'weekly') Periodo settimanale
                        @elseif($selection === 'custom') Periodo personalizzato
                        @else Seleziona il tuo periodo @endif
                    </h4>

                    <h1>
                        @if($selection === 'fixed_date') Seleziona il giorno
                        @elseif($selection === 'weekly') Seleziona il giorno della settimana
                        @elseif($selection === 'custom') Seleziona l'intervallo
                        @else Seleziona la data @endif
                    </h1>

                    <p>
                        @if($selection === 'fixed_date')
                            Scegli il giorno del mese in cui vuoi che inizi il tuo periodo di budget.
                        @elseif($selection === 'weekly')
                            Scegli il giorno della settimana in cui inizia la tua settimana di budget.
                        @elseif($selection === 'custom')
                            Seleziona le date esatte di inizio e fine del tuo periodo di budget.
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
                                <p>Il tuo periodo si resetterà il <strong>giorno selezionato</strong></p>
                            </div>
                        @endif

                        @if($selection === 'weekly')
                            <div class="mb-4">
                                <label for="weekday" class="form-label">La settimana inizia il</label>
                                <select id="weekday" name="weekday" class="form-select">
                                    <option value="1">Lunedì</option>
                                    <option value="2">Martedì</option>
                                    <option value="3">Mercoledì</option>
                                    <option value="4">Giovedì</option>
                                    <option value="5">Venerdì</option>
                                    <option value="6">Sabato</option>
                                    <option value="7">Domenica</option>
                                </select>
                                <p class="mt-2 text-muted">Il tuo periodo coprirà 7 giorni a partire dal giorno selezionato.</p>
                            </div>
                        @endif

                        @if($selection === 'custom')
                            <div class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="custom_start_date" class="form-label">Data di inizio</label>
                                        <input type="date" id="custom_start_date" name="custom_start_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="custom_end_date" class="form-label">Data di fine</label>
                                        <input type="date" id="custom_end_date" name="custom_end_date" class="form-control" required>
                                    </div>
                                </div>

                                <input type="hidden" id="start_date" name="start_date" value="">
                                <input type="hidden" id="end_date" name="end_date" value="">
                                <input type="hidden" id="dateField" name="date" value="">

                                <p class="mt-2 text-muted">La data di fine deve essere uguale o successiva alla data di inizio.</p>
                            </div>
                        @endif

                        <div class="row align-items-center my-4">
                            <div class="col-6 d-flex justify-content-start">
                                <a class="setupStepsBackButton" href="{{ route('account-setup.step-one') }}">Indietro</a>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <button type="submit" class="twoToneBlueGreenBtn">Continua</button>
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
                    if (strong) strong.textContent = `${day} di ogni mese`;
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
                        alert('Seleziona entrambe le date di inizio e fine.');
                        return false;
                    }
                    if (en < s) {
                        e.preventDefault();
                        alert('La data di fine deve essere uguale o successiva alla data di inizio.');
                        return false;
                    }

                    if (!dateField || !dateField.value) {
                        e.preventDefault();
                        alert('Seleziona una data di inizio valida.');
                        return false;
                    }
                });
            });
        </script>
    @endif
    <script>
        (function(){
            var btn = document.createElement('button');
            btn.innerHTML = document.body.classList.contains('light-mode') ? '🌙 Dark' : '☀️ Light';
            btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:20px;border:1px solid #d1d5db;background:#fff;color:#111;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);';
            document.body.appendChild(btn);
            btn.addEventListener('click', function(){
                if (document.body.classList.contains('light-mode')) {
                    document.body.classList.remove('light-mode');
                    localStorage.setItem('cc-theme','dark');
                } else {
                    document.body.classList.add('light-mode');
                    localStorage.setItem('cc-theme','light');
                }
                window.location.reload();
            });
        })();
    </script>
@endsection

