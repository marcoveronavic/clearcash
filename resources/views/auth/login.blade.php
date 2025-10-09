@extends('layouts.login')

@section('content')
    <section class="loginPage">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-lg-3 col-md-8 offset-md-2">
                    <a href="{{ route('login') }}">
                        <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo">
                    </a>
                    <div class="card loginCard">
                        <div class="card-body">
                            @env('local')
                                <div class="btn-group loginLinkBtnGroup ">
                                    <x-login-link :user-attributes="['role', 'super admin']" email="super-admin@admin.com" label="Super Admin Login" redirect-url="{{ route('admin.dashboard') }}"/>
                                    <x-login-link :user-attributes="['role', 'staff']" email="teststaff@test.com" label="Staff Login" redirect-url="{{ route('staff.dashboard') }}"/>
                                    <x-login-link :user-attributes="['role', 'customer']" email="testcustomer@test.com" label="Customer Login" redirect-url="{{ route('dashboard') }}"/>
                                </div>
                            @endenv
                            <form method="POST" action="{{ route('login') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <label for="email">{{ __('Email Address') }}</label>
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <label for="password">{{ __('Password') }}</label>

                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        @if (Route::has('password.request'))
                                            <a class="btn btn-link passwordResetLink" href="{{ route('password.request') }}">
                                                {{ __('Forgot Your Password?') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary loginBtn">
                                            {{ __('Login') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                                <hr>
                        </div>
                        <div class="card-footer">
                            <p>
                                Don't have an account? <a href="{{ route('register') }}">Sign up</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
