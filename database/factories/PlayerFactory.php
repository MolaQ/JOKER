<?php

namespace Database\Factories;

use App\Models\Player;
use App\PlayerPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'jersey_number' => fake()->unique()->numberBetween(1, 99),
            'position' => fake()->randomElement(PlayerPosition::cases()),
            'birth_date' => fake()->dateTimeBetween('-30 years', '-15 years'),
            'height' => fake()->numberBetween(170, 210),
            'weight' => fake()->numberBetween(60, 100),
            'reach' => fake()->numberBetween(220, 250),
            'spike_reach' => fake()->numberBetween(250, 340),
            'bio' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
