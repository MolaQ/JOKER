<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('season_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('league_standings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_id');
        });
    }
};
