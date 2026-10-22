<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skład grupy z reguł (np. „1. miejsce – Grupa A”, „Zwycięzca #7”, konkretna drużyna)
     * i reguły grupy: które miejsca z tabeli trafiają do klasyfikacji końcowej.
     */
    public function up(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->json('slots')->nullable()->after('name');
            $table->json('final_places')->nullable()->after('slots');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->dropColumn(['slots', 'final_places']);
        });
    }
};
