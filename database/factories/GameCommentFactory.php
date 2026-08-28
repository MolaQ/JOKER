<?php

namespace Database\Factories;

use App\Models\GameComment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameComment>
 */
class GameCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraph(),
            'is_approved' => fake()->boolean(90), // 90% szans na zatwierdzenie
        ];
    }
}
