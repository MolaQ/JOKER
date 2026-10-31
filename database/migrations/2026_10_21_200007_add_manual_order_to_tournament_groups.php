<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolejność w tabeli grupy ustawiona ręcznie przez organizatora (np. po rozpisaniu małej tabeli).
     */
    public function up(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->json('manual_order')->nullable()->after('scoring');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_groups', function (Blueprint $table) {
            $table->dropColumn('manual_order');
        });
    }
};
