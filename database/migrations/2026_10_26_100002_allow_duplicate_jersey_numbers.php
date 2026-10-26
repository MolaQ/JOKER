<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Numer koszulki zwykle jest unikalny w drużynie, ale zdarzają się wyjątki:
 * zamiast blokady panel ostrzega o powtórzonym numerze.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // Klucz obcy drużyny potrzebuje własnego indeksu, zanim usuniemy wspólny.
            $table->index('team_id');
            $table->index(['team_id', 'jersey_number']);
            $table->dropUnique(['team_id', 'jersey_number']);
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->unique(['team_id', 'jersey_number']);
            $table->dropIndex(['team_id', 'jersey_number']);
            $table->dropIndex(['team_id']);
        });
    }
};
