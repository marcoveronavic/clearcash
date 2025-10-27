<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('bank_accounts', 'plaid_item_id'))      $table->string('plaid_item_id')->nullable()->index();
            if (!Schema::hasColumn('bank_accounts', 'plaid_account_id'))   $table->string('plaid_account_id')->nullable()->index();
            if (!Schema::hasColumn('bank_accounts', 'plaid_access_token')) $table->text('plaid_access_token')->nullable();
            if (!Schema::hasColumn('bank_accounts', 'institution_name'))   $table->string('institution_name')->nullable();
            if (!Schema::hasColumn('bank_accounts', 'mask'))               $table->string('mask', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            foreach (['plaid_item_id','plaid_account_id','plaid_access_token','institution_name','mask'] as $col) {
                if (Schema::hasColumn('bank_accounts', $col)) $table->dropColumn($col);
            }
        });
    }
};
