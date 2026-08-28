<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Archiwalne sezony
        Season::create([
            'name' => '2023/2024',
            'start_date' => '2023-09-01',
            'end_date' => '2024-06-30',
            'is_current' => false,
        ]);

        Season::create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
            'is_current' => false,
        ]);

        // Aktualny sezon
        Season::create([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => true,
        ]);
    }
}
