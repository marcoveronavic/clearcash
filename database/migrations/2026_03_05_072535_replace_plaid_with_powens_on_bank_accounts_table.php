<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiungi colonne Powens
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('powens_account_id')->nullable()->unique()->after('user_id');
            $table->unsignedBigInteger('powens_connection_id')->nullable()->index()->after('powens_account_id');
            $table->string('iban', 64)->nullable()->after('powens_connection_id');
            $table->string('currency', 8)->nullable()->default('EUR')->after('iban');
        });

        // Rimuovi colonne Plaid una alla volta
        if (Schema::hasColumn('bank_accounts', 'plaid_item_id')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('plaid_item_id');
            });
        }

        if (Schema::hasColumn('bank_accounts', 'plaid_account_id')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('plaid_account_id');
            });
        }

        if (Schema::hasColumn('bank_accounts', 'plaid_access_token')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->dropColumn('plaid_access_token');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['powens_account_id', 'powens_connection_id', 'iban', 'currency']);
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('plaid_item_id')->nullable()->index();
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('plaid_account_id')->nullable()->index();
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->text('plaid_access_token')->nullable();
        });
    }
};
