<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->timestamp('publish_from')->nullable()->after('is_active');
            $table->timestamp('publish_to')->nullable()->after('publish_from');
            $table->index(['is_active', 'publish_from', 'publish_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'publish_from', 'publish_to']);
            $table->dropColumn(['publish_from', 'publish_to']);
        });
    }
};
