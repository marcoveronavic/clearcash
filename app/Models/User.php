<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    // 🔐 Spatie: forza il guard corretto per ruoli/permessi
    protected $guard_name = 'web';

    protected $fillable = [
        'first_name',
        'last_name',
        'full_name',
        'username',
        'email',
        'password',
        'avatar',
        'has_completed_setup',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function customerDetails()
    {
        return $this->hasOne(CustomerAccountDetails::class, 'customer_id', 'id');
    }

    public function bankAccount()
    {
        return $this->hasMany(BankAccount::class);
    }
}
