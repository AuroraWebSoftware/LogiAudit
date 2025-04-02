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
        Schema::create('logiaudit_history', function (Blueprint $table) {
            $table->id();
            $table->string('action')->nullable(false)->index();
            $table->string('table')->nullable(false)->index();
            $table->string('model')->nullable(false)->index();
            $table->integer('model_id')->nullable(false);
            $table->jsonb('column')->nullable();
            $table->jsonb('old_value')->nullable();
            $table->jsonb('new_value')->nullable();
            $table->integer('user_id')->nullable()->index();
            $table->string('ip_address')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logiaudit_history');
    }
};
