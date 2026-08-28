<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->integer('weight')->nullable()->after('height'); // kg
            $table->integer('reach')->nullable()->after('weight'); // zasięg w staniu/bloku, cm
            $table->integer('spike_reach')->nullable()->after('reach'); // zasięg w ataku, cm
        });
    }

    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            $table->dropColumn(['weight', 'reach', 'spike_reach']);
        });
    }
};
