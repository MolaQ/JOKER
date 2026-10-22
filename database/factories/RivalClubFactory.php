<?php

namespace Database\Factories;

use App\Models\RivalClub;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RivalClub>
 */
class RivalClubFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Energetyk Poznań', 'MKS Kalisz', 'Sparta Złotów', 'Gwardia Wrocław']),
            'logo_path' => null,
        ];
    }
}
