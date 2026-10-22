<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

/**
 * Start strony z czystą bazą (bez przykładowych aktualności): sezony, poziomy rozgrywek, podstrony,
 * 7 drużyn klubu z trenerami, rozgrywki bieżącego sezonu z adresami stron związku oraz konta administratora, szefa i zawodnika z adresami i hasłami z .env
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

        $this->ensureCurrentSeason();

        $this->call([PagesSeeder::class, ClubAccountsSeeder::class]);

        // Rozgrywki bieżącego sezonu z adresami stron związku: terminarz, wyniki i tabele
        // pobierze `php artisan joker:sync-schedules` (i potem cron co 3 godziny).
        $club = new ClubSeeder;
        if ($this->command) {
            $club->setCommand($this->command);
        }
        $club->seedCompetitions();
    }

    /**
     * Sezon trwający dziś (od 1 września) musi istnieć i być bieżący, bo do niego trafiają rozgrywki.
     * Gdy bieżący sezon jest już ustawiony w panelu, zostaje bez zmian.
     */
    private function ensureCurrentSeason(): void
    {
        if (Season::current()->exists()) {
            return;
        }

        $startYear = now()->month >= 8 ? now()->year : now()->year - 1;
        $season = Season::firstOrCreate(['name' => $startYear.'/'.($startYear + 1)], [
            'start_date' => "{$startYear}-09-01",
            'end_date' => ($startYear + 1).'-06-30',
        ]);
        $season->update(['is_current' => true]);

        $this->command?->info("Ustawiono bieżący sezon: {$season->name}.");
    }
}
