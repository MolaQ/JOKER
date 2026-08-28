<?php

namespace Database\Factories;

use App\Models\Competition;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Competition>
 */
class CompetitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'level' => fake()->randomElement(['wojewódzka', 'okręgowa', '3 liga', 'międzywojewódzka']),
            'season_id' => Season::factory(),
            'description' => fake()->sentence(),
            'display_order' => 0,
        ];
    }
}
