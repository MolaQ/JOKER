<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\TournamentMatch;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Powiadomienie „Za 15 min: …” przed meczami ligowymi i turniejowymi. Uruchamiane z harmonogramu przy każdym
 * wywołaniu crona; każdy mecz przypomina najwyżej raz.
 */
class SendUpcomingReminders extends Command
{
    protected $signature = 'joker:push-upcoming';

    protected $description = 'Wyślij przypomnienia push o meczach, które zaraz się zaczną';

    public function handle(PushNotifier $push, SiteSettings $site): int
    {
        if (! $push->enabled() || ! $site['push_upcoming']) {
            return self::SUCCESS;
        }

        $minutes = max(5, (int) $site['push_upcoming_minutes']);
        $window = [now(), now()->addMinutes($minutes)];

        $games = Game::with('team')->whereBetween('game_date', $window)->whereNull('home_score')->get()
            // Godzina 00:00 oznacza „do ustalenia”.
            ->reject(fn (Game $game) => $game->game_date->format('H:i') === '00:00');

        $matches = TournamentMatch::with(['category.tournament', 'home', 'away', 'group'])
            ->whereBetween('starts_at', $window)->where('is_live', false)->whereNull('home_sets')->get();

        foreach ([...$games, ...$matches] as $match) {
            if (Cache::add('push-sent:upcoming-'.class_basename($match).'-'.$match->id, true, now()->addDays(2))) {
                $push->upcoming($match, (int) ceil(now()->diffInMinutes($match instanceof Game ? $match->game_date : $match->starts_at)));
            }
        }

        return self::SUCCESS;
    }
}
