<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SeasonSeeder::class,
            CompetitionLevelSeeder::class,
            RivalTeamSeeder::class,
            TeamSeeder::class,
            PlayerSeeder::class,
            CompetitionSeeder::class,
            GameSeeder::class,
            LeagueStandingSeeder::class,
            ArticleSeeder::class,
            DocumentSeeder::class,
            SponsorSeeder::class,
        ]);
    }
}
