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
                            <h1>Check your inbox!</h1>
                            <p>If an account exists for the email address you provided, you'll get instruction on resetting your password in the next 5 minutes.</p>
                            <p>
                                Didn't get the email?  Check your spam folder or make sure you've typed your email address correctly and <a href="{{ route('password.request') }}" class="resetPasswordLink">try resending the email</a>.
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
