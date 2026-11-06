<?php

namespace App\Support;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Co trwa teraz: mecze trwającego turnieju (przełącznik „Trwa” w edytorze) albo mecz drużyny,
 * który zaczął się w ciągu ostatnich 2,5 godziny i nie ma jeszcze wyniku. Pasek „Na żywo”
 * nad dolnym menu strony prowadzi kibica prosto do relacji. Liczone raz na minutę.
 */
class LiveNow
{
    public const CACHE_KEY = 'live-now';

    /**
     * @return array{title: string, detail: string, url: string, matches?: list<array{category: string, court: ?string, home: string, away: string, score: string, stream: ?string, url: string}>}|null
     */
    public static function current(): ?array
    {
        return Cache::remember(self::CACHE_KEY, 60, fn () => self::find()) ?: null;
    }

    /**
     * @return array{title: string, detail: string, url: string, matches?: list<array<string, mixed>>}|false
     */
    private static function find(): array|false
    {
        $live = TournamentMatch::query()->where('is_live', true)->with('category.tournament')->get();
        $tournament = $live->map->tournament()->filter(fn ($tournament) => $tournament?->has_page)->first();

        if ($tournament) {
            $count = $live->filter(fn (TournamentMatch $match) => $match->tournament()?->is($tournament))->count();

            return [
                'title' => $tournament->fullName(),
                'detail' => trans_choice('Trwa :count mecz|Trwają :count mecze|Trwa :count meczów', $count),
                'url' => route('tournaments.show', $tournament),
                'matches' => self::tournamentMatches($tournament, $live),
            ];
        }

        $game = Game::query()->with('team', 'opponentTeam')
            ->where('status', 'scheduled')
            ->whereBetween('game_date', [now()->subMinutes(150), now()])
            ->orderByDesc('game_date')
            ->get()
            ->first(fn (Game $game) => $game->game_date->format('H:i') !== '00:00');

        if (! $game) {
            return false;
        }

        return [
            'title' => $game->matchup(withClub: false),
            'detail' => __('Mecz trwa'),
            'url' => route('games.show', $game),
            'stream' => $game->normalizedStreams()[0]['url'] ?? null,
        ];
    }

    /**
     * Trwające mecze turnieju z wynikiem w setach; każdy prowadzi do zakładki „Mecze” swojej kategorii.
     * Mecze naszych drużyn idą pierwsze, potem mecze z transmisją.
     *
     * @param  Collection<int, TournamentMatch>  $live
     * @return list<array{category: string, court: ?string, home: string, away: string, score: string, stream: ?string, url: string}>
     */
    private static function tournamentMatches(Tournament $tournament, Collection $live): array
    {
        $first = $tournament->categories()->value('id');
        $brackets = [];

        return $live->filter(fn (TournamentMatch $match) => $match->tournament()?->is($tournament))
            ->load('home.competitor', 'away.competitor')
            ->sortBy(fn (TournamentMatch $match) => [
                $match->home?->isOwnTeam() || $match->away?->isOwnTeam() ? 0 : 1,
                $match->hasStreams() ? 0 : 1,
                $match->category->display_order,
                $match->court,
                $match->number,
            ])
            ->map(function (TournamentMatch $match) use ($tournament, $first, &$brackets) {
                $bracket = $brackets[$match->tournament_category_id] ??= TournamentBracket::for($match->category);

                return [
                    'category' => $match->category->name,
                    'court' => $match->court,
                    'home' => $bracket->side($match, 'home')['text'],
                    'away' => $bracket->side($match, 'away')['text'],
                    'score' => ($match->home_sets ?? 0).':'.($match->away_sets ?? 0),
                    'stream' => $match->normalizedStreams()[0]['url'] ?? null,
                    'url' => route('tournaments.show', [
                        'tournament' => $tournament,
                        'category' => $match->tournament_category_id === $first ? null : $match->category->slug(),
                        'tab' => 'matches',
                    ]),
                ];
            })
            ->values()
            ->all();
    }
}
