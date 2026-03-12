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
        'powens_user_id',
        'powens_user_token',
        'base_currency',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'powens_user_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function currencySymbol(): string
    {
        return match($this->base_currency ?? 'GBP') {
            'GBP'   => '£',
            'EUR'   => '€',
            'USD'   => '$',
            'CHF'   => 'CHF',
            'JPY'   => '¥',
            default => $this->base_currency ?? 'GBP',
        };
    }

    public function customerDetails()
    {
        return $this->hasOne(CustomerAccountDetails::class, 'customer_id', 'id');
    }

    public function bankAccount()
    {
        return $this->hasMany(BankAccount::class);
    }
}
