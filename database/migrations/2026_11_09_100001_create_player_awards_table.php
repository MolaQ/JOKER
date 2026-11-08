<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nagrody indywidualne zawodnika z imprez spoza turniejów prowadzonych na stronie (wyróżnienie, impreza, data, miejsce).
 * Tylko nowa tabela: nic nie zmienia się w istniejących danych.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::hasTable('player_awards') || Schema::create('player_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('event', 200)->nullable();
            $table->date('awarded_on')->nullable();
            $table->string('place', 120)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_awards');
    }
};
