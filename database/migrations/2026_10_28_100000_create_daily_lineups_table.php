<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skład dnia na stronie głównej: jedno losowanie na dzień, takie samo dla wszystkich odwiedzających.
     */
    public function up(): void
    {
        Schema::create('daily_lineups', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            // [{"slot": "outside_hitter", "player_id": 12}, ...] w kolejności miejsc na boisku.
            $table->json('players');
            $table->foreignId('coach_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('captain_id')->nullable()->constrained('players')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_lineups');
    }
};
