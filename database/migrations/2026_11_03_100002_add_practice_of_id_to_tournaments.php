<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turniej ćwiczebny dla wpisujących wyniki: kopia prawdziwego turnieju, niewidoczna dla kibiców.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'practice_of_id')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->foreignId('practice_of_id')->nullable()->constrained('tournaments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('practice_of_id');
        });
    }
};
