<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class PlayerController extends Controller
{
    /**
     * Profil zawodnika jak na volleybox: dane fizyczne, kariera sezon po sezonie,
     * osiągnięcia i ostatnie mecze jego drużyn.
     */
    public function show(Player $player): View
    {
        $player->load(['team', 'likes', 'achievements']);
        $player->registerView();

        $currentSeasonId = Season::current()->value('id');
        $seasonTeams = $currentSeasonId
            ? $player->teamsForSeason($currentSeasonId)->get()
            : collect([$player->team]);

        $teamIds = $seasonTeams->filter()->pluck('id')->push($player->team_id)->filter()->unique();

        $recentGames = Game::with(['team', 'competition.teams', 'opponentTeam', 'articles'])
            ->whereIn('team_id', $teamIds)
            ->when($currentSeasonId, fn ($query) => $query->where('season_id', $currentSeasonId))
            ->where('status', 'finished')
            ->orderByDesc('game_date')
            ->limit(6)
            ->get();

        return view('players.show', [
            'player' => $player,
            'seasonTeams' => $seasonTeams,
            'career' => $this->career($player),
            'recentGames' => $recentGames,
            'photos' => $player->taggedPhotos(),
        ]);
    }

    /**
     * Kariera z kadr sezonowych, od najnowszego sezonu.
     *
     * @return Collection<int, array{season: ?Season, team: Team, jersey: ?int, captain: bool, competitions: Collection<int, string>}>
     */
    private function career(Player $player): Collection
    {
        $entries = $player->teams()->get();
        $seasons = Season::whereIn('id', $entries->pluck('pivot.season_id')->filter()->unique())->get()->keyBy('id');
        $competitions = Competition::with('teams:id')
            ->whereIn('season_id', $seasons->keys())
            ->orderBy('display_order')
            ->get();

        // Zawodnik gra w jednych rozgrywkach tylko w jednej drużynie. Gdy dwie jego drużyny są w tych samych
        // rozgrywkach (np. kadeci i juniorzy II w III lidze), rozgrywki pokazujemy tylko przy drużynie głównej.
        $shown = [];

        return $entries
            ->sortByDesc(fn (Team $team) => ($team->id === $player->team_id ? 1 : 0).'-'.(1000000 - $team->id))
            ->map(function (Team $team) use ($competitions, $seasons, &$shown) {
                $own = $competitions
                    ->filter(fn (Competition $competition) => $competition->season_id === $team->pivot->season_id && $competition->teams->contains('id', $team->id))
                    ->reject(fn (Competition $competition) => isset($shown[$competition->id]));

                foreach ($own as $competition) {
                    $shown[$competition->id] = true;
                }

                return [
                    'season' => $seasons->get($team->pivot->season_id),
                    'team' => $team,
                    'jersey' => $team->pivot->jersey_number,
                    'captain' => (bool) $team->pivot->is_captain,
                    'competitions' => $own->pluck('name')->values(),
                ];
            })
            ->sortByDesc(fn (array $entry) => ($entry['season']?->start_date?->format('Y-m-d') ?? '0').'-'.($entry['team']->id === $player->team_id ? 1 : 0))
            ->values();
    }
}
