<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Poziomy rozgrywek (np. "3 liga", "wojewódzka") jako zarządzalna lista,
     * zamiast dowolnego tekstu.
     */
    public function up(): void
    {
        Schema::create('competition_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competition_levels');
    }
};
