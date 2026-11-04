<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Wiadomości z formularza na stronie Kontakt: trafiają do panelu („Do zatwierdzenia”), żeby nic nie przepadło.
     */
    public function up(): void
    {
        if (Schema::hasTable('contact_messages')) {
            return;
        }

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('read_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('read_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
