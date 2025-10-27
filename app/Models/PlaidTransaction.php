<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlaidTransaction extends Model
{
    protected $table = 'plaid_transactions';

    protected $fillable = [
        'bank_connection_id',
        'account_id',
        'transaction_id',
        'pending_transaction_id',
        'amount',
        'iso_currency_code',
        'unofficial_currency_code',
        'date',
        'authorized_date',
        'datetime',
        'authorized_datetime',
        'name',
        'merchant_name',
        'merchant_entity_id',
        'payment_channel',
        'transaction_type',
        'transaction_code',
        'check_number',
        'pending',
        'logo_url',
        'website',
        'category',
        'counterparties',
        'personal_finance_category',
        'location',
        'raw',
        'is_removed',
    ];

    protected $casts = [
        'amount'                    => 'decimal:2',
        'date'                      => 'date',
        'authorized_date'           => 'date',
        'datetime'                  => 'datetime',
        'authorized_datetime'       => 'datetime',
        'pending'                   => 'boolean',
        'is_removed'                => 'boolean',
        'category'                  => 'array',
        'counterparties'            => 'array',
        'personal_finance_category' => 'array',
        'location'                  => 'array',
        'raw'                       => 'array',
    ];
}
