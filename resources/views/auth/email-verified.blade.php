@extends('layouts.login')

@section('content')
    <section class="emailVerifyPage">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    {{-- <a href="{{ route('login') }}"> --}}
                        <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo">
                    {{-- </a> --}}
                    <div class="card emailVerifyCard">
                        <div class="card-header" style="padding: 0;">

                            <h1>Your email has been verified</h1>
                            <a class="continueAccSetupBtn" href="{{ route('account-setup.step-one') }}">Set up your account</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
