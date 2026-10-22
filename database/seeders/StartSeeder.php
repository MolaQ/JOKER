<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

/**
 * Start strony z czystą bazą (bez przykładowych aktualności): sezony, poziomy rozgrywek, podstrony,
 * 7 drużyn klubu z trenerami oraz konta administratora, szefa i zawodnika z adresami i hasłami z .env
 * (SEED_*). Pozostałych zawodników i sztab wczytasz w panelu: Import z plików.
 * Można uruchomić ponownie: nie dubluje danych i nie zmienia haseł.
 */
class StartSeeder extends Seeder
{
    public function run(): void
    {
        if (Season::doesntExist()) {
            $club = new ClubSeeder;
            $club->seedSeasons();
            $club->seedCompetitionLevels();
        }

        $this->call([PagesSeeder::class, ClubAccountsSeeder::class]);
    }
}
