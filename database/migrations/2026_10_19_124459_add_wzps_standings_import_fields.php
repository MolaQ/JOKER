<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabele pobierane ze związku mogą mieć kilka grup (np. młodzicy: Grupa 1 i Grupa 2).
     * Mecz derbowy naszych dwóch drużyn to dwa wpisy (po jednym na drużynę), dlatego
     * identyfikator meczu u źródła jest unikalny w obrębie drużyny, a nie rozgrywek.
     */
    public function up(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->string('group_name', 100)->nullable()->after('competition_id');
        });

        Schema::table('games', function (Blueprint $table) {
            // competition_id ma klucz obcy - MySQL potrzebuje dla niego indeksu przed usunięciem unikalnego.
            $table->index('competition_id', 'games_competition_id_index');
            $table->dropUnique(['competition_id', 'external_id']);
            $table->unique(['team_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->index('team_id', 'games_team_id_index');
            $table->dropUnique(['team_id', 'external_id']);
            $table->unique(['competition_id', 'external_id']);
            $table->dropIndex('games_competition_id_index');
        });

        Schema::table('league_standings', function (Blueprint $table) {
            $table->dropColumn('group_name');
        });
    }
};
