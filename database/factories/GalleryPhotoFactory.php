<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\GalleryPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'path' => 'galleries/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(4),
            'display_order' => 0,
        ];
    }
}
