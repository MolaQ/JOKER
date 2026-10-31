<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('mini_groups', 'color')) {
            Schema::table('mini_groups', function (Blueprint $table) {
                $table->string('color', 7)->nullable()->after('gender');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mini_groups', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
