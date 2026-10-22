<?php

namespace App\Support;

use App\Models\Game;
use App\Models\TournamentMatch;
use Illuminate\Database\Eloquent\Model;

/**
 * Lista meczów do wyboru w formularzach ("game:12", "tournament_match:5") i odczyt wybranego.
 */
class MatchPicker
{
    public const TYPES = ['game' => Game::class, 'tournament_match' => TournamentMatch::class];

    /**
     * Mecze z ostatnich tygodni i najbliższych dni, pogrupowane na ligowe i turniejowe.
     *
     * @return array<string, array<string, string>>
     */
    public static function recent(int $weeksBack = 6, int $weeksAhead = 2): array
    {
        $from = now()->subWeeks($weeksBack);
        $to = now()->addWeeks($weeksAhead);

        return array_filter([
            __('Mecze ligowe') => Game::with(['team', 'competition', 'opponentTeam'])
                ->whereBetween('game_date', [$from, $to])
                ->orderByDesc('game_date')
                ->get()
                ->mapWithKeys(fn (Game $game) => ['game:'.$game->id => $game->mediaLabel()])
                ->all(),
            __('Turnieje') => TournamentMatch::with(['category.tournament', 'home.competitor', 'away.competitor'])
                ->whereBetween('starts_at', [$from, $to])
                ->orderByDesc('starts_at')
                ->get()
                ->mapWithKeys(fn (TournamentMatch $match) => ['tournament_match:'.$match->id => $match->mediaLabel()])
                ->all(),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function keys(array $options): array
    {
        return collect($options)->flatMap(fn (array $group) => array_keys($group))->all();
    }

    public static function find(?string $key): ?Model
    {
        [$type, $id] = array_pad(explode(':', (string) $key, 2), 2, null);
        $class = self::TYPES[$type] ?? null;

        return $class && ctype_digit((string) $id) ? $class::find((int) $id) : null;
    }

    public static function keyFor(?Model $match): string
    {
        $type = array_search($match ? $match::class : null, self::TYPES, true);

        return $type && $match ? $type.':'.$match->getKey() : '';
    }
}
