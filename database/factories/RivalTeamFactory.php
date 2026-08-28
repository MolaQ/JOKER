<?php

namespace Database\Factories;

use App\Models\RivalTeam;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RivalTeam>
 */
class RivalTeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fullName = fake()->randomElement([
            'AZS Politechnika Warszawska',
            'MKS Bedłów',
            'Exact Systems Norwid Częstochowa',
            'BKS Visla Bydgoszcz',
            'Ślepsk Malow Słupsk',
            'AZS AGH Kraków',
            'MCKS Czarni Radom',
            'GKS Katowice',
            'LUK Politechnika Lublin',
            'Aluron CMC Warta Zawiercie',
        ]);

        return [
            'full_name' => $fullName,
            'short_name' => Str::of($fullName)->explode(' ')->first(),
            'category' => fake()->randomElement(['senior', 'junior', 'junior_mlodszy', 'mlodzik']),
            'logo_path' => null,
        ];
    }
}
