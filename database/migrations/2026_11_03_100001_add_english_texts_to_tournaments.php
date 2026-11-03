<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Angielska wersja opisu, zakładki „O turnieju”, zasad i informatora (dla gości z zagranicy); puste = wersja polska.
     */
    public function up(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            if (! Schema::hasColumn('tournaments', 'description_en')) {
                $table->text('description_en')->nullable();
            }
            foreach (['about_en', 'rules_en', 'guest_info_en'] as $column) {
                if (! Schema::hasColumn('tournaments', $column)) {
                    $table->longText($column)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn(['description_en', 'about_en', 'rules_en', 'guest_info_en']));
    }
};
