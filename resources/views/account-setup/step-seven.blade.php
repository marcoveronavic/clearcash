@extends('layouts.customer')
@section('styles_in_head')
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header, aside.sidebar { display: none; }
        .floatingQuickAddDropUp { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }
        .setupStepsWrapper .setupStepsWrap .titles .item.active { color: #44E0AC; }
        .setupStepsWrapper .setupStepsWrap .boxes .box.active { background-color: #44E0AC; }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item">Crea il tuo budget</div><div class="sep"></div>
                            <div class="item">Aggiungi conti bancari</div><div class="sep"></div>
                            <div class="item">Investimenti e pensioni</div><div class="sep"></div>
                            <div class="item active">Fatto</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div><div class="box active"></div><div class="box active"></div>
                            <div class="box active"></div><div class="box active"></div><div class="box active"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1 class="mb-4">Tutto pronto! Il tuo account è configurato</h1>
                    <p>Il tuo account ClearCash è ora attivo e pronto all'uso.</p>
                    <p>Il prossimo passo è impostare i pagamenti ricorrenti e iniziare ad aggiungere le transazioni.</p>
                    <a href="{{ route('dashboard', ['from_setup' => 1]) }}" class="twoToneBlueGreenBtn">
                        Vai alla dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function(){
            if (document.getElementById('cc-theme-toggle')) return;

            var isLight = document.body.classList.contains('light-mode');
            var btn = document.createElement('button');
            btn.id = 'cc-theme-toggle';
            btn.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:999999;padding:10px 18px;border-radius:20px;border:1px solid #d1d5db;background:#fff;color:#111;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,0.15);display:flex;align-items:center;gap:6px;';
            var ico = document.createElement('i');
            ico.className = isLight ? 'fa-solid fa-moon' : 'fa-solid fa-sun';
            ico.style.color = isLight ? '#fbbf24' : '#f59e0b';
            btn.appendChild(ico);
            btn.appendChild(document.createTextNode(isLight ? 'Dark' : 'Light'));
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
        });
    </script>
@endsection
