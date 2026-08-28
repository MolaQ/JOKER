<?php

namespace Database\Factories;

use App\Models\GamePhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GamePhoto>
 */
class GamePhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'photo_path' => 'photos/games/'.fake()->uuid().'.jpg',
            'caption' => fake()->sentence(),
            'display_order' => fake()->numberBetween(0, 10),
        ];
    }
}
