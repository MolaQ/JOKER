<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $category = fake()->randomElement(['senior', 'junior', 'junior_mlodszy', 'mlodzik']);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'category' => $category,
            'birth_year' => $category === 'senior' ? null : fake()->numberBetween(2008, 2013),
            'display_order' => 0,
        ];
    }
}
