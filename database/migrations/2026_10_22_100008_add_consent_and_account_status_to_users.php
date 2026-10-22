<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stan konta poza „aktywne/nieaktywne”: zgoda RODO (warunek konta), odrzucenie prośby
 * o aktywację z powodem, wyłączenie konta przez użytkownika i usunięcie (anonimizacja).
 * Istniejące konta nie mają zapisanej zgody, więc po zalogowaniu zostaną o nią poproszone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_consent_at')->nullable()->after('activated_by');
            $table->timestamp('rejected_at')->nullable()->after('privacy_consent_at');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->foreignId('rejected_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('disabled_at')->nullable()->after('rejected_by');
            $table->timestamp('anonymized_at')->nullable()->after('disabled_at');
            $table->string('closed_reason', 30)->nullable()->after('anonymized_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['privacy_consent_at', 'rejected_at', 'rejection_reason', 'disabled_at', 'anonymized_at', 'closed_reason']);
        });
    }
};
