<?php

namespace Database\Seeders;

use App\Models\RivalTeam;
use Illuminate\Database\Seeder;

class RivalTeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Baza drużyn rywali, używana w terminarzu i tabeli ligowej zamiast
     * wolnego tekstu wpisywanego ręcznie przy każdym meczu.
     */
    public function run(): void
    {
        $rivalTeams = [
            ['full_name' => 'AZS Politechnika Warszawska', 'short_name' => 'AZS Warszawa', 'category' => 'senior'],
            ['full_name' => 'MKS Bedłów', 'short_name' => 'Bedłów', 'category' => 'senior'],
            ['full_name' => 'Exact Systems Norwid Częstochowa', 'short_name' => 'Norwid Częstochowa', 'category' => 'senior'],
            ['full_name' => 'BKS Visla Bydgoszcz', 'short_name' => 'Visla Bydgoszcz', 'category' => 'senior'],
            ['full_name' => 'Ślepsk Malow Słupsk', 'short_name' => 'Ślepsk Słupsk', 'category' => 'senior'],
            ['full_name' => 'AZS AGH Kraków', 'short_name' => 'AZS AGH', 'category' => 'senior'],
            ['full_name' => 'MCKS Czarni Radom', 'short_name' => 'Czarni Radom', 'category' => 'junior'],
            ['full_name' => 'GKS Katowice', 'short_name' => 'GKS Katowice', 'category' => 'junior'],
            ['full_name' => 'LUK Politechnika Lublin', 'short_name' => 'LUK Lublin', 'category' => 'junior_mlodszy'],
            ['full_name' => 'Aluron CMC Warta Zawiercie', 'short_name' => 'Warta Zawiercie', 'category' => 'mlodzik'],
        ];

        foreach ($rivalTeams as $rivalTeam) {
            RivalTeam::create($rivalTeam);
        }
    }
}
