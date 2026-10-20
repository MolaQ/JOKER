<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Statystyki odwiedzin bez ciasteczek: każde wyświetlenie strony i przełączenie zakładki,
     * z czasem na stronie i głębokością przewinięcia. Odwiedzający rozpoznawany jest tylko
     * skrótem zmieniającym się codziennie (bez adresu IP w bazie).
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_view_id')->nullable()->constrained('page_views')->cascadeOnDelete();
            $table->string('kind', 8)->default('page');
            $table->string('path');
            $table->string('route_name', 100)->nullable();
            $table->string('title')->nullable();
            $table->string('label', 120)->nullable();
            $table->char('visitor_hash', 64);
            $table->string('referrer_host', 120)->nullable();
            $table->string('device', 8)->nullable();
            $table->string('locale', 5)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedTinyInteger('max_scroll')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['kind', 'created_at']);
            $table->index(['path', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
