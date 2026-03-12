<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite non supporta ALTER COLUMN, quindi ricreiamo il vincolo
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->change();
        });

        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable(false)->change();
        });
    }
};
