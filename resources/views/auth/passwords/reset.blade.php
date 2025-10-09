@extends('layouts.login')

@section('content')
    <section class="forgotPasswordPage">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-md-3">
                    <a href="{{ route('login') }}">
                        <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo">
                    </a>
                    <div class="card resetPasswordCard">
                        <div class="card-header">
                            <h1>Reset your password</h1>
                            <p>Let's get you back in.  Enter your email address and your new password below.</p>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('password.update') }}">
                                @csrf

                                <input type="hidden" name="token" value="{{ $token }}">
                                <div class="row">
                                    <div class="col-12">
                                        <label for="email">{{ __('Email Address') }}</label>
                                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>

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
                                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                        @error('password')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <label for="password-confirm">{{ __('Confirm Password') }}</label>
                                        <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary loginBtn">
                                            {{ __('Reset Password') }}
                                        </button>
                                    </div>
                                </div>


                                <div class="row mb-3">

                                </div>

                                <div class="row mb-0">
                                    <div class="col-md-6 offset-md-4">

                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
