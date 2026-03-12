<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PowensConnection extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw'          => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(BankAccount::class, 'powens_connection_id', 'powens_connection_id');
    }
}
