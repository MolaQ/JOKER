<?php

namespace Database\Factories;

use App\Models\Tournament;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tournament>
 */
class TournamentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+2 months');

        return [
            'name' => 'Turniej w '.fake('pl_PL')->city(),
            'start_date' => $start,
            'end_date' => null,
            'city' => fake('pl_PL')->city(),
            'is_organizer' => false,
        ];
    }
}
