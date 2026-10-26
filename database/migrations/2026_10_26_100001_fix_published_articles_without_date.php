<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Artykuły zapisane jako „Opublikowany” bez daty publikacji nie pokazywały się na stronie.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('articles')->where('status', 'published')->whereNull('published_at')
            ->update(['published_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void {}
};
