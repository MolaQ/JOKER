<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\FanMvpPoll;
use App\Models\FanMvpVote;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Permission;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Kibicowskie MVP: zalogowani kibice przez kilka godzin od rozpoczęcia meczu wybierają najlepszego zawodnika klubu.
 * Wynik widać dopiero po zakończeniu głosowania; zawodnik nie głosuje na siebie, a rodzic na swoje dziecko.
 */
class FanMvp
{
    /** @var array<string, class-string<Model>> */
    public const TYPES = [
        'game' => Game::class,
        'tournament_match' => TournamentMatch::class,
    ];

    public static function enabled(): bool
    {
        return (bool) app(SiteSettings::class)['fan_mvp_enabled'];
    }

    public static function hours(): int
    {
        return max(1, (int) app(SiteSettings::class)['fan_mvp_hours']);
    }

    public static function title(): string
    {
        return (string) (app(SiteSettings::class)['fan_mvp_title'] ?: __('Kibicowskie MVP'));
    }

    public static function type(Model $match): string
    {
        return $match instanceof Game ? 'game' : 'tournament_match';
    }

    public static function find(string $type, int $id): ?Model
    {
        $class = self::TYPES[$type] ?? null;

        return $class ? $class::find($id) : null;
    }

    /**
     * Początek głosowania: data i godzina meczu. Mecz bez godziny albo odwołany nie ma głosowania.
     */
    public static function startsAt(Model $match): ?CarbonInterface
    {
        if ($match instanceof Game) {
            if (! $match->game_date || $match->status === 'cancelled' || $match->game_date->format('H:i') === '00:00') {
                return null;
            }

            return $match->game_date;
        }

        return $match instanceof TournamentMatch ? $match->starts_at : null;
    }

    public static function endsAt(Model $match): ?CarbonInterface
    {
        $start = self::startsAt($match);

        if (! $start) {
            return null;
        }

        $end = $start->copy()->addHours(self::hours());
        $closed = self::poll($match)?->closed_at;

        return $closed && $closed->lt($end) ? $closed : $end;
    }

    public static function poll(Model $match): ?FanMvpPoll
    {
        return FanMvpPoll::where('match_type', self::type($match))->where('match_id', $match->getKey())->first();
    }

    private static function pollOrCreate(Model $match): FanMvpPoll
    {
        return FanMvpPoll::firstOrCreate(['match_type' => self::type($match), 'match_id' => $match->getKey()]);
    }

    /**
     * off - brak głosowania, before - mecz jeszcze się nie zaczął, open - trwa, closed - zakończone (widać wynik).
     */
    public static function state(Model $match): string
    {
        $site = app(SiteSettings::class);
        $start = self::startsAt($match);

        if (! self::enabled() || ! $start || self::teams($match)->isEmpty() || self::poll($match)?->disabled) {
            return 'off';
        }

        if ($match instanceof Game && $match->isFriendly() && ! $site['fan_mvp_friendlies']) {
            return 'off';
        }

        if ($match instanceof TournamentMatch && ! $site['fan_mvp_tournaments']) {
            return 'off';
        }

        if (now()->lt($start)) {
            return 'before';
        }

        return now()->lt(self::endsAt($match)) ? 'open' : 'closed';
    }

    /**
     * Nasze drużyny grające w meczu (w derbach turniejowych dwie).
     *
     * @return Collection<int, Team>
     */
    public static function teams(Model $match): Collection
    {
        if ($match instanceof Game) {
            return collect([$match->team])->filter()->values();
        }

        return collect([$match->home, $match->away])
            ->filter(fn ($participant) => $participant?->isOwnTeam() && $participant->competitor instanceof Team)
            ->map(fn ($participant) => $participant->competitor)
            ->unique('id')->values();
    }

    private static function seasonId(Model $match): ?int
    {
        $seasonId = $match instanceof Game ? $match->season_id : $match->tournament()?->season_id;

        return $seasonId ?? Season::current()->value('id');
    }

    /**
     * Skład drużyny z sezonu meczu, razem ze zdjętymi z głosowania (do panelu).
     *
     * @return Collection<int, Player>
     */
    public static function roster(Model $match): Collection
    {
        $seasonId = self::seasonId($match);
        $extra = self::poll($match)?->extraIds() ?? [];

        return self::teams($match)
            ->flatMap(fn (Team $team) => Player::query()->inTeam($team->id, $seasonId)->where('is_active', true)->get())
            ->concat($extra ? Player::whereKey($extra)->get() : [])
            ->unique('id')
            ->sortBy(fn (Player $player) => mb_strtolower($player->last_name.' '.$player->first_name))
            ->values();
    }

    /**
     * Aktywni zawodnicy klubu spoza składu meczu, których prowadzący może dopisać do głosowania.
     *
     * @return Collection<int, Player>
     */
    public static function addable(Model $match): Collection
    {
        return Player::with('team')->where('is_active', true)->whereNotNull('team_id')
            ->whereNotIn('id', self::roster($match)->pluck('id'))
            ->orderBy('last_name')->orderBy('first_name')->get();
    }

    /**
     * Dopisuje do głosowania zawodnika spoza składu drużyny (np. junior, który zagrał z seniorami).
     */
    public static function addPlayer(Model $match, Player $player): void
    {
        $poll = self::pollOrCreate($match);
        $poll->update([
            'extra' => collect($poll->extraIds())->push($player->id)->unique()->values()->all(),
            'excluded' => collect($poll->excludedIds())->reject(fn (int $id) => $id === $player->id)->values()->all(),
        ]);

        AuditLog::record('fan_mvp.added', self::title().': '.$player->displayName().' dopisany do głosowania ('.self::label($match).')', $player, ['match_type' => self::type($match), 'match_id' => $match->getKey()]);
    }

    /**
     * Zawodnicy, na których można głosować.
     *
     * @return Collection<int, Player>
     */
    public static function candidates(Model $match): Collection
    {
        $excluded = self::poll($match)?->excludedIds() ?? [];

        return self::roster($match)->reject(fn (Player $player) => in_array($player->id, $excluded, true))->values();
    }

    /**
     * Zawodnicy, na których ta osoba nie może głosować: ona sama i jej dzieci.
     *
     * @return array<int, string> id zawodnika => powód
     */
    public static function blocked(User $user): array
    {
        $own = Player::where('user_id', $user->id)->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => __('Nie można oddać głosu na samego siebie')]);
        $children = $user->children()->pluck('players.id')->mapWithKeys(fn ($id) => [(int) $id => __('Nie można głosować na swoje dziecko')]);

        return $own->all() + $children->all();
    }

    public static function votedFor(User $user, Model $match): ?int
    {
        $id = FanMvpVote::where('match_type', self::type($match))->where('match_id', $match->getKey())->where('user_id', $user->id)->value('player_id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Oddaje albo zmienia głos. Zwraca komunikat błędu albo null, gdy głos zapisano.
     */
    public static function vote(User $user, Model $match, int $playerId): ?string
    {
        if (! $user->is_active) {
            return __('Głosować możesz po aktywacji konta.');
        }

        if (self::state($match) !== 'open') {
            return __('Głosowanie w tym meczu nie jest teraz otwarte.');
        }

        if (! self::candidates($match)->contains('id', $playerId)) {
            return __('Na tego zawodnika nie można głosować w tym meczu.');
        }

        if ($reason = self::blocked($user)[$playerId] ?? null) {
            return $reason.'.';
        }

        FanMvpVote::updateOrCreate(
            ['match_type' => self::type($match), 'match_id' => $match->getKey(), 'user_id' => $user->id],
            ['player_id' => $playerId],
        );

        return null;
    }

    /**
     * Wynik głosowania wśród zawodników, którzy zostali na liście: od największej liczby głosów.
     *
     * @return Collection<int, array{player: Player, votes: int, percent: int, winner: bool}>
     */
    public static function results(Model $match): Collection
    {
        $candidates = self::candidates($match)->keyBy('id');
        $votes = FanMvpVote::where('match_type', self::type($match))->where('match_id', $match->getKey())
            ->whereIn('player_id', $candidates->keys())
            ->selectRaw('player_id, count(*) as total')->groupBy('player_id')->pluck('total', 'player_id');
        $all = (int) $votes->sum();
        $top = (int) $votes->max();

        return $candidates
            ->map(fn (Player $player) => [
                'player' => $player,
                'votes' => (int) ($votes[$player->id] ?? 0),
                'percent' => $all ? (int) round(($votes[$player->id] ?? 0) * 100 / $all) : 0,
                'winner' => $top > 0 && (int) ($votes[$player->id] ?? 0) === $top,
            ])
            ->sortByDesc('votes')->values();
    }

    /**
     * Zwycięzcy zakończonego głosowania (przy remisie kilku).
     *
     * @return Collection<int, array{player: Player, votes: int, percent: int, winner: bool}>
     */
    public static function winners(Model $match): Collection
    {
        return self::state($match) === 'closed' ? self::results($match)->where('winner', true)->values() : collect();
    }

    public static function canManage(?User $user, Model $match): bool
    {
        if (! $user?->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($match instanceof TournamentMatch) {
            return $user->hasPermission(Permission::Tournaments) || $user->hasPermission(Permission::FanMvp);
        }

        return ($user->hasPermission(Permission::Games) || $user->hasPermission(Permission::FanMvp)) && $user->managesTeam($match->team_id);
    }

    /**
     * Zdejmuje zawodnika z głosowania (np. nie grał w meczu). W trakcie głosowania kasuje oddane na niego głosy,
     * żeby kibice mogli wybrać kogoś innego; po zakończeniu tytuł przechodzi na następnego według głosów.
     */
    public static function exclude(Model $match, Player $player): void
    {
        $poll = self::pollOrCreate($match);
        $poll->update(['excluded' => collect($poll->excludedIds())->push($player->id)->unique()->values()->all()]);

        if (self::state($match) === 'open') {
            FanMvpVote::where('match_type', self::type($match))->where('match_id', $match->getKey())->where('player_id', $player->id)->delete();
        }

        AuditLog::record('fan_mvp.excluded', self::title().': '.$player->displayName().' zdjęty z głosowania ('.self::label($match).')', $player, ['match_type' => self::type($match), 'match_id' => $match->getKey()]);
    }

    public static function restore(Model $match, Player $player): void
    {
        $poll = self::pollOrCreate($match);
        $poll->update(['excluded' => collect($poll->excludedIds())->reject(fn (int $id) => $id === $player->id)->values()->all()]);

        AuditLog::record('fan_mvp.restored', self::title().': '.$player->displayName().' wraca do głosowania ('.self::label($match).')', $player, ['match_type' => self::type($match), 'match_id' => $match->getKey()]);
    }

    public static function setDisabled(Model $match, bool $disabled): void
    {
        self::pollOrCreate($match)->update(['disabled' => $disabled]);

        AuditLog::record('fan_mvp.'.($disabled ? 'disabled' : 'enabled'), self::title().': głosowanie '.($disabled ? 'wyłączone' : 'włączone').' ('.self::label($match).')', $match);
    }

    public static function closeNow(Model $match): void
    {
        self::pollOrCreate($match)->update(['closed_at' => now()]);

        AuditLog::record('fan_mvp.closed', self::title().': głosowanie zamknięte przed czasem ('.self::label($match).')', $match);
    }

    public static function label(Model $match): string
    {
        if ($match instanceof Game) {
            return $match->matchup(false).', '.$match->game_date?->format('d.m.Y');
        }

        return collect([$match->home?->name(), $match->away?->name()])->filter()->implode(' – ').', '.$match->starts_at?->format('d.m.Y');
    }

    public static function url(Model $match): ?string
    {
        if ($match instanceof Game) {
            return route('games.show', $match);
        }

        $tournament = $match->tournament();

        return $tournament?->has_page ? route('tournaments.matches.show', [$tournament, $match]) : null;
    }

    /**
     * Mecze, w których zawodnik został Kibicowskim MVP (najnowsze pierwsze).
     *
     * @return Collection<int, array{label: string, url: ?string, percent: int}>
     */
    public static function winsFor(Player $player): Collection
    {
        if (! self::enabled()) {
            return collect();
        }

        return FanMvpVote::where('player_id', $player->id)->select('match_type', 'match_id')->distinct()->get()
            ->map(fn (FanMvpVote $vote) => self::find($vote->match_type, (int) $vote->match_id))
            ->filter()
            ->map(function (Model $match) use ($player) {
                $win = self::winners($match)->first(fn (array $row) => $row['player']->id === $player->id);

                return $win ? ['label' => self::label($match), 'url' => self::url($match), 'percent' => $win['percent'], 'at' => self::startsAt($match)] : null;
            })
            ->filter()->sortByDesc('at')->values();
    }

    /**
     * Ranking sezonu drużyny: zawodnicy z liczbą tytułów w meczach tej drużyny, od największej.
     *
     * @return Collection<int, array{player: Player, wins: int}>
     */
    public static function ranking(Team $team, ?Season $season): Collection
    {
        if (! self::enabled() || ! $season) {
            return collect();
        }

        $games = Game::where('team_id', $team->id)->where('season_id', $season->id)->where('game_date', '<', now())->get();
        $wins = FanMvpVote::where('match_type', 'game')->whereIn('match_id', $games->pluck('id'))->distinct()->pluck('match_id')
            ->flatMap(fn ($id) => self::winners($games->firstWhere('id', (int) $id))->pluck('player'));

        return $wins->groupBy('id')
            ->map(fn (Collection $rows) => ['player' => $rows->first(), 'wins' => $rows->count()])
            ->sortBy(fn (array $row) => mb_strtolower($row['player']->last_name))->sortByDesc('wins')->values();
    }

    /**
     * Mecze z trwającym teraz głosowaniem (pasek na stronie głównej).
     *
     * @return Collection<int, array{label: string, url: string, ends: CarbonInterface}>
     */
    public static function openNow(): Collection
    {
        if (! self::enabled()) {
            return collect();
        }

        $from = now()->subHours(self::hours());
        $games = Game::with('team')->whereBetween('game_date', [$from, now()])->where('status', '!=', 'cancelled')->get();
        $matches = TournamentMatch::with(['home.competitor', 'away.competitor', 'category.tournament'])->whereBetween('starts_at', [$from, now()])
            ->where(fn ($query) => $query->whereHas('home', fn ($home) => $home->where('competitor_type', 'team'))->orWhereHas('away', fn ($away) => $away->where('competitor_type', 'team')))
            ->get();

        return $games->concat($matches)
            ->filter(fn (Model $match) => self::state($match) === 'open' && self::url($match))
            ->map(fn (Model $match) => ['label' => self::label($match), 'url' => self::url($match).'#kibicowskie-mvp', 'ends' => self::endsAt($match)])
            ->values();
    }
}
