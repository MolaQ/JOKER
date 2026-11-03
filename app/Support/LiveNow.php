<?php

namespace App\Support;

use App\Models\Game;
use App\Models\TournamentMatch;
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
     * @return array{title: string, detail: string, url: string}|null
     */
    public static function current(): ?array
    {
        return Cache::remember(self::CACHE_KEY, 60, fn () => self::find()) ?: null;
    }

    /**
     * @return array{title: string, detail: string, url: string}|false
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
        ];
    }
}
