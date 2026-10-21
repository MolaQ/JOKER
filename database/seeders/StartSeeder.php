<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Start strony z czystą bazą (bez przykładowych drużyn, zawodników i aktualności): sezony, poziomy rozgrywek
 * i podstrony. Drużyny, sztab i zawodników wczytasz w panelu: Import z plików. Administratora tworzy
 * `php artisan joker:admin twoj@email`.
 */
class StartSeeder extends Seeder
{
    public function run(): void
    {
        $club = new ClubSeeder;
        $club->seedSeasons();
        $club->seedCompetitionLevels();

        $this->call(PagesSeeder::class);
    }
}
