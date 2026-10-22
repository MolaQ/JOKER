<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        $title = fake('pl_PL')->words(3, true);

        return [
            'title' => Str::ucfirst($title),
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'lead' => fake('pl_PL')->sentence(),
            'content' => '<p>'.fake('pl_PL')->paragraph().'</p>',
            'is_published' => true,
            'display_order' => 0,
        ];
    }
}
