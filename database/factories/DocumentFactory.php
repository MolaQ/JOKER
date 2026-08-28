<?php

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileType = fake()->randomElement(['pdf', 'doc', 'docx', 'xls', 'xlsx']);

        return [
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'file_path' => 'documents/'.fake()->uuid().'.'.$fileType,
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(10000, 5000000), // 10KB - 5MB
            'category' => fake()->randomElement(['regulations', 'forms', 'reports', 'other']),
            'download_count' => fake()->numberBetween(0, 500),
            'is_public' => fake()->boolean(80),
        ];
    }
}
