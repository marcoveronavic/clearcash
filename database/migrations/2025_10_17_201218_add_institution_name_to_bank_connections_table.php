<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // Aggiungi le colonne solo se mancano (utile perché sei su SQLite)
            if (!Schema::hasColumn('bank_connections', 'institution_id')) {
                $table->string('institution_id', 64)->nullable();
            }
            if (!Schema::hasColumn('bank_connections', 'institution_name')) {
                $table->string('institution_name', 191)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_connections', function (Blueprint $table) {
            // La drop potrebbe non essere supportata su alcune versioni di SQLite: è ok, serve solo per rollback
            if (Schema::hasColumn('bank_connections', 'institution_name')) {
                $table->dropColumn('institution_name');
            }
            if (Schema::hasColumn('bank_connections', 'institution_id')) {
                $table->dropColumn('institution_id');
            }
        });
    }
};
