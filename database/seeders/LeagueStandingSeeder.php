<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\Season;
use App\Services\StandingsCalculator;
use Illuminate\Database\Seeder;

class LeagueStandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Tabela ligowa jest liczona automatycznie na podstawie zakończonych meczów
     * (drużyna własna oraz rywale) dla każdych rozgrywek (competition) z osobna.
     */
    public function run(): void
    {
        $currentSeason = Season::current()->first();
        $competitions = Competition::where('season_id', $currentSeason?->id)->get();

        foreach ($competitions as $competition) {
            StandingsCalculator::recalculate($competition);
        }
    }
}
