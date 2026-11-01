<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Części hali klubowej niedostępne w danym turnieju (np. główne boisko, gdy sala jest podzielona na trzy sektory).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'main_off')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->json('main_off')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn('main_off'));
    }
};
