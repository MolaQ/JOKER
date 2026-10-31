<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Link do transmisji meczu: rozpoznaje platformę (YouTube, Facebook, inna) i buduje adres osadzenia.
 */
class StreamLink
{
    public const YOUTUBE = 'youtube';

    public const FACEBOOK = 'facebook';

    public const OTHER = 'other';

    public static function platform(string $url): string
    {
        $host = self::host($url);

        return match (true) {
            $host === 'youtu.be', self::isDomain($host, 'youtube.com'), self::isDomain($host, 'youtube-nocookie.com') => self::YOUTUBE,
            in_array($host, ['fb.watch', 'fb.com', 'fb.me'], true), self::isDomain($host, 'facebook.com') => self::FACEBOOK,
            default => self::OTHER,
        };
    }

    public static function platformName(string $platform, string $url = ''): string
    {
        return match ($platform) {
            self::YOUTUBE => 'YouTube',
            self::FACEBOOK => 'Facebook',
            default => self::host($url) ?: __('Transmisja'),
        };
    }

    /**
     * 11-znakowy identyfikator filmu YouTube (watch?v=, youtu.be/, /live/, /shorts/, /embed/).
     */
    public static function youtubeId(string $url): ?string
    {
        if (self::platform($url) !== self::YOUTUBE) {
            return null;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        $candidate = match (true) {
            self::host($url) === 'youtu.be' => trim($path, '/'),
            isset($query['v']) && is_string($query['v']) => $query['v'],
            (bool) preg_match('#^/(?:live|shorts|embed|v)/([^/?]+)#', $path, $matches) => $matches[1],
            default => null,
        };

        return $candidate !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    public static function youtubeEmbedUrl(string $url): ?string
    {
        $id = self::youtubeId($url);

        return $id ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
    }

    /**
     * Ujednolicona lista transmisji: pomija puste i niepoprawne adresy oraz duplikaty.
     *
     * @param  array<int, mixed>|null  $streams
     * @return array<int, array{url: string, label: string, platform: string, platform_name: string, youtube_id: ?string, embed_url: ?string, thumbnail_url: ?string}>
     */
    public static function normalize(?array $streams): array
    {
        return collect($streams ?? [])
            ->map(fn ($stream) => is_string($stream) ? ['url' => $stream] : (array) $stream)
            ->map(fn (array $stream) => ['url' => trim((string) ($stream['url'] ?? '')), 'label' => trim((string) ($stream['label'] ?? ''))])
            ->filter(fn (array $stream) => filter_var($stream['url'], FILTER_VALIDATE_URL) && Str::startsWith($stream['url'], ['http://', 'https://']))
            ->unique('url')
            ->map(fn (array $stream) => self::describe($stream['url'], $stream['label']))
            ->values()
            ->all();
    }

    /**
     * @return array{url: string, label: string, platform: string, platform_name: string, youtube_id: ?string, embed_url: ?string, thumbnail_url: ?string}
     */
    public static function describe(string $url, string $label = ''): array
    {
        $platform = self::platform($url);
        $youtubeId = self::youtubeId($url);

        return [
            'url' => $url,
            'label' => $label !== '' ? $label : self::platformName($platform, $url),
            'platform' => $platform,
            'platform_name' => self::platformName($platform, $url),
            'youtube_id' => $youtubeId,
            'embed_url' => $youtubeId ? 'https://www.youtube-nocookie.com/embed/'.$youtubeId : null,
            'thumbnail_url' => $youtubeId ? 'https://i.ytimg.com/vi/'.$youtubeId.'/hqdefault.jpg' : null,
        ];
    }

    private static function isDomain(string $host, string $domain): bool
    {
        return $host === $domain || Str::endsWith($host, '.'.$domain);
    }

    private static function host(string $url): string
    {
        return Str::lower(Str::after((string) parse_url($url, PHP_URL_HOST), 'www.'));
    }
}
