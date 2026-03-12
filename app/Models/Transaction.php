<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'internal_transfer' => 'boolean',
        'date'              => 'date',
        'exchange_rate'     => 'decimal:6',
        'amount_native'     => 'decimal:4',
    ];

    /**
     * True se la transazione è in valuta diversa dalla base currency dell'utente.
     */
    public function isForeign(): bool
    {
        $baseCurrency = $this->bankAccount?->user?->base_currency ?? 'GBP';
        return ($this->currency ?? 'GBP') !== $baseCurrency;
    }

    public function category()
    {
        return $this->belongsTo(Budget::class, 'category_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
