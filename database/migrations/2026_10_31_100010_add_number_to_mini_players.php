<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Numer dziecka na koszulce; na kafelku zamiast inicjałów.
        if (! Schema::hasColumn('mini_players', 'number')) {
            Schema::table('mini_players', function (Blueprint $table) {
                $table->unsignedTinyInteger('number')->nullable()->after('birth_year');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mini_players', function (Blueprint $table) {
            $table->dropColumn('number');
        });
    }
};
