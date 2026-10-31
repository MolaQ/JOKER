<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ile razy pobrano logo ze strony /logotypy (sortowanie „najpopularniejsze”).
        Schema::hasTable('logo_downloads') || Schema::create('logo_downloads', function (Blueprint $table) {
            $table->string('logo_key', 60)->primary();
            $table->unsignedInteger('downloads')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logo_downloads');
    }
};
