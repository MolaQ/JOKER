<?php

namespace App\Support;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Throwable;

/**
 * Linki w treściach (artykuły, podstrony, relacje) prowadzące do usuniętych rzeczy na stronie
 * zostają samym tekstem, bez linku. Gdy rzecz wróci z kosza, link znowu działa.
 */
class DeadLinks
{
    /** @var array<string, bool> */
    private static array $alive = [];

    public static function strip(?string $html): ?string
    {
        if ($html === null || ! str_contains($html, '<a')) {
            return $html;
        }

        return preg_replace_callback('/<a\b([^>]*)>(.*?)<\/a>/isu', function (array $match) {
            if (! preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/i', $match[1], $href)) {
                return $match[0];
            }

            return self::alive(html_entity_decode($href[2])) ? $match[0] : $match[2];
        }, $html) ?? $html;
    }

    public static function flush(): void
    {
        self::$alive = [];
    }

    /**
     * Czy adres prowadzi do istniejącej rzeczy. Adresy zewnętrzne, kotwice i pliki zawsze uznajemy za działające.
     */
    public static function alive(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false) {
            return true;
        }

        $host = $parts['host'] ?? null;
        $path = $parts['path'] ?? '';

        if (($host && $host !== parse_url(config('app.url'), PHP_URL_HOST) && $host !== request()->getHost()) || $path === '' || ! str_starts_with($path, '/') || str_starts_with($path, '/storage/')) {
            return true;
        }

        return self::$alive[$path] ??= self::check($path);
    }

    private static function check(string $path): bool
    {
        try {
            $route = Route::getRoutes()->match(Request::create($path));
        } catch (Throwable) {
            return false;
        }

        try {
            foreach ($route->signatureParameters(['subClass' => UrlRoutable::class]) as $parameter) {
                $value = $route->parameter($parameter->getName());

                if ($value === null || $value instanceof UrlRoutable) {
                    continue;
                }

                $class = $parameter->getType()?->getName();

                if ($class && app($class)->resolveRouteBinding($value, $route->bindingFieldFor($parameter->getName())) === null) {
                    return false;
                }
            }
        } catch (Throwable) {
            return true;
        }

        return true;
    }
}
