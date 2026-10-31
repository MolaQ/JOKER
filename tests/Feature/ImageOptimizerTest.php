<?php

use App\Models\Gallery;
use App\Models\GalleryPhoto;
use App\Support\ImageOptimizer;
use Illuminate\Support\Facades\Storage;

function storeBigJpeg(string $path, int $width = 3000, int $height = 2000): void
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 30, 120, 200));
    ob_start();
    imagejpeg($image, null, 95);
    Storage::disk('public')->put($path, ob_get_clean());
}

test('nowe zdjęcie galerii jest zmniejszane i dostaje miniaturę', function () {
    Storage::fake('public');
    storeBigJpeg('galleries/1/duze.jpg');

    $photo = GalleryPhoto::create(['gallery_id' => Gallery::create(['title' => 'Mecz', 'is_published' => true])->id, 'path' => 'galleries/1/duze.jpg']);

    [$width] = getimagesize(Storage::disk('public')->path('galleries/1/duze.jpg'));
    [$thumbWidth] = getimagesize(Storage::disk('public')->path('galleries/1/thumbs/duze.jpg'));

    expect($width)->toBe(ImageOptimizer::MAX_SIZE)
        ->and($thumbWidth)->toBe(ImageOptimizer::THUMB_SIZE)
        ->and($photo->thumbUrl())->toContain('thumbs/duze.jpg');

    $photo->delete();
    Storage::disk('public')->assertMissing('galleries/1/thumbs/duze.jpg');
});

test('starsze zdjęcia dostają miniatury poleceniem, a do tego czasu pokazują oryginał', function () {
    Storage::fake('public');
    $gallery = Gallery::create(['title' => 'Turniej', 'is_published' => true]);
    $photo = GalleryPhoto::withoutEvents(fn () => GalleryPhoto::create(['gallery_id' => $gallery->id, 'path' => 'galleries/2/stare.jpg']));
    storeBigJpeg('galleries/2/stare.jpg', 2400, 1600);

    expect($photo->thumbUrl())->toBe($photo->url());

    $this->artisan('joker:optimize-photos')->expectsOutputToContain('1 z 1')->assertSuccessful();

    Storage::disk('public')->assertExists('galleries/2/thumbs/stare.jpg');
    expect($photo->thumbUrl())->toContain('thumbs/stare.jpg');

    // Drugie uruchomienie pomija zdjęcia z miniaturą, żeby nie pogarszać jakości JPEG.
    $this->artisan('joker:optimize-photos')->expectsOutputToContain('0 z 1')->assertSuccessful();
});
