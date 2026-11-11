<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drugie logo rywala (klubu i drużyny): wariant na ciemne tło, np. granatowe logo przerobione na białe do miniatur transmisji.
 * Tylko nowa, pusta kolumna: nic nie zmienia się w istniejących danych.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['rival_clubs', 'rival_teams'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'logo_light_path')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('logo_light_path')->nullable()->after('logo_path');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['rival_clubs', 'rival_teams'] as $table) {
            if (Schema::hasColumn($table, 'logo_light_path')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('logo_light_path');
                });
            }
        }
    }
};
