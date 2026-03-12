<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('currency', 3)->default('GBP')->after('amount');
            $table->decimal('amount_native', 15, 4)->nullable()->after('currency');
            $table->decimal('exchange_rate', 15, 6)->default(1.000000)->after('amount_native');
        });

        // Popola i record esistenti
        DB::statement('UPDATE transactions SET amount_native = amount, exchange_rate = 1.000000 WHERE amount_native IS NULL');
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['currency', 'amount_native', 'exchange_rate']);
        });
    }
};
