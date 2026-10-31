<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wybór kibica: tematy powiadomień, drużyny klubu i obserwowane drużyny turniejowe (puste = wszystko).
     */
    public function up(): void
    {
        if (Schema::hasColumn('push_subscriptions', 'topics')) {
            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->json('topics')->nullable()->after('content_encoding');
            $table->json('teams')->nullable()->after('topics');
            $table->json('participants')->nullable()->after('teams');
        });
    }

    public function down(): void
    {
        Schema::table('push_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['topics', 'teams', 'participants']);
        });
    }
};
