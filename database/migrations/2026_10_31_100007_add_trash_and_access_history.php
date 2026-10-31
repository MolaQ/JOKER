<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kosz: usunięty w panelu rekord razem ze wszystkim, co od niego zależało, do przywrócenia przez „Kosz: ile dni”.
        Schema::hasTable('trash_items') || Schema::create('trash_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 120);
            $table->unsignedBigInteger('item_id');
            $table->string('label', 200);
            $table->longText('payload');
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'item_id']);
        });

        // Historia uprawnień konta: stan ról, wyjątków i drużyn koordynatora sprzed każdej zmiany.
        Schema::hasTable('access_snapshots') || Schema::create('access_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 160);
            $table->json('data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_snapshots');
        Schema::dropIfExists('trash_items');
    }
};
