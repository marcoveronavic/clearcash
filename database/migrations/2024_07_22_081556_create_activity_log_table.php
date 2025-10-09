<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->create(config('activitylog.table_name'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable();
                $table->text('description');
                
                // Manual morphs with shorter length
                $table->string('subject_type', 125)->nullable();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->index(['subject_type', 'subject_id'], 'subject');

                $table->string('causer_type', 125)->nullable();
                $table->unsignedBigInteger('causer_id')->nullable();
                $table->index(['causer_type', 'causer_id'], 'causer');

                $table->json('properties')->nullable();
                $table->timestamps();

                $table->index('log_name');
            });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))
            ->dropIfExists(config('activitylog.table_name'));
    }
}
