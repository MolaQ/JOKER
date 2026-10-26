<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drużyny wycofane z rozgrywek: ich dalsze mecze są odwoływane i znikają z kalendarza.
     */
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->json('withdrawn_teams')->nullable()->after('source_url');
        });
    }

    public function down(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('withdrawn_teams');
        });
    }
};
