<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Import terminarza ze strony związku (np. WZPS): adres źródła w rozgrywkach,
     * nazwa naszej drużyny używana w danych rozgrywkach oraz identyfikator meczu u źródła.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->text('source_url')->nullable()->after('description');
            $table->timestamp('synced_at')->nullable()->after('source_url');
            $table->string('sync_message', 500)->nullable()->after('synced_at');
        });

        Schema::table('competition_team', function (Blueprint $table) {
            $table->string('external_name')->nullable()->after('team_id');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->string('external_id', 64)->nullable()->after('competition_id');
            $table->unique(['competition_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropUnique(['competition_id', 'external_id']);
            $table->dropColumn('external_id');
        });

        Schema::table('competition_team', function (Blueprint $table) {
            $table->dropColumn('external_name');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'synced_at', 'sync_message']);
        });
    }
};
