<?php

namespace App\Support;

use App\Models\Setting;
use App\UserRole;
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
     * Czy kolor jest ciemny (wtedy kładziemy na nim jasny tekst).
     */
    public function isDark(string ...$keys): bool
    {
        $luminance = collect($keys)->avg(function (string $key) {
            [$r, $g, $b] = sscanf($this->color($key), '#%02x%02x%02x');

            return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        });

        return $luminance < 140;
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
     * Zasady wiadomości: rola piszącego => role, do których może zacząć rozmowę. Domyślnie każdy do każdego.
     *
     * @return array<string, list<string>>
     */
    public function messageRules(): array
    {
        $all = array_column(UserRole::cases(), 'value');
        $stored = $this->get('messages_rules');

        if (! is_array($stored)) {
            return array_fill_keys($all, $all);
        }

        return collect($all)->mapWithKeys(fn (string $role) => [
            $role => array_values(array_intersect($all, (array) ($stored[$role] ?? []))),
        ])->all();
    }

    /**
     * Adres do wstawienia w href: ścieżki /coś zamieniane na pełny adres strony.
     */
    public function href(string $url): string
    {
        $url = self::currentPath($url);

        return str_starts_with($url, '/') ? url($url) : $url;
    }

    /**
     * Adresy zapisane w ustawieniach po staremu (np. /druzyny, /schedule) zamieniamy na aktualne (/teams, /matches).
     */
    public static function currentPath(string $url): string
    {
        if (! preg_match('#^/([^/?\#]+)#', $url, $match) || ! array_key_exists($match[1], LegacyUrls::PREFIXES)) {
            return $url;
        }

        return LegacyUrls::translate($url);
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

        $path = trim((string) parse_url(self::currentPath($url), PHP_URL_PATH), '/');

        if ($path === '') {
            return request()->is('/');
        }

        $related = [
            'druzyny' => ['zawodnicy/*', 'sztab/*'],
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

    /**
     * Ikony serwisów do wyboru w ustawieniach (rysuje je komponent x-social-icon).
     */
    public const SOCIAL_ICONS = [
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'x' => 'X (Twitter)',
        'threads' => 'Threads',
        'linkedin' => 'LinkedIn',
        'twitch' => 'Twitch',
        'strava' => 'Strava',
        'spotify' => 'Spotify',
        'website' => 'Strona WWW / inny link',
    ];

    /**
     * Serwisy społecznościowe w profilu zawodnika: klucz => [label, icon].
     *
     * @return array<string, array{label: string, icon: string}>
     */
    public function playerSocialPlatforms(): array
    {
        return collect((array) $this->get('player_social_platforms', []))
            ->filter(fn ($platform) => filled($platform['key'] ?? null) && filled($platform['label'] ?? null))
            ->mapWithKeys(fn (array $platform) => [$platform['key'] => [
                'label' => (string) $platform['label'],
                'icon' => array_key_exists($platform['icon'] ?? '', self::SOCIAL_ICONS) ? $platform['icon'] : 'website',
            ]])
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
