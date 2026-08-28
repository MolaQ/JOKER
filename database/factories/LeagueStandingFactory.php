<?php

namespace Database\Factories;

use App\Models\LeagueStanding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeagueStanding>
 */
class LeagueStandingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $played = fake()->numberBetween(10, 30);
        $won = fake()->numberBetween(0, $played);
        $lost = $played - $won;
        $setsWon = fake()->numberBetween($won * 2, $won * 3);
        $setsLost = fake()->numberBetween($lost * 2, $lost * 3);

        return [
            'position' => 0,
            'played' => $played,
            'won' => $won,
            'lost' => $lost,
            'sets_won' => $setsWon,
            'sets_lost' => $setsLost,
            'points_for' => fake()->numberBetween(2000, 3000),
            'points_against' => fake()->numberBetween(2000, 3000),
            'points' => ($won * 3) + (fake()->numberBetween(0, $lost)), // 3 pkt za wygraną
        ];
    }
}
