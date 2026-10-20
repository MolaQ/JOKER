<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kluby rywali: grupa drużyn (np. „Energetyk Poznań 1”, „2”, „II”) ze wspólnym logo.
     * Drużyna rywali nie ma już kategorii, pamięta za to dawne nazwy z importu.
     */
    public function up(): void
    {
        Schema::create('rival_clubs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });

        Schema::table('rival_teams', function (Blueprint $table) {
            $table->foreignId('rival_club_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->json('aliases')->nullable()->after('short_name');
            $table->boolean('needs_review')->default(false)->after('logo_path');
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('rival_teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rival_club_id');
            $table->dropColumn(['aliases', 'needs_review']);
            $table->string('category')->nullable();
        });

        Schema::dropIfExists('rival_clubs');
    }
};
