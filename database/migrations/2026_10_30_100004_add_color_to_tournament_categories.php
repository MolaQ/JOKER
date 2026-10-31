<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolor kategorii w podglądzie obłożenia sektorów.
     */
    public function up(): void
    {
        Schema::table('tournament_categories', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('tournament_categories', fn (Blueprint $table) => $table->dropColumn('color'));
    }
};
