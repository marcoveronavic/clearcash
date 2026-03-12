@extends('layouts.login')

@section('content')
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height:100vh;">
            <div class="col-md-6 col-lg-5">
                <div class="authCard">
                    <div class="authCardHeader">
                        <img src="{{ asset('images/logo/clear-cash-logo-dark.svg') }}" alt="ClearCash" class="authLogo">
                        <h1>Crea un account</h1>
                        <p>Inizia a gestire le tue finanze</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="first_name">Nome</label>
                                    <input id="first_name" type="text" class="form-control @error('first_name') is-invalid @enderror"
                                           name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
                                    @error('first_name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="last_name">Cognome</label>
                                    <input id="last_name" type="text" class="form-control @error('last_name') is-invalid @enderror"
                                           name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                                    @error('last_name')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="email">Email</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="password">Password</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password">
                            @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="password-confirm">Conferma password</label>
                            <input id="password-confirm" type="password" class="form-control"
                                   name="password_confirmation" required autocomplete="new-password">
                        </div>

                        <div class="form-group mb-3">
                            <label for="country">Paese</label>
                            <select id="country" name="country" class="form-control @error('country') is-invalid @enderror" required>
                                <option value="">-- Seleziona --</option>
                                <option value="GB" {{ old('country') === 'GB' ? 'selected' : '' }}>🇬🇧 Regno Unito (GBP)</option>
                                <option value="EU" {{ old('country') === 'EU' ? 'selected' : '' }}>🇪🇺 Europa (EUR)</option>
                            </select>
                            @error('country')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="form-group mt-4">
                            <button type="submit" class="twoToneBlueGreenBtn w-100">
                                Registrati
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <span style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Hai già un account?</span>
                            <a href="{{ route('login') }}" style="color:#44E0AC;font-weight:600;font-size:0.9rem;margin-left:6px;">Accedi</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
