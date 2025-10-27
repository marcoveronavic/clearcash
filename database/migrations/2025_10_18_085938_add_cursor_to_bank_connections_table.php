<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // Aggiungo colonne solo se mancano (utile con SQLite)
            if (!Schema::hasColumn('bank_connections', 'transactions_cursor')) {
                // cursor di Plaid /transactions/sync
                $table->text('transactions_cursor')->nullable();
            }
            if (!Schema::hasColumn('bank_connections', 'last_synced_at')) {
                $table->timestamp('last_synced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // Nota: su alcune versioni di SQLite il dropColumn richiede DBAL; se fallisce è ok in dev.
            if (Schema::hasColumn('bank_connections', 'last_synced_at')) {
                $table->dropColumn('last_synced_at');
            }
            if (Schema::hasColumn('bank_connections', 'transactions_cursor')) {
                $table->dropColumn('transactions_cursor');
            }
        });
    }
};
