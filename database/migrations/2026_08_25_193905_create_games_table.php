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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->string('opponent'); // przeciwnik
            $table->boolean('is_home')->default(true); // czy mecz u siebie
            $table->dateTime('game_date');
            $table->string('venue')->nullable(); // miejsce meczu
            $table->string('league')->nullable(); // np. "Tauron 1 Liga"
            $table->enum('status', ['scheduled', 'live', 'finished', 'cancelled'])->default('scheduled');
            $table->integer('home_score')->nullable();
            $table->integer('away_score')->nullable();
            $table->json('sets_score')->nullable(); // [25:23, 20:25, 25:22, ...]
            $table->text('match_report')->nullable(); // relacja z meczu
            $table->string('video_url')->nullable(); // link do transmisji/nagrania
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
