<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relacja z turnieju: artykuł pokazuje link do strony turnieju, a strona turnieju listę relacji.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('articles', 'tournament_id')) {
            Schema::table('articles', function (Blueprint $table) {
                $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tournament_id');
        });
    }
};
