<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class   Transaction extends Model
{
    use HasFactory;

    protected $guarded = [];



    public function category(){
        return $this->belongsTo(Budget::class, 'category_id');
    }

      public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
}
