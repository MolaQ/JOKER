<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Zgłoszenia niedziałających linków (filmy i transmisje) od zalogowanych osób.
     * Rekord to mecz ligowy, turniejowy albo turniej minisiatkówki z polem streams.
     */
    public function up(): void
    {
        if (! Schema::hasTable('link_reports')) {
            Schema::create('link_reports', function (Blueprint $table) {
                $table->id();
                $table->string('reportable_type', 64);
                $table->unsignedBigInteger('reportable_id');
                $table->string('url', 500);
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('status', 16)->default('pending');
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->index(['reportable_type', 'reportable_id']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('link_reports');
    }
};
