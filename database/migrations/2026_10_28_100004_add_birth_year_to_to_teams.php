<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drużyna może łączyć kilka roczników, np. 2010–2011 (birth_year = najstarszy, birth_year_to = najmłodszy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('birth_year_to')->nullable()->after('birth_year');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('birth_year_to');
        });
    }
};
