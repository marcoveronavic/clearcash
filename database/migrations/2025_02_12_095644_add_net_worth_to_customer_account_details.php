<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customer_account_details', function (Blueprint $table) {
            $table->decimal('net_worth', 8, 2)->nullable()->after('renewal_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_account_details', function (Blueprint $table) {
            $table->dropColumn('net_worth');
        });
    }
};
