<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bank_account_id')->constrained()->onDelete('cascade');
            $table->string('name');                       // es. "Vacanze", "Macchina nuova"
            $table->decimal('target_amount', 12, 2);      // obiettivo finale
            $table->string('icon')->default('fa-bullseye'); // icona FontAwesome
            $table->string('color')->default('#2DD4BF');    // colore accent
            $table->date('deadline')->nullable();           // scadenza opzionale
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_goals');
    }
};
