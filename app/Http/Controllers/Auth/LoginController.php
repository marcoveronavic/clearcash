<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    protected function authenticated(Request $request, $user)
    {
        if ($user->hasRole(['super admin', 'admin'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->hasRole('staff')) {
            return redirect()->route('staff.dashboard');
        }
        if ($user->hasRole('customer')) {
            return redirect()->route('dashboard');
        }

        Auth::logout();
        return redirect('/login')->withErrors([
            'email' => 'Account senza ruolo. Contatta l’admin.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->loggedOut($request) ?: redirect('/login');
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
