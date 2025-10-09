@extends('layouts.login')

@section('content')
    <section class="forgotPasswordPage">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 offset-md-3">
                    <a href="{{ route('login') }}">
                        <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo">
                    </a>
                    <div class="card forgotPasswordCard">
                        <div class="card-header">
                            <h1>Reset your password</h1>
                            <p>Enter the email associated with your account and we'll send you a reset link.</p>
                        </div>
                        <div class="card-body">
                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif
                            <form method="POST" action="{{ route('password.email') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <label for="email" class="">{{ __('Email') }}</label>
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
                                        <button type="submit" class="btn btn-primary loginBtn">
                                            {{ __('Send Password Reset Link') }}
                                        </button>
                                    </div>
                                </div>


                            </form>
                                <hr>
                        </div>
                        <div class="card-footer">
                            <p>
                                Remember your password? <a href="{{ route('login') }}">Login</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
