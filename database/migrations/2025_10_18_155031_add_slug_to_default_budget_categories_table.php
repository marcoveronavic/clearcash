<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Aggiungo la colonna slug (nullable per poter fare il backfill su righe esistenti)
        if (!Schema::hasColumn('default_budget_categories', 'slug')) {
            Schema::table('default_budget_categories', function (Blueprint $table) {
                $table->string('slug')->nullable();
            });
        }

        // Backfill: ricavo lo slug dal name (formato con underscore per combaciare con i tuoi seed: es. "Eating out" -> "eating_out")
        $rows = DB::table('default_budget_categories')->select('id', 'name', 'slug')->get();

        foreach ($rows as $row) {
            if (empty($row->slug)) {
                $slug = Str::slug((string) $row->name, '_');

                // Evito collisioni forzando un suffisso numerico se necessario
                $base   = $slug;
                $suffix = 1;
                while (
                DB::table('default_budget_categories')
                    ->where('slug', $slug)
                    ->where('id', '!=', $row->id)
                    ->exists()
                ) {
                    $slug = $base . '_' . $suffix++;
                }

                DB::table('default_budget_categories')
                    ->where('id', $row->id)
                    ->update(['slug' => $slug]);
            }
        }

        // Rendo lo slug unico (crea un indice UNIQUE). Con SQLite non possiamo "after()".
        // Se l'unico non esiste già, lo aggiungo.
        // Nota: drop/creazione indice è idempotente in down().
        try {
            Schema::table('default_budget_categories', function (Blueprint $table) {
                $table->unique('slug');
            });
        } catch (\Throwable $e) {
            // Se l'indice esiste già o l'operazione non è necessaria, ignora.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tolgo l'indice UNIQUE (se presente) e poi la colonna
        try {
            Schema::table('default_budget_categories', function (Blueprint $table) {
                $table->dropUnique(['slug']);
            });
        } catch (\Throwable $e) {
            // indice non presente: ignora
        }

        if (Schema::hasColumn('default_budget_categories', 'slug')) {
            Schema::table('default_budget_categories', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
