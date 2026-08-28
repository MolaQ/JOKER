<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence();
        $publishedAt = fake()->boolean(80) ? fake()->dateTimeBetween('-3 months', 'now') : null;

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(),
            'content' => fake()->paragraphs(10, true),
            'featured_image' => 'images/articles/'.fake()->uuid().'.jpg',
            'status' => $publishedAt ? 'published' : fake()->randomElement(['draft', 'published']),
            'published_at' => $publishedAt,
            'views_count' => fake()->numberBetween(0, 5000),
            'is_featured' => fake()->boolean(20),
            'allow_comments' => fake()->boolean(90),
        ];
    }
}
