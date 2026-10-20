<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opcjonalne angielskie wersje aktualności i podstron (pokazywane, gdy odwiedzający wybierze angielski).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->text('excerpt_en')->nullable()->after('excerpt');
            $table->longText('content_en')->nullable()->after('content');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('title_en')->nullable()->after('title');
            $table->string('lead_en')->nullable()->after('lead');
            $table->longText('content_en')->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'excerpt_en', 'content_en']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn(['title_en', 'lead_en', 'content_en']);
        });
    }
};
