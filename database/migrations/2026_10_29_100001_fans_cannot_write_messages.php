<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Kibic nie pisze wiadomości (może je tylko dostawać). Pozostałe role piszą „każdy do każdego”.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('role_permissions')->where('role', 'fan')->where('permission', 'messages.write')->delete();
        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->insertOrIgnore(['role' => 'fan', 'permission' => 'messages.write']);
        Cache::forget('role_permissions');
    }
};
