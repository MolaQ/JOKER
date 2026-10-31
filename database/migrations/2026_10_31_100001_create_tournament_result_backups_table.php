<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kopie wyników kategorii turnieju robione przed wyczyszczeniem (do przywrócenia).
     */
    public function up(): void
    {
        if (Schema::hasTable('tournament_result_backups')) {
            return;
        }

        Schema::create('tournament_result_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('results_count')->default(0);
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_result_backups');
    }
};
