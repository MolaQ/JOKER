<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Media społecznościowe trenerów i sztabu (te same serwisy co u zawodników). Dotychczasowy Instagram
     * trafia do nowej listy tylko tam, gdzie lista jest pusta; nic nie jest kasowane.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'social_links')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('social_links')->nullable();
            });
        }

        DB::table('users')->whereNull('social_links')->whereNotNull('instagram')->where('instagram', '!=', '')
            ->select(['id', 'instagram'])->orderBy('id')
            ->each(function (object $user) {
                $value = trim((string) $user->instagram);
                $url = preg_match('#^https?://#i', $value) ? $value : 'https://www.instagram.com/'.ltrim($value, '@/');
                DB::table('users')->where('id', $user->id)->whereNull('social_links')->update(['social_links' => json_encode(['instagram' => $url])]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'social_links')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('social_links');
            });
        }
    }
};
