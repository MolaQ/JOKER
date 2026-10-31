<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nowe konto nie działa, dopóki ktoś z klubu go nie aktywuje i nie powiąże z profilem
 * (konto kibica działa po potwierdzeniu e-maila). Istniejące konta zostają aktywne
 * i traktujemy je jak potwierdzone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('password');
            $table->string('requested_role', 20)->nullable()->after('is_active');
            $table->foreignId('requested_team_id')->nullable()->after('requested_role')->constrained('teams')->nullOnDelete();
            $table->text('registration_note')->nullable()->after('requested_team_id');
            $table->timestamp('activated_at')->nullable()->after('registration_note');
            $table->foreignId('activated_by')->nullable()->after('activated_at')->constrained('users')->nullOnDelete();
        });

        DB::table('users')->update(['is_active' => true, 'activated_at' => now()]);
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activated_by');
            $table->dropConstrainedForeignId('requested_team_id');
            $table->dropColumn(['is_active', 'requested_role', 'registration_note', 'activated_at']);
        });
    }
};
