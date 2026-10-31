<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Zdjęcia z meczu',
            'description' => null,
            'match_type' => (new Game)->getMorphClass(),
            'match_id' => Game::factory(),
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
