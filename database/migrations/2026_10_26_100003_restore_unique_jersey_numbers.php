<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Numer koszulki jest unikalny w obrębie drużyny (w klubie może się powtarzać między drużynami).
 * Przywraca ograniczenie tam, gdzie wcześniejsza wersja je zdjęła; przy istniejących dublach
 * pomija je, a panel zawodników pokazuje je do poprawienia.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasIndex('players', ['team_id', 'jersey_number'], 'unique')) {
            return;
        }

        $duplicates = DB::table('players')->whereNotNull('team_id')->whereNotNull('jersey_number')
            ->select('team_id', 'jersey_number')->groupBy('team_id', 'jersey_number')->havingRaw('count(*) > 1')->exists();

        if (! $duplicates) {
            Schema::table('players', fn (Blueprint $table) => $table->unique(['team_id', 'jersey_number']));
        }
    }

    public function down(): void {}
};
