<?php

namespace App\Support;

use App\Models\Setting;
use ArrayAccess;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Ustawienia strony z panelu admina. W widokach dostępne jako $site['klucz'].
 * Brak zapisanej wartości = wartość domyślna z config/site.php.
 *
 * @implements ArrayAccess<string, mixed>
 */
class SiteSettings implements ArrayAccess
{
    public const CACHE_KEY = 'site-settings';

    /** @var array<string, mixed>|null */
    private ?array $stored = null;

    /**
     * @return array<string, array{type: string, label: string, default: mixed, help?: string}>
     */
    public static function fields(): array
    {
        return collect(config('site.groups'))
            ->flatMap(fn (array $group) => $group['fields'])
            ->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->stored();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        return Arr::get(self::fields(), $key.'.default', $default);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->stored = null;
    }

    /**
     * Kolor w formacie #rrggbb (zabezpieczenie przed wstrzyknięciem CSS).
     */
    public function color(string $key): string
    {
        $value = (string) $this->get($key);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : (string) Arr::get(self::fields(), $key.'.default');
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function links(string $key): array
    {
        return collect((array) $this->get($key, []))
            ->filter(fn ($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->values()
            ->all();
    }

    /**
     * Adres do wstawienia w href: ścieżki /coś zamieniane na pełny adres strony.
     */
    public function href(string $url): string
    {
        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /**
     * Czy link prowadzi poza stronę klubu (otwieramy go w nowej karcie).
     */
    public function isExternal(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== request()->getHost();
    }

    /**
     * Czy link z menu wskazuje bieżącą stronę (podświetlenie pozycji menu).
     */
    public function isCurrent(string $url): bool
    {
        if ($this->isExternal($url)) {
            return false;
        }

        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return request()->is('/');
        }

        $related = [
            'teams' => ['players/*', 'trenerzy/*'],
            'schedule' => ['mecze/*'],
            'news' => [],
        ];

        return request()->is($path, $path.'/*', ...$related[$path] ?? []);
    }

    /**
     * Adres obrazka z ustawień albo zapasowy.
     */
    public function image(string $key, ?string $fallback = null): ?string
    {
        return Media::url($this->get($key)) ?? $fallback;
    }

    /**
     * Profile w mediach społecznościowych, które mają wpisany adres.
     *
     * @return array<string, string>
     */
    public function socials(): array
    {
        return collect(['facebook', 'instagram', 'youtube', 'tiktok', 'x'])
            ->mapWithKeys(fn (string $network) => [$network => (string) $this->get('social_'.$network)])
            ->filter()
            ->all();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->get($offset) !== null;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set([$offset => $value]);
    }

    public function offsetUnset(mixed $offset): void
    {
        Setting::whereKey($offset)->delete();
        $this->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        if ($this->stored !== null) {
            return $this->stored;
        }

        try {
            return $this->stored = Cache::rememberForever(self::CACHE_KEY, fn () => Schema::hasTable('settings')
                ? Setting::pluck('value', 'key')->all()
                : []);
        } catch (\Throwable) {
            // Np. przed pierwszą migracją - strona działa na wartościach domyślnych.
            return $this->stored = [];
        }
    }
}
