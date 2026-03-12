@extends('layouts.customer')
@section('styles_in_head')
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header,
        aside.sidebar {
            display: none;
        }
        main.dashboardMain {
            padding-top: 2rem;
            width: 100%;
        }
        main.dashboardMain.full {
            padding-top: 2rem;
        }
        .setupStepsWrapper .setupStepsWrap .titles .item.active {
            color: #44E0AC;
        }
        .setupStepsWrapper .setupStepsWrap .boxes .box.active {
            background-color: #44E0AC;
        }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item active">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box active"></div>
                            <div class="box active"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <h1 class="mb-4">All done!  Your account is ready</h1>
                    <p>
                        Your Clear Cash account is now set up and ready to go.
                    </p>
                    <p>
                        The next step is to set up your recurring payments and start adding transactions.
                    </p>
                    <a href="{{ route('dashboard') }}" class="twoToneBlueGreenBtn">
                        Go to dashboard
                    </a>
                </div>
            </div>
        </div>
    </section>
    <script>
        (function(){
            var btn = document.createElement('button');
            var ico = document.createElement('i'); ico.className = document.body.classList.contains('light-mode') ? 'fa-solid fa-moon' : 'fa-solid fa-sun'; ico.style.marginRight = '6px'; ico.style.color = document.body.classList.contains('light-mode') ? '#fbbf24' : '#f59e0b'; btn.appendChild(ico); btn.appendChild(document.createTextNode(document.body.classList.contains('light-mode') ? ' Dark' : ' Light'));
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
