<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nowe kategorie dokumentów (RODO, zgody na wizerunek i wyjazdy, wymagania klubu):
 * lista kategorii jest w App\Models\Document::CATEGORIES zamiast w bazie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('category', 30)->default('other')->change();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('category', ['regulations', 'forms', 'reports', 'other'])->default('other')->change();
        });
    }
};
