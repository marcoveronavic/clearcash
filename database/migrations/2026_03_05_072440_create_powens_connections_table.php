<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('powens_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('powens_connection_id')->unique(); // ID connessione su Powens
            $table->unsignedBigInteger('powens_connector_id')->nullable(); // ID del connettore (banca)
            $table->string('institution_name', 191)->nullable();
            $table->string('state', 64)->nullable(); // null = ok, SCARequired, error, ecc.
            $table->string('error_message', 512)->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->json('raw')->nullable(); // payload Powens per debug
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('powens_connections');
    }
};
