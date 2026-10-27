<?php

namespace App\Console\Commands;

use App\Models\GalleryPhoto;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Zmniejsza zdjęcia galerii wgrane przed dodaniem automatycznego zmniejszania i robi do nich miniatury.
 * Zdjęcia, które mają już miniaturę, są pomijane (ponowne zapisywanie JPEG obniżałoby jakość),
 * więc polecenie można bezpiecznie uruchamiać przy każdej aktualizacji.
 */
class OptimizePhotos extends Command
{
    protected $signature = 'joker:optimize-photos {--force : Przetwórz też zdjęcia, które mają już miniaturę}';

    protected $description = 'Zmniejsz zdjęcia galerii i zrób do nich miniatury';

    public function handle(): int
    {
        $done = 0;
        $total = GalleryPhoto::count();

        $this->withProgressBar(GalleryPhoto::lazyById(), function (GalleryPhoto $photo) use (&$done) {
            if (! $this->option('force') && Storage::disk('public')->exists(ImageOptimizer::thumbPath((string) $photo->path))) {
                return;
            }

            $done += ImageOptimizer::process($photo->path) ? 1 : 0;
        });

        $this->newLine();
        $this->info("Przetworzone zdjęcia: {$done} z {$total}.");

        return self::SUCCESS;
    }
}
