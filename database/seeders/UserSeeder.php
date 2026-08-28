<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin Joker',
            'email' => 'admin@jokerpila.pl',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        // Trenerzy
        User::create([
            'name' => 'Marek Kowalski',
            'email' => 'trener1@jokerpila.pl',
            'password' => Hash::make('password'),
            'role' => UserRole::Trainer,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Anna Nowak',
            'email' => 'trener2@jokerpila.pl',
            'password' => Hash::make('password'),
            'role' => UserRole::Trainer,
            'email_verified_at' => now(),
        ]);

        // Rodzice
        User::factory()->count(10)->create([
            'role' => UserRole::Parent,
        ]);

        // Zawodnicy
        User::factory()->count(20)->create([
            'role' => UserRole::Player,
        ]);

        // Kibice
        User::factory()->count(15)->create([
            'role' => UserRole::Fan,
        ]);

        // Goście
        User::factory()->count(5)->create([
            'role' => UserRole::Guest,
        ]);
    }
}
