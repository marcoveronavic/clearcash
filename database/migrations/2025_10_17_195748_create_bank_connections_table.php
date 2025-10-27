<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();
            // user_id opzionale (evitiamo vincoli finché non sappiamo il nome della tua users table)
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('item_id', 128)->unique();   // Plaid item_id
            $table->text('access_token');               // lo castiamo encrypted nel Model
            $table->string('institution_id', 64)->nullable();
            $table->string('institution_name', 191)->nullable();

            $table->json('raw')->nullable();            // payload Plaid salvato per audit/debug
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
