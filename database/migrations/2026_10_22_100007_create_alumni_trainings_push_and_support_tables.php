<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wychowankowie klubu, stały plan treningów, powiadomienia push na telefon oraz wsparcie klubu
 * (pakiety sponsorskie i gadżety).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->string('position')->nullable();
            $table->string('years_in_club', 40)->nullable();
            $table->string('current_club')->nullable();
            $table->string('level', 80)->nullable();
            $table->text('achievements')->nullable();
            $table->string('profile_url', 500)->nullable();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('venue', 20);
            $table->string('note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['weekday', 'starts_at']);
        });

        Schema::create('push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('content_encoding', 20)->default('aes128gcm');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('price')->nullable();
            $table->text('benefits')->nullable();
            $table->boolean('is_highlighted')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('sizes')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });

        foreach ([UserRole::Boss, UserRole::Trainer, UserRole::Staff] as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => Permission::Trainings->value]);
        }

        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', Permission::Trainings->value)->delete();
        Cache::forget('role_permissions');
        Schema::dropIfExists('shop_products');
        Schema::dropIfExists('support_packages');
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('alumni');
    }
};
