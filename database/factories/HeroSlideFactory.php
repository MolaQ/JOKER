<?php

namespace Database\Factories;

use App\Models\HeroSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HeroSlide>
 */
class HeroSlideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'subtitle' => fake()->sentence(10),
            'image_path' => 'hero-slides/'.fake()->uuid().'.jpg',
            'cta_label' => fake()->optional(0.4)->randomElement(['Czytaj więcej', 'Sprawdź terminarz', 'Poznaj zespół']),
            'cta_url' => fake()->optional(0.4)->url(),
            'display_order' => fake()->numberBetween(0, 20),
            'is_active' => fake()->boolean(90),
        ];
    }
}
