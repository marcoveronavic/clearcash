<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('budgets', 'include_internal_transfers')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->boolean('include_internal_transfers')->default(false)->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('budgets', 'include_internal_transfers')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropColumn('include_internal_transfers');
            });
        }
    }
};
