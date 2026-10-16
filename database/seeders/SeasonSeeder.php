<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Dwa sezony archiwalne i aktualny (sezon startuje 1 września).
     */
    public function run(): void
    {
        $currentStartYear = now()->month >= 8 ? now()->year : now()->year - 1;

        foreach ([2, 1, 0] as $yearsAgo) {
            $startYear = $currentStartYear - $yearsAgo;

            Season::create([
                'name' => $startYear.'/'.($startYear + 1),
                'start_date' => "{$startYear}-09-01",
                'end_date' => ($startYear + 1).'-06-30',
                'is_current' => $yearsAgo === 0,
            ]);
        }
    }
}
