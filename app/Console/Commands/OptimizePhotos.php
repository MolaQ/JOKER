<?php

namespace App\Console\Commands;

use App\Models\GalleryPhoto;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;

/**
 * Jednorazowo zmniejsza zdjęcia galerii wgrane przed dodaniem automatycznego zmniejszania
 * i robi do nich miniatury. Można uruchamiać wielokrotnie: zmniejszone zdjęcia zostają w tym samym rozmiarze.
 */
class OptimizePhotos extends Command
{
    protected $signature = 'joker:optimize-photos';

    protected $description = 'Zmniejsz zdjęcia galerii i zrób do nich miniatury';

    public function handle(): int
    {
        $done = 0;
        $total = GalleryPhoto::count();

        $this->withProgressBar(GalleryPhoto::lazyById(), function (GalleryPhoto $photo) use (&$done) {
            $done += ImageOptimizer::process($photo->path) ? 1 : 0;
        });

        $this->newLine();
        $this->info("Przetworzone zdjęcia: {$done} z {$total}.");

        return self::SUCCESS;
    }
}
