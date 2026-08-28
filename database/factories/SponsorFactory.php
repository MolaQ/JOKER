<?php

namespace Database\Factories;

use App\Models\Sponsor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sponsor>
 */
class SponsorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'logo_path' => 'logos/sponsors/'.fake()->uuid().'.png',
            'website_url' => fake()->url(),
            'tier' => fake()->randomElement(['platinum', 'gold', 'silver', 'bronze']),
            'display_order' => fake()->numberBetween(0, 10),
            'is_active' => fake()->boolean(90),
        ];
    }
}
