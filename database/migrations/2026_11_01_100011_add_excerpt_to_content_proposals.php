<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lead (zajawka) w propozycji relacji: trafia do artykułu jako wstęp.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('content_proposals', 'excerpt')) {
            Schema::table('content_proposals', function (Blueprint $table) {
                $table->text('excerpt')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('content_proposals', fn (Blueprint $table) => $table->dropColumn('excerpt'));
    }
};
