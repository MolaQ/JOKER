<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Odpowiedź wysłana z panelu na wiadomość z formularza kontaktowego (treść, kiedy i kto). Same nowe pola.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('contact_messages', 'reply_body')) {
                $table->text('reply_body')->nullable();
            }
            if (! Schema::hasColumn('contact_messages', 'replied_at')) {
                $table->timestamp('replied_at')->nullable();
            }
            if (! Schema::hasColumn('contact_messages', 'replied_by')) {
                $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replied_by');
            $table->dropColumn(['reply_body', 'replied_at']);
        });
    }
};
