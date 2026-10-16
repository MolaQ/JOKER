<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Miniatura (zdjęcie) drużyny, funkcja członka sztabu (np. "Asystent trenera", "Fizjoterapeuta")
     * oraz opcjonalne logo sponsora (bez logo strona pokazuje nazwę).
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('logo_path');
        });

        Schema::table('team_trainers', function (Blueprint $table) {
            $table->string('role')->nullable()->after('user_id');
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });

        Schema::table('team_trainers', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('logo_path')->nullable(false)->change();
        });
    }
};
