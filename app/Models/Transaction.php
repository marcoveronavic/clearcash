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
    ];

    public function category()
    {
        // Nota: in molti progetti questo punterebbe a BudgetCategory,
        // ma lasciamo Budget per compatibilità con il resto della tua app.
        return $this->belongsTo(Budget::class, 'category_id');
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
