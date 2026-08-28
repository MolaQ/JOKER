<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skład drużyny w danym sezonie. Jeden zawodnik może grać w kilku drużynach
     * (np. młodszy zawodnik grający dodatkowo ze starszym rocznikiem),
     * a skład może się różnić między sezonami.
     */
    public function up(): void
    {
        Schema::create('team_player_season', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->integer('jersey_number')->nullable(); // numer w tej konkretnej drużynie
            $table->boolean('is_captain')->default(false);
            $table->timestamps();

            $table->unique(['team_id', 'player_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_player_season');
    }
};
