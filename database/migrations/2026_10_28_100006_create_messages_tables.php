<?php

use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wiadomości między użytkownikami: rozmowy (dwie osoby, kilka osób albo grupa drużyny), ich uczestnicy
 * z momentem ostatniego przeczytania i same wiadomości. Uprawnienie „Pisanie wiadomości” dostają wszystkie role.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120)->nullable();
            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->string('audience', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
            $table->index(['team_id', 'audience']);
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Ostatnia przeczytana wiadomość (numer, a nie czas: dwie wiadomości w tej samej sekundzie się nie gubią).
            $table->unsignedBigInteger('last_read_message_id')->default(0);
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamp('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at')->nullable()->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_messages')->default(true);
            $table->timestamp('messages_emailed_at')->nullable();
        });

        foreach (UserRole::cases() as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => 'messages.write']);
        }
        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', 'messages.write')->delete();
        DB::table('permission_user')->where('permission', 'messages.write')->delete();
        Cache::forget('role_permissions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_messages', 'messages_emailed_at']);
        });

        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};
