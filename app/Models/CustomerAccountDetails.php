<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAccountDetails extends Model
{
    protected $table = 'customer_account_details';

    protected $fillable = [
        'customer_id',
        'period_selection',
        'renewal_date',
        'custom_start',
        'custom_end',
    ];

    // se non hai timestamps in quella tabella, abilita/disabilita qui
    public $timestamps = true;
}
