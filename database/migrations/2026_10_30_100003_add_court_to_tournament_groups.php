<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Domyślny sektor grupy: dostają go mecze grupy przy układaniu harmonogramu.
     */
    public function up(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->string('court', 60)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_groups', fn (Blueprint $table) => $table->dropColumn('court'));
    }
};
