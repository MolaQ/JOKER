<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Automatyczne godziny meczów: odstęp między meczami na sektorze (w minutach) dla kategorii
     * i godzina z planu, od której liczy się opóźnienie pokazywane kibicom.
     */
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->unsignedSmallInteger('match_interval')->default(90)->after('display_order');
        });

        Schema::table('tournament_matches', function (Blueprint $table) {
            $table->dateTime('planned_starts_at')->nullable()->after('starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_matches', fn (Blueprint $table) => $table->dropColumn('planned_starts_at'));
        Schema::table('tournament_categories', fn (Blueprint $table) => $table->dropColumn('match_interval'));
    }
};
