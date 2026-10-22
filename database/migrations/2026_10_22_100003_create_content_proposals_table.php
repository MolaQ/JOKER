<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Artykuły, zdjęcia i transmisje proponowane przez rodziców i zawodników.
 * Trafiają na stronę po zatwierdzeniu przez redaktora, szefa lub administratora.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->nullableMorphs('match');
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('url', 500)->nullable();
            $table->json('files')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_note')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        foreach ([UserRole::Boss, UserRole::Editor] as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => Permission::ContentProposals->value]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_proposals');
        DB::table('role_permissions')->where('permission', Permission::ContentProposals->value)->delete();
    }
};
