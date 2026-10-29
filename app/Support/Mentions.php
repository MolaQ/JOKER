<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Wzmianki w artykułach: „@Imię Nazwisko” zamienia się przy wyświetlaniu w link do profilu zawodnika.
 */
class Mentions
{
    /** @var array<string, Player>|null */
    private static ?array $players = null;

    /**
     * Zawodnicy do podpowiedzi w edytorze (po wpisaniu @).
     *
     * @return list<array{name: string, team: string|null}>
     */
    public static function suggestions(): array
    {
        return Player::query()->with('team:id,name')->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'team_id', 'is_active'])
            ->map(fn (Player $player) => ['name' => $player->full_name, 'team' => $player->is_active ? $player->team?->name : null])
            ->values()
            ->all();
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
                    $player = self::players()[mb_strtolower(implode(' ', array_slice($words, 0, $count)))] ?? null;

                    if ($player) {
                        $shown = implode('', array_slice($pieces, 0, $count * 2 - 1));

                        return '<a href="'.e(route('players.show', $player, false)).'" class="mention">'.$shown.'</a>'.implode('', array_slice($pieces, $count * 2 - 1));
                    }
                }

                return $match[0];
            },
            $text,
        ) ?? $text;
    }

    /**
     * @return array<string, Player>
     */
    private static function players(): array
    {
        return self::$players ??= Player::query()->get(['id', 'first_name', 'last_name'])
            ->keyBy(fn (Player $player) => mb_strtolower($player->full_name))
            ->all();
    }

    public static function flush(): void
    {
        self::$players = null;
    }

    /**
     * Opublikowane artykuły, w których pada imię i nazwisko zawodnika (z @ albo bez).
     *
     * @return Collection<int, Article>
     */
    public static function articlesAbout(Player $player, int $limit = 12): Collection
    {
        $name = $player->full_name;

        return Article::published()
            ->where(fn (Builder $query) => $query->where('content', 'like', '%'.$name.'%')->orWhere('title', 'like', '%'.$name.'%'))
            ->limit($limit)
            ->get(['id', 'title', 'title_en', 'slug', 'published_at']);
    }
}
