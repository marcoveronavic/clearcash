<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankConnection extends Model
{
    use HasFactory;

    protected $table = 'bank_connections';

    protected $fillable = [
        'user_id',
        'item_id',
        'access_token',
        'institution_id',
        'institution_name',
        'raw',
        'transactions_cursor', // <-- usato da transactionsSyncStore
        'last_synced_at',      // <-- timestamp ultima sync
    ];

    protected $casts = [
        'access_token'     => 'encrypted', // cifra/decifra in automatico
        'raw'              => 'array',
        'last_synced_at'   => 'datetime',
    ];

    protected $hidden = [
        'access_token', // non esporre via API
    ];

    // (opzionale) relazione con User, se esiste App\Models\User
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
