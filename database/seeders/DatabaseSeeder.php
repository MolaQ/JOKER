<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

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
            TeamSeeder::class,
            PlayerSeeder::class,
            CompetitionSeeder::class,
            ArticleSeeder::class,
            DocumentSeeder::class,
            SponsorSeeder::class,
            HeroSlideSeeder::class,
            PageSeeder::class,
        ]);

        // Prawdziwe mecze, wyniki i tabele ze strony WZPS (w testach bez sieci).
        if (! app()->runningUnitTests()) {
            Artisan::call('joker:sync-schedules');
            $this->command?->line(trim(Artisan::output()));
        }
    }
}
