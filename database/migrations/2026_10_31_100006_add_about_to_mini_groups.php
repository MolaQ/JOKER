<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mini_groups', 'about')) {
            Schema::table('mini_groups', function (Blueprint $table) {
                $table->text('about')->nullable()->after('lead');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mini_groups', function (Blueprint $table) {
            $table->dropColumn('about');
        });
    }
};
