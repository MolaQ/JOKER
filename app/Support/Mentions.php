<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Player;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Wzmianki w artykułach: „@Imię Nazwisko” zamienia się przy wyświetlaniu w link do profilu
 * zawodnika albo osoby z publicznym profilem (sztab, administracja klubu).
 */
class Mentions
{
    /** @var array<string, string>|null imię i nazwisko (małymi literami) => adres profilu */
    private static ?array $profiles = null;

    /**
     * Osoby do podpowiedzi w edytorze (po wpisaniu @): zawodnicy i osoby z publicznym profilem.
     *
     * @return list<array{name: string, team: string|null}>
     */
    public static function suggestions(): array
    {
        $players = Player::query()->with('team:id,name')->get(['id', 'first_name', 'last_name', 'team_id', 'is_active'])
            ->toBase()->map(fn (Player $player) => ['name' => $player->full_name, 'team' => $player->is_active ? $player->team?->name : null]);

        $people = User::withPublicProfile()->get(['id', 'name'])
            ->map(fn (User $user) => ['name' => $user->name, 'team' => $user->rolesLabel() ?: __('Sztab')]);

        return $players->concat($people)->unique('name')->sortBy('name', SORT_LOCALE_STRING)->values()->all();
    }

    /**
     * Zamienia „@Imię Nazwisko” na link do profilu. Rusza tylko tekst, nie znaczniki i nie istniejące linki.
     */
    public static function link(?string $html): ?string
    {
        if ($html === null || ! str_contains($html, '@')) {
            return $html;
        }

        $parts = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
        $insideLink = 0;

        foreach ($parts as $index => $part) {
            if (str_starts_with($part, '<')) {
                if (preg_match('/^<a[\s>]/i', $part)) {
                    $insideLink++;
                } elseif (preg_match('/^<\/a>/i', $part)) {
                    $insideLink = max(0, $insideLink - 1);
                }

                continue;
            }

            if ($insideLink === 0 && str_contains($part, '@')) {
                $parts[$index] = self::linkText($part);
            }
        }

        return implode('', $parts);
    }

    private static function linkText(string $text): string
    {
        return preg_replace_callback(
            '/(?<![\p{L}\p{N}._%+-])@(\p{Lu}[\p{L}\'’-]*(?:(?:\s|&nbsp;)+\p{Lu}[\p{L}\'’-]*){1,2})/u',
            function (array $match) {
                // Słowa i odstępy na zmianę: [słowo, odstęp, słowo, ...].
                $pieces = preg_split('/((?:\s|&nbsp;)+)/u', $match[1], -1, PREG_SPLIT_DELIM_CAPTURE);
                $words = array_values(array_filter($pieces, fn (int $key) => $key % 2 === 0, ARRAY_FILTER_USE_KEY));

                // Najpierw najdłuższe dopasowanie (np. imię i dwa człony nazwiska), potem krótsze.
                for ($count = count($words); $count >= 2; $count--) {
                    $url = self::profiles()[mb_strtolower(implode(' ', array_slice($words, 0, $count)))] ?? null;

                    if ($url) {
                        $shown = implode('', array_slice($pieces, 0, $count * 2 - 1));

                        return '<a href="'.e($url).'" class="mention">'.$shown.'</a>'.implode('', array_slice($pieces, $count * 2 - 1));
                    }
                }

                return $match[0];
            },
            $text,
        ) ?? $text;
    }

    /**
     * @return array<string, string>
     */
    private static function profiles(): array
    {
        if (self::$profiles !== null) {
            return self::$profiles;
        }

        // Przy tej samej nazwie wygrywa profil zawodnika.
        $people = User::withPublicProfile()->get(['id', 'name'])->toBase()
            ->mapWithKeys(fn (User $user) => [mb_strtolower($user->name) => $user->profileUrl(false)]);
        $players = Player::query()->get(['id', 'first_name', 'last_name'])->toBase()
            ->mapWithKeys(fn (Player $player) => [mb_strtolower($player->full_name) => route('players.show', $player, false)]);

        return self::$profiles = $people->merge($players)->all();
    }

    public static function flush(): void
    {
        self::$profiles = null;
    }

    /**
     * Opublikowane artykuły, w których ktoś oznaczył tę osobę („@Imię Nazwisko”).
     *
     * @return Collection<int, Article>
     */
    public static function articlesAbout(string $name, int $limit = 12): Collection
    {
        return Article::published()
            ->where(fn (Builder $query) => $query->where('content', 'like', '%@'.$name.'%')->orWhere('content_en', 'like', '%@'.$name.'%'))
            ->limit($limit)
            ->get(['id', 'title', 'title_en', 'slug', 'published_at']);
    }
}
