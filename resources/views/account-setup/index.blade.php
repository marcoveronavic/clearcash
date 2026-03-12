@extends('layouts.customer')

@section('styles_in_head')
    <link rel="stylesheet" href="{{ asset('build/assets/account-setup.css') }}">
@endsection

@section('content')
    <style>
        header,
        aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }

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
                    <h1>Seleziona il tuo periodo</h1>
                    <p>Il tuo budget e i tuoi report si sincronizzeranno con questo periodo.</p>
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

                    @php
                        $pre = $accSetup['period_selection'] ?? old('period_selection');
                    @endphp

                    <form action="{{ route('account-setup.step-one-store') }}" method="POST" id="periodForm">
                        @csrf

                        <label class="inputWrap {{ $pre === 'first_day' ? 'active' : '' }}" for="first_day_of_month">
                            <input type="radio" name="period_selection" id="first_day_of_month" value="first_day"
                                {{ $pre === 'first_day' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Dal primo all'ultimo giorno del mese</h5>
                                <p>Il tuo periodo inizierà il 1° giorno del mese e terminerà l'ultimo.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'last_working' ? 'active' : '' }}" for="last_working_day">
                            <input type="radio" name="period_selection" id="last_working_day" value="last_working"
                                {{ $pre === 'last_working' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Ultimo giorno lavorativo del mese</h5>
                                <p>Il tuo periodo si resetterà l'ultimo giorno lavorativo del mese.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'fixed_date' ? 'active' : '' }}" for="fixed_monthly_date">
                            <input type="radio" name="period_selection" id="fixed_monthly_date" value="fixed_date"
                                {{ $pre === 'fixed_date' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Data fissa mensile</h5>
                                <p>Il tuo periodo si resetterà nel giorno esatto che scegli.</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'weekly' ? 'active' : '' }}" for="weekly_period">
                            <input type="radio" name="period_selection" id="weekly_period" value="weekly"
                                {{ $pre === 'weekly' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Settimanale</h5>
                                <p>Il tuo periodo si resetterà ogni settimana (sceglierai il giorno di inizio dopo).</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <label class="inputWrap {{ $pre === 'custom' ? 'active' : '' }}" for="custom_period">
                            <input type="radio" name="period_selection" id="custom_period" value="custom"
                                {{ $pre === 'custom' ? 'checked' : '' }}>
                            <div class="content">
                                <h5>Personalizzato</h5>
                                <p>Definisci un periodo personalizzato (ti chiederemo i dettagli nel prossimo step).</p>
                            </div>
                            <div class="iconWrap"><i class="fas fa-chevron-right" aria-hidden="true"></i></div>
                        </label>

                        <button type="submit" class="btn btn-primary d-none" id="continueBtn">Continua</button>
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
                    if (e.target && e.target.tagName.toLowerCase() === 'input') return;

                    wraps.forEach(x => x.classList.remove('active'));
                    this.classList.add('active');

                    const radio = this.querySelector('input[type="radio"]');
                    if (!radio) return;

                    radio.checked = true;

                    const old = form.querySelector('input[type="hidden"][name="period_selection"]');
                    if (old) old.remove();

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

