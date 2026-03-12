@extends('layouts.login')

@section('styles_in_head')
    <style>
        .authCard {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }
        .authCardHeader { text-align: center; margin-bottom: 28px; }
        .authLogo { height: 48px; margin-bottom: 16px; }
        .authCardHeader h1 { color: #fff; font-size: 1.8rem; font-weight: 800; margin-bottom: 6px; }
        .authCardHeader p { color: rgba(255,255,255,0.5); font-size: 0.95rem; margin: 0; }
        .authCard label { color: rgba(255,255,255,0.7); font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; display: block; }
        .authCard .form-control {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            padding: 12px 16px;
            font-size: 0.95rem;
            width: 100%;
        }
        .authCard .form-control:focus {
            background: rgba(255,255,255,0.09);
            border-color: #44E0AC;
            outline: none;
            box-shadow: 0 0 0 3px rgba(68,224,172,0.15);
            color: #fff;
        }
        .authCard .form-control option { background: #0f2629; color: #fff; }
        .authCard .form-control::placeholder { color: rgba(255,255,255,0.3); }
        .authCard .invalid-feedback { color: #ff6b6b; font-size: 0.82rem; margin-top: 4px; }
        .authCard .is-invalid { border-color: #ff6b6b !important; }
        .authCard .alert-danger {
            background: rgba(210,20,20,0.15);
            border: 1px solid rgba(210,20,20,0.3);
            color: #ff6b6b;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }
        .authCard .alert-danger ul { margin: 0; padding-left: 16px; }
        .twoToneBlueGreenBtn {
            background: linear-gradient(90deg, #33BBC5, #44E0AC);
            color: #04262a;
            font-weight: 800;
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            font-size: 1rem;
            cursor: pointer;
            transition: all .15s;
            width: 100%;
        }
        .twoToneBlueGreenBtn:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(68,224,172,0.3); }
    </style>
@endsection

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

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label for="first_name">Nome</label>
                                <input id="first_name" type="text"
                                       class="form-control @error('first_name') is-invalid @enderror"
                                       name="first_name" value="{{ old('first_name') }}"
                                       required autocomplete="given-name" placeholder="Mario">
                                @error('first_name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label for="last_name">Cognome</label>
                                <input id="last_name" type="text"
                                       class="form-control @error('last_name') is-invalid @enderror"
                                       name="last_name" value="{{ old('last_name') }}"
                                       required autocomplete="family-name" placeholder="Rossi">
                                @error('last_name')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email">Email</label>
                            <input id="email" type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}"
                                   required autocomplete="email" placeholder="mario@esempio.com">
                            @error('email')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password">Password</label>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password" placeholder="••••••••">
                            @error('password')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password-confirm">Conferma password</label>
                            <input id="password-confirm" type="password"
                                   class="form-control"
                                   name="password_confirmation" required
                                   autocomplete="new-password" placeholder="••••••••">
                        </div>

                        <div class="mb-4">
                            <label for="country">Paese</label>
                            <select id="country" name="country"
                                    class="form-control @error('country') is-invalid @enderror" required>
                                <option value="">-- Seleziona --</option>
                                <option value="GB" {{ old('country') === 'GB' ? 'selected' : '' }}>🇬🇧 Regno Unito (GBP)</option>
                                <option value="EU" {{ old('country') === 'EU' ? 'selected' : '' }}>🇪🇺 Europa (EUR)</option>
                            </select>
                            @error('country')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="twoToneBlueGreenBtn">Registrati</button>
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
