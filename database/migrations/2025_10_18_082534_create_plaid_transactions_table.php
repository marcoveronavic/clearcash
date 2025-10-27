<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plaid_transactions', function (Blueprint $table) {
            $table->id();

            // Collegamenti
            $table->unsignedBigInteger('bank_connection_id')->index(); // -> references id on bank_connections (no FK hard per SQLite)
            $table->string('account_id', 191)->index();                // Plaid account_id

            // Identificativi Plaid
            $table->string('transaction_id', 191)->unique();           // Plaid transaction_id (stable)
            $table->string('pending_transaction_id', 191)->nullable()->index();

            // Importi e valute
            $table->decimal('amount', 18, 2);                          // Plaid amount: positivo per uscite (NB: tuo schema potrebbe usare negativi)
            $table->string('iso_currency_code', 8)->nullable();
            $table->string('unofficial_currency_code', 8)->nullable();

            // Date
            $table->date('date')->index();                              // data “postata”
            $table->date('authorized_date')->nullable();
            $table->dateTime('datetime')->nullable();
            $table->dateTime('authorized_datetime')->nullable();

            // Descrizioni/merchant
            $table->string('name', 512)->nullable()->index();
            $table->string('merchant_name', 512)->nullable();
            $table->string('merchant_entity_id', 191)->nullable();
            $table->string('payment_channel', 64)->nullable();          // in store | online | other
            $table->string('transaction_type', 64)->nullable();         // place | digital | special | etc.
            $table->string('transaction_code', 64)->nullable();
            $table->string('check_number', 64)->nullable();
            $table->boolean('pending')->default(false)->index();

            // Metadati utili
            $table->string('logo_url', 1024)->nullable();
            $table->string('website', 1024)->nullable();

            // Strutture JSON (SQLite le salva come TEXT, ma Laravel cast->array funziona)
            $table->json('category')->nullable();                       // Plaid legacy category
            $table->json('counterparties')->nullable();                 // Array di controparti
            $table->json('personal_finance_category')->nullable();      // PFC object
            $table->json('location')->nullable();                       // Address, lat/lon, ecc.
            $table->json('raw')->nullable();                            // payload integro per audit

            // Gestione rimozioni Plaid
            $table->boolean('is_removed')->default(false)->index();     // true se ricevuta in "removed" da /transactions/sync

            $table->timestamps();

            // Indici mirati
            $table->index(['bank_connection_id', 'date']);
            $table->index(['bank_connection_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plaid_transactions');
    }
};
