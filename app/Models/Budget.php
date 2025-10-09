<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Budget extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Update formula fields
    protected $fillable = [
        'id',
        'category_id',
        'amount',
        'user_id',
        'category_name',
        'created_at',
        'updated_at',
        'budget_start_date',
        'budget_end_date',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function category(){
        return $this->belongsTo(BudgetCategory::class);
    }

    // Accessor for remaining amount based on formula
    public function getRemainingAttribute()
    {
        return $this->amount - $this->spent;
    }
}
