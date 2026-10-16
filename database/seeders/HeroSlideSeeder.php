<?php

namespace Database\Seeders;

use App\Models\HeroSlide;
use Illuminate\Database\Seeder;

class HeroSlideSeeder extends Seeder
{
    /**
     * Slajdy na stronie głównej (zdjęcia z Unsplash - patrz config/media.php).
     */
    public function run(): void
    {
        $slides = [
            ['title' => 'Gramy razem. Wygrywamy razem.', 'subtitle' => 'Klub siatkarski z Piły. Od pierwszego odbicia młodzika po mecze seniorów w 3 lidze.',
                'photo' => 'hero-team', 'cta_label' => 'Poznaj drużyny', 'cta_url' => '/teams'],
            ['title' => 'Nabór trwa cały rok', 'subtitle' => 'Pierwszy trening za darmo. Zapraszamy dziewczęta i chłopców od 8 lat.',
                'photo' => 'men-game-1', 'cta_label' => 'Zapisz się', 'cta_url' => '/contact'],
            ['title' => 'Każdy punkt to praca całej drużyny', 'subtitle' => 'Sprawdź terminarz i przyjdź nas wspierać na hali.',
                'photo' => 'spike', 'cta_label' => 'Terminarz', 'cta_url' => '/schedule'],
        ];

        foreach ($slides as $order => $slide) {
            HeroSlide::create([
                'title' => $slide['title'],
                'subtitle' => $slide['subtitle'],
                'image_path' => config("media.photos.{$slide['photo']}.url"),
                'cta_label' => $slide['cta_label'],
                'cta_url' => $slide['cta_url'],
                'display_order' => $order + 1,
                'is_active' => true,
            ]);
        }
    }
}
