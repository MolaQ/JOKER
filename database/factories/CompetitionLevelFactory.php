<?php

namespace Database\Factories;

use App\Models\CompetitionLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionLevel>
 */
class CompetitionLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'PlusLiga', '1 liga', '2 liga', '3 liga', 'międzywojewódzka', 'wojewódzka', 'okręgowa',
            ]),
            'display_order' => 0,
        ];
    }
}
