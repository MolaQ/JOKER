<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Strona „Sztab”: kolejność osób ustawiana w panelu, funkcja pokazywana na kafelku, ukrycie ze strony
     * oraz trenerzy przypisani do grup minisiatkówki. Same nowe pola, nic nie jest zmieniane ani kasowane.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'staff_order')) {
                $table->unsignedSmallInteger('staff_order')->nullable();
            }
            if (! Schema::hasColumn('users', 'staff_function')) {
                $table->string('staff_function', 120)->nullable();
            }
            if (! Schema::hasColumn('users', 'staff_hidden')) {
                $table->boolean('staff_hidden')->default(false);
            }
        });

        if (! Schema::hasTable('mini_group_trainers')) {
            Schema::create('mini_group_trainers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('mini_group_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['mini_group_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_group_trainers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['staff_order', 'staff_function', 'staff_hidden']);
        });
    }
};
