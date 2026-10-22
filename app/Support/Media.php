<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Adresy zdjęć: wgrane pliki z dysku "public" albo zdjęcia tematyczne z config/media.php.
 */
class Media
{
    /**
     * Publiczny adres pliku. Pełne adresy (https://...) zostają bez zmian.
     */
    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Zestaw rozmiarów (srcset) - tylko dla zdjęć z Unsplash, które skalują się po stronie CDN.
     *
     * @param  array<int, int>  $widths
     */
    public static function srcset(?string $url, array $widths = [480, 800, 1200, 1800]): ?string
    {
        if (! self::isUnsplash($url)) {
            return null;
        }

        return collect($widths)
            ->map(fn (int $width) => self::sized($url, $width).' '.$width.'w')
            ->implode(', ');
    }

    /**
     * Adres zdjęcia w konkretnej szerokości (Unsplash: WebP/AVIF, kompresja, kadrowanie).
     */
    public static function sized(?string $url, int $width): ?string
    {
        if (! self::isUnsplash($url)) {
            return $url;
        }

        $base = Str::before($url, '?');

        return $base.'?auto=format&fit=crop&q=70&w='.$width;
    }

    /**
     * @return array{url: string, author: string, page: string}
     */
    public static function photo(string $key): array
    {
        $photos = config('media.photos');

        return $photos[$key] ?? reset($photos);
    }

    public static function teamPhoto(Team $team): string
    {
        if ($team->photo_path) {
            return self::url($team->photo_path);
        }

        return self::fallback('team_fallbacks', (int) $team->display_order ?: $team->id);
    }

    public static function articleImage(Article $article): string
    {
        if ($article->featured_image && (self::isRemote($article->featured_image) || Storage::disk('public')->exists($article->featured_image))) {
            return self::url($article->featured_image);
        }

        return self::fallback('article_fallbacks', $article->id);
    }

    public static function fallback(string $list, int $seed): string
    {
        $keys = config("media.{$list}");

        return self::photo($keys[$seed % count($keys)])['url'];
    }

    public static function isUnsplash(?string $url): bool
    {
        return $url !== null && Str::startsWith($url, 'https://images.unsplash.com/');
    }

    private static function isRemote(string $path): bool
    {
        return Str::startsWith($path, ['http://', 'https://']);
    }
}
