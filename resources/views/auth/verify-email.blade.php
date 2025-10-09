@extends('layouts.login')

@section('content')
    <section class="emailVerifyPage">
        <div class="container">
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <a href="{{ route('login') }}">
                        <img src="{{ asset('images/logo/clear-cash-logo.svg') }}" alt="" class="img-fluid logo">
                    </a>
                    <div class="card emailVerifyCard">
                        <div class="card-header">
                            @if (session('resent'))
                                <div class="alert alert-success" role="alert">
                                    {{ __('A fresh verification link has been sent to your email address.') }}
                                </div>
                            @endif
                            <h1>Verify your email</h1>
                            <p>
                                You're almost there!  We've just sent an email to {{ Auth::user()->email ?? 'your email address' }}.
                            </p>
                            <p>
                                Click the link in that email to verify your email address and complete your signup.  If you don't see it, please check your spam folder.
                            </p>
                            <p>Still don't see it?</p>
                            <form class="resendVeriEmailForm" method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('Click here to request another') }}</button>.
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
