@extends('customer.layouts.main')
@section('styles_in_head')
    <link rel="stylesheet" href="{{asset('build/assets/account-setup.css')}}">
@endsection
@section('content')
    <style>
        header, aside.sidebar { display: none; }
        main.dashboardMain { padding-top: 2rem; width: 100%; }
        main.dashboardMain.full { padding-top: 2rem; }
        .setupStepsWrapper .setupStepsWrap .titles .item.active { color: #44E0AC; }
        .setupStepsWrapper .setupStepsWrap .boxes .box.active { background-color: #44E0AC; }
    </style>

    <section class="setupStepsWrapper">
        <div class="container">
            <div class="row mb-4">
                <div class="col-lg-8 offset-lg-2 col-md-10 offset-md-1">
                    <div class="setupStepsWrap">
                        <div class="titles">
                            <div class="item">{{ __('messages.setup_step_budget') }}</div>
                            <div class="sep"></div>
                            <div class="item">{{ __('messages.setup_step_banks') }}</div>
                            <div class="sep"></div>
                            <div class="item active">{{ __('messages.setup_step_done') }}</div>
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
                    <h1 class="mb-4">{{ __('messages.step7_title') }}</h1>
                    <p>{{ __('messages.step7_desc1') }}</p>
                    <p>{{ __('messages.step7_desc2') }}</p>
                    <a href="{{ route('dashboard') }}" class="twoToneBlueGreenBtn">
                        {{ __('messages.go_to_dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
@endsection
