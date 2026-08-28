<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Rocznik przypisany do drużyny (np. Junior 2008). Puste dla Seniorów - gra tam może każdy zawodnik klubu.
            $table->integer('birth_year')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('birth_year');
        });
    }
};
