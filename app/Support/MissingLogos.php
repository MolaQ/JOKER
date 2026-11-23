<?php

namespace App\Support;

use App\Models\Game;
use App\Models\RivalTeam;
use Illuminate\Support\Collection;

/**
 * Mecze w najbliższych 14 dniach, w których rywal nie ma logo: do uzupełnienia przed grafikami transmisji.
 */
class MissingLogos
{
    public const DAYS = 14;

    /**
     * @return Collection<int, array{game: Game, rival: ?RivalTeam}>
     */
    public static function upcoming(): Collection
    {
        return Game::with(['team', 'competition', 'opponentTeam.club'])
            ->where('status', '!=', 'finished')
            ->whereBetween('game_date', [today(), today()->addDays(self::DAYS)->endOfDay()])
            ->orderBy('game_date')
            ->get()
            ->filter(fn (Game $game) => $game->opponentLogoPath() === null)
            ->map(fn (Game $game) => [
                'game' => $game,
                'rival' => $game->opponentTeam ?? (filled($game->opponent) ? RivalTeam::with('club')->find(RivalTeam::findByName($game->opponent)?->id) : null),
            ])
            ->values();
    }

    public static function count(): int
    {
        return self::upcoming()->count();
    }
}
