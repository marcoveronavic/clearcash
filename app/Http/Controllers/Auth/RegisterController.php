<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/email/verify';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $user = $this->create($request->all());

        $user->assignRole('customer');

        event(new Registered($user));

        return redirect()->route('verification.notice');
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name'  => ['required', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'country'     => ['required', 'string', 'in:GB,EU'],
        ]);
    }

    protected function create(array $data)
    {
        $username = strtolower($data['first_name'] . $data['last_name']);
        $originalUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        // Determina base_currency dal paese selezionato
        $baseCurrency = ($data['country'] ?? 'GB') === 'GB' ? 'GBP' : 'EUR';

        $user = User::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'full_name'     => $data['first_name'] . ' ' . $data['last_name'],
            'username'      => $username,
            'email'         => $data['email'],
            'password'      => Hash::make($data['password']),
            'base_currency' => $baseCurrency,
        ]);

        $user->assignRole('customer');

        return $user;
    }
}
