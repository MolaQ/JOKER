<?php

namespace Database\Seeders;

use App\Models\CompetitionLevel;
use Illuminate\Database\Seeder;

class CompetitionLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['name' => 'PlusLiga', 'display_order' => 1],
            ['name' => '1 liga', 'display_order' => 2],
            ['name' => '2 liga', 'display_order' => 3],
            ['name' => '3 liga', 'display_order' => 4],
            ['name' => 'międzywojewódzka', 'display_order' => 5],
            ['name' => 'wojewódzka', 'display_order' => 6],
            ['name' => 'okręgowa', 'display_order' => 7],
        ];

        foreach ($levels as $level) {
            CompetitionLevel::create($level);
        }
    }
}
