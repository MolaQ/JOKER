<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Turniej w kilku halach: dodatkowe hale z boiskami (klubowa hala z sektorami jest zawsze)
     * i hala, w której domyślnie gra kategoria.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('tournaments', 'halls')) {
            Schema::table('tournaments', function (Blueprint $table) {
                $table->json('halls')->nullable();
            });
        }

        if (! Schema::hasColumn('tournament_categories', 'hall')) {
            Schema::table('tournament_categories', function (Blueprint $table) {
                $table->string('hall', 80)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tournaments', fn (Blueprint $table) => $table->dropColumn('halls'));
        Schema::table('tournament_categories', fn (Blueprint $table) => $table->dropColumn('hall'));
    }
};
