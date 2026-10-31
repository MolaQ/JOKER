<?php

use App\Permission;
use App\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Minisiatkówka: grupy (czwórki, trójki, dwójki, singielki), dzieci, wyjazdy na turnieje,
     * drużyny wystawione na wyjazd (z wynikiem) i ich składy.
     */
    public function up(): void
    {
        // Tabele mogły powstać przy przerwanym wcześniej uruchomieniu (MySQL nie cofa zmian struktury).
        Schema::hasTable('mini_groups') || Schema::create('mini_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 80)->unique();
            $table->unsignedTinyInteger('players_on_court')->default(4);
            $table->string('age_label', 40)->nullable();
            $table->unsignedSmallInteger('birth_year_from')->nullable();
            $table->unsignedSmallInteger('birth_year_to')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('photo_path')->nullable();
            $table->string('lead')->nullable();
            $table->longText('rules')->nullable();
            $table->longText('history')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::hasTable('mini_players') || Schema::create('mini_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 60);
            $table->string('last_name', 80);
            $table->unsignedSmallInteger('birth_year')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('image_consent')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::hasTable('mini_trips') || Schema::create('mini_trips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('venue')->nullable();
            $table->string('organizer')->nullable();
            $table->text('description')->nullable();
            $table->text('parent_info')->nullable();
            $table->foreignId('tournament_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('starts_on');
        });

        Schema::hasTable('mini_trip_teams') || Schema::create('mini_trip_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_trip_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mini_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 40);
            $table->unsignedSmallInteger('place')->nullable();
            $table->unsignedSmallInteger('teams_count')->nullable();
            $table->string('note')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        // Tabela składów jest pusta, gdy poprzednie uruchomienie przerwał zbyt długi indeks: zakładamy ją od nowa.
        Schema::dropIfExists('mini_player_mini_trip_team');
        Schema::create('mini_player_mini_trip_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_trip_team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mini_player_id')->constrained()->cascadeOnDelete();

            // Krótka nazwa: MySQL przyjmuje najwyżej 64 znaki.
            $table->unique(['mini_trip_team_id', 'mini_player_id'], 'mini_lineup_unique');
        });

        // Cztery grupy na start, ukryte do czasu uzupełnienia (zdjęcie, dzieci) w panelu.
        foreach ([['Czwórki', 'czworki', 4, 'U-13'], ['Trójki', 'trojki', 3, 'U-12'], ['Dwójki', 'dwojki', 2, 'U-11'], ['Singielki', 'singielki', 1, 'U-10']] as $order => [$name, $slug, $players, $age]) {
            DB::table('mini_groups')->insertOrIgnore(['name' => $name, 'slug' => $slug, 'players_on_court' => $players, 'age_label' => $age, 'display_order' => $order, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()]);
        }

        foreach ([UserRole::Admin, UserRole::Boss, UserRole::Trainer, UserRole::Coordinator] as $role) {
            DB::table('role_permissions')->insertOrIgnore(['role' => $role->value, 'permission' => Permission::MiniVolleyball->value]);
        }
        Cache::forget('role_permissions');
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission', 'mini.manage')->delete();
        Schema::dropIfExists('mini_player_mini_trip_team');
        Schema::dropIfExists('mini_trip_teams');
        Schema::dropIfExists('mini_trips');
        Schema::dropIfExists('mini_players');
        Schema::dropIfExists('mini_groups');
    }
};
