<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minisiatkówka: link do filmu (YouTube) z relacji turnieju, tak jak transmisje meczów.
     */
    public function up(): void
    {
        if (Schema::hasTable('mini_trips') && ! Schema::hasColumn('mini_trips', 'streams')) {
            Schema::table('mini_trips', function (Blueprint $table) {
                $table->json('streams')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mini_trips', 'streams')) {
            Schema::table('mini_trips', function (Blueprint $table) {
                $table->dropColumn('streams');
            });
        }
    }
};
