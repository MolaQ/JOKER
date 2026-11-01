<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcjonalne wyróżnienie meczu (np. MVP) przy wyniku: mecze klubu i mecze turniejowe.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['games', 'tournament_matches'] as $table) {
            if (! Schema::hasColumn($table, 'mvp')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->string('mvp', 120)->nullable());
            }
        }
    }

    public function down(): void
    {
        foreach (['games', 'tournament_matches'] as $table) {
            if (Schema::hasColumn($table, 'mvp')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropColumn('mvp'));
            }
        }
    }
};
