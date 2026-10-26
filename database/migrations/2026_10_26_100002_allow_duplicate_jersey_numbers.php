<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Wycofane: numer koszulki zostaje unikalny w drużynie (patrz 2026_10_26_100003).
 * Pusta migracja zostaje, żeby bazy, które ją już uruchomiły, miały spójną historię.
 */
return new class extends Migration
{
    public function up(): void {}

    public function down(): void {}
};
