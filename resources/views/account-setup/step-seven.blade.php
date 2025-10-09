@extends('layouts.customer')
@section('styles_in_head')
    {{-- Add your link below --}}
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
                <div class="col-12">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item ">Create your budget</div>
                            <div class="sep"></div>
                            <div class="item">Add bank accounts</div>
                            <div class="sep"></div>
                            <div class="item">Add your investments and pensions</div>
                            <div class="sep"></div>
                            <div class="item active">Done</div>
                        </div>
                        <div class="boxes">
                            <div class="box active"></div>
                            <div class="box active "></div>
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
@endsection
