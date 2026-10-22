<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Season;
use App\Services\ScheduleImporter;
use Illuminate\Console\Command;

/**
 * Pobiera terminarze, wyniki i tabele (wielkopolskasiatkowka.com.pl i WZPS) dla rozgrywek z wpisanymi adresami.
 * Działa samo co 3 godziny, jeśli na serwerze jest ustawiony cron z `php artisan schedule:run`.
 */
class SyncSchedules extends Command
{
    protected $signature = 'joker:sync-schedules';

    protected $description = 'Pobierz terminarze, wyniki i tabele ze stron związku';

    public function handle(ScheduleImporter $importer): int
    {
        // Najpierw rozgrywki z tabelą wyników WZPS - to one „zabierają” swoje mecze ze stron drużyn.
        // Tylko bieżący sezon: archiwalne rozgrywki już się nie zmieniają.
        $competitions = Competition::whereNotNull('source_url')->where('source_url', '!=', '')
            ->whereHas('season', fn ($season) => $season->where('is_current', true))
            ->get()
            ->sortBy(fn (Competition $competition) => str_contains($competition->source_url, 'mode=12') ? 0 : 1);

        if ($competitions->isEmpty()) {
            $season = Season::current()->value('name');
            $this->warn($season
                ? "Żadne rozgrywki sezonu {$season} nie mają adresu terminarza. Uruchom: php artisan db:seed --class=StartSeeder albo wpisz adresy w panelu: Rozgrywki."
                : 'Brak bieżącego sezonu. Ustaw go w panelu: Sezony, albo uruchom: php artisan db:seed --class=StartSeeder.');

            return self::SUCCESS;
        }

        foreach ($competitions as $competition) {
            $importer->sync($competition);
            $this->line("{$competition->name}: {$competition->sync_message}");
        }

        return self::SUCCESS;
    }
}
