<?php

namespace App\Support;

use App\Models\Game;
use App\Models\MiniTrip;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Kafelki dnia w kalendarzu meczów: mecze jednej drużyny z jednego dnia razem (kategoria, rozgrywki, pary herbów),
 * osobno turnieje minisiatkówki i turnieje bez rozpisanych naszych meczów.
 */
class CalendarTiles
{
    /**
     * @param  Collection<int, Game>  $games
     * @param  Collection<int, TournamentMatch>  $tournamentMatches
     * @param  Collection<int, Tournament>  $tournaments
     * @param  Collection<int, MiniTrip>  $miniTrips
     * @return Collection<int, array<string, mixed>>
     */
    public static function forDay(Collection $games, Collection $tournamentMatches, Collection $tournaments, Collection $miniTrips): Collection
    {
        $tiles = collect();

        foreach ($miniTrips as $trip) {
            $groups = $trip->teams->pluck('group')->filter()->unique('id')->sortByDesc('players_on_court')->values();
            $tiles->push([
                'key' => 'mini-'.$trip->id,
                'type' => 'mini',
                'label' => $groups->pluck('name')->implode(' · ') ?: __('Minisiatkówka'),
                'color' => $groups->first()?->iconColor() ?? AgeColors::forMini(4),
                'competition' => $trip->title,
                'groups' => $groups,
                'trip' => $trip,
                'url' => $trip->url(),
                'order' => -1,
            ]);
        }

        $matches = $games->sortBy('game_date')->map(fn (Game $game) => self::fromGame($game))
            ->concat($tournamentMatches->sortBy('starts_at')->map(fn (TournamentMatch $match) => self::fromTournamentMatch($match)))
            ->filter();

        foreach ($matches->groupBy('group') as $group => $list) {
            $first = $list->first();
            $tiles->push([
                'key' => 'team-'.$group,
                'type' => 'matches',
                'label' => $first['team'],
                'color' => $first['color'],
                'competition' => $list->pluck('competition')->filter()->unique()->count() === 1 ? $first['competition'] : __('różne rozgrywki'),
                'matches' => $list->values()->all(),
                'order' => $first['order'],
            ]);
        }

        // Turniej z rozpisanymi naszymi meczami pokazują same mecze; bez nich zostaje kafelek turnieju.
        $withMatches = $tournamentMatches->map(fn (TournamentMatch $match) => $match->category?->tournament_id)
            ->concat($games->pluck('tournament_id'))->filter()->unique();

        foreach ($tournaments->reject(fn (Tournament $tournament) => $withMatches->contains($tournament->id)) as $tournament) {
            $tiles->push([
                'key' => 'tournament-'.$tournament->id,
                'type' => 'tournament',
                'label' => $tournament->teams->pluck('name')->implode(' · ') ?: __('Turniej'),
                'color' => AgeColors::forTeam($tournament->teams->first()),
                'competition' => $tournament->fullName(),
                'tournament' => $tournament,
                'url' => $tournament->has_page ? route('tournaments.show', $tournament) : null,
                'order' => 999,
            ]);
        }

        return $tiles->sortBy('order')->values();
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromGame(Game $game): array
    {
        $ourLabel = $game->ourLabel();
        $ours = ['name' => trim($ourLabel['name'].' '.($ourLabel['badge'] ?? '')), 'logo' => Media::url($game->team?->logoPath())];
        $rival = ['name' => $game->opponentShortName(), 'logo' => Media::url($game->opponentTeam?->logoPath())];
        $result = $game->ourSetsResult();
        $outcome = $game->outcome();
        $hasTime = $game->game_date->format('H:i') !== '00:00';
        $article = $game->latestArticle();

        return [
            'id' => 'g'.$game->id,
            'group' => 'team-'.($game->team_id ?? 'x').'-'.$game->game_date->toDateString(),
            'team' => $game->team?->name ?? (string) $ourLabel['name'],
            'color' => AgeColors::forTeam($game->team),
            'order' => (int) ($game->team?->display_order ?? 0),
            'home' => $game->is_home ? $ours : $rival,
            'away' => $game->is_home ? $rival : $ours,
            'ourSide' => $game->is_home ? 'home' : 'away',
            'mid' => $game->isFinished() && $result ? $game->home_score.':'.$game->away_score : ($hasTime ? $game->game_date->format('H:i') : ''),
            'finished' => $game->isFinished() && $result !== null,
            'outcome' => $outcome,
            'outcomeLabel' => match ($outcome) {
                'win' => __('Wygrana'),
                'loss' => __('Porażka'),
                'derby' => (string) app(SiteSettings::class)['derby_winner'],
                default => $game->is_home ? __('U siebie') : __('Wyjazd'),
            },
            'date' => Str::ucfirst($game->game_date->translatedFormat('l, j F')).($hasTime ? ', '.$game->game_date->format('H:i') : ''),
            'competition' => $game->competitionLabel(),
            'round' => $game->round_label,
            'venue' => $game->venue,
            'sets' => is_array($game->sets_score) && count($game->sets_score) ? collect($game->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(', ') : null,
            'note' => trim(collect([$game->result_note, $game->mvp ? '★ '.$game->mvp : null])->filter()->implode(' · ')) ?: null,
            'friendly' => $game->isFriendly(),
            'url' => route('games.show', $game),
            'article' => $article ? route('articles.show', $article) : null,
            'calendar' => $game->isUpcoming() ? route('games.calendar', $game) : null,
        ];
    }

    /**
     * Mecz naszej drużyny w turnieju prowadzonym na stronie (rozpisany w harmonogramie turnieju).
     *
     * @return array<string, mixed>|null
     */
    public static function fromTournamentMatch(TournamentMatch $match): ?array
    {
        $tournament = $match->category?->tournament;
        $ourParticipant = $match->home?->isOwnTeam() ? $match->home : ($match->away?->isOwnTeam() ? $match->away : null);
        $team = $ourParticipant?->competitor instanceof Team ? $ourParticipant->competitor : null;

        if (! $tournament || ! $ourParticipant || ! $match->starts_at) {
            return null;
        }

        $side = fn (?TournamentParticipant $participant, string $fallback) => [
            'name' => $participant?->name() ?? $fallback,
            'logo' => Media::url($participant?->logoPath()),
        ];
        $finished = $match->isFinished() && $match->home_sets !== null && $match->away_sets !== null;
        $ourHome = $match->home?->is($ourParticipant);
        $outcome = null;

        if ($finished) {
            $bothOurs = $match->home?->isOwnTeam() && $match->away?->isOwnTeam();
            $won = $ourHome ? $match->home_sets > $match->away_sets : $match->away_sets > $match->home_sets;
            $outcome = $bothOurs ? 'derby' : ($won ? 'win' : 'loss');
        }

        return [
            'id' => 't'.$match->id,
            'group' => 'team-'.($team?->id ?? 'p'.$ourParticipant->id).'-'.$match->starts_at->toDateString(),
            'team' => $team?->name ?? $ourParticipant->name(),
            'color' => $match->category->color() ?: AgeColors::forTeam($team),
            'order' => (int) ($team?->display_order ?? 0),
            'home' => $side($match->home, (string) $match->home_placeholder),
            'away' => $side($match->away, (string) $match->away_placeholder),
            'ourSide' => $ourHome ? 'home' : 'away',
            'mid' => $finished ? $match->home_sets.':'.$match->away_sets : $match->starts_at->format('H:i'),
            'finished' => $finished,
            'outcome' => $outcome,
            'outcomeLabel' => match ($outcome) {
                'win' => __('Wygrana'),
                'loss' => __('Porażka'),
                'derby' => __('Nasze drużyny'),
                default => __('Turniej'),
            },
            'date' => Str::ucfirst($match->starts_at->translatedFormat('l, j F, H:i')),
            'competition' => $tournament->fullName(),
            'round' => collect([$match->category->name, $match->phase_name ?: $match->label, $match->court ? __('boisko :court', ['court' => $match->court]) : null])->filter()->implode(' · '),
            'venue' => collect([$tournament->venue, $tournament->city])->filter()->implode(', ') ?: null,
            'sets' => $match->setsLabel(),
            'note' => $match->mvp ? '★ '.$match->mvp : null,
            'friendly' => false,
            'url' => $tournament->has_page ? route('tournaments.matches.show', [$tournament, $match]) : null,
            'article' => null,
            'calendar' => null,
        ];
    }
}
