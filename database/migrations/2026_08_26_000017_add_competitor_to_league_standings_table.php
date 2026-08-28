<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wpis w tabeli ligowej może dotyczyć naszej drużyny albo drużyny rywala
     * (relacja polimorficzna). team_id zostaje wypełniane dodatkowo, gdy
     * competitor to nasza drużyna (wygodne zapytania po team_id).
     * is_manual_override blokuje nadpisanie wpisu przez automatyczne przeliczenie.
     */
    public function up(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->string('competitor_type')->nullable()->after('team_id');
            $table->unsignedBigInteger('competitor_id')->nullable()->after('competitor_type');
            $table->boolean('is_manual_override')->default(false)->after('points');

            $table->index(['competitor_type', 'competitor_id']);
        });

        // team_id musi być teraz opcjonalne (wpis może dotyczyć drużyny rywala).
        Schema::table('league_standings', function (Blueprint $table) {
            $table->unsignedBigInteger('team_id')->nullable()->change();
        });

        // Backfill: istniejące wpisy dotyczą zawsze naszych drużyn.
        DB::table('league_standings')->whereNull('competitor_type')->update([
            'competitor_type' => 'team',
        ]);

        DB::statement('UPDATE league_standings SET competitor_id = team_id WHERE competitor_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->dropIndex(['competitor_type', 'competitor_id']);
            $table->dropColumn(['competitor_type', 'competitor_id', 'is_manual_override']);
        });
    }
};
