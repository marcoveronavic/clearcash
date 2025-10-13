<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $table = 'budgets';

    // Consenti l’assegnazione di massa dei campi usati dai controller
    protected $fillable = [
        'user_id',
        'category_id',
        'category_name',
        'amount',
        'budget_start_date',
        'budget_end_date',
        'include_internal_transfers', // ✅ nuovo flag
    ];

    protected $casts = [
        'amount'                => 'float',
        'budget_start_date'     => 'datetime',
        'budget_end_date'       => 'datetime',
        'include_internal_transfers' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        // FK esplicita per chiarezza
        return $this->belongsTo(BudgetCategory::class, 'category_id');
    }

    // Accessor opzionale: remaining = amount - spent (se 'spent' è stato valorizzato in query)
    public function getRemainingAttribute()
    {
        $spent = (float) ($this->getAttribute('spent') ?? 0);
        return (float) $this->amount - $spent;
    }
}
