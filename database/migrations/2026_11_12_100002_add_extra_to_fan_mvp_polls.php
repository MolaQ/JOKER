<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kibicowskie MVP: zawodnicy dopisani do głosowania spoza składu drużyny (np. junior, który zagrał z seniorami).
     */
    public function up(): void
    {
        if (Schema::hasTable('fan_mvp_polls') && ! Schema::hasColumn('fan_mvp_polls', 'extra')) {
            Schema::table('fan_mvp_polls', function (Blueprint $table) {
                $table->json('extra')->nullable()->after('excluded');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fan_mvp_polls', 'extra')) {
            Schema::table('fan_mvp_polls', function (Blueprint $table) {
                $table->dropColumn('extra');
            });
        }
    }
};
