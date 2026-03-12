<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function currencySymbol(): string
    {
        return match($this->currency ?? 'GBP') {
            'GBP'   => '£',
            'EUR'   => '€',
            'USD'   => '$',
            'CHF'   => 'CHF',
            'JPY'   => '¥',
            default => $this->currency ?? 'GBP',
        };
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(\App\Models\Transaction::class, 'bank_account_id', 'id');
    }
}
