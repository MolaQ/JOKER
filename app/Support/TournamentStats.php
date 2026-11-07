<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TournamentMatch;
use App\Models\TournamentParticipant;
use Illuminate\Support\Collection;

/**
 * Statystyki turnieju dla kibiców: liderzy (podium w każdej statystyce), rekordy i liczby, liczone tylko
 * z zakończonych meczów. Zakres: grupa, kategoria albo cały turniej.
 *
 * Format meczu nie ma znaczenia (do 2 albo 3 wygranych setów, albo zawsze 3 sety):
 * tie-break to ostatni set meczu rozegrany przy remisie w setach (1:1 albo 2:2), a odwrócenie losów
 * to wygrana po przegranym pierwszym secie.
 */
class TournamentStats
{
    /**
     * Liderzy: klucz => [nazwa, opis, jednostka]. Kolejność to kolejność kafelków na stronie.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const LEADERS = [
        'points' => ['Najwięcej zdobytych punktów', 'Suma małych punktów ze wszystkich setów.'],
        'points_diff' => ['Najlepszy bilans punktów', 'Punkty zdobyte minus stracone.'],
        'avg_lost' => ['Najszczelniejsza obrona', 'Najmniej straconych punktów na set (co najmniej 2 mecze).'],
        'comebacks' => ['Odwrócone losy meczu', 'Wygrane mecze po przegranym pierwszym secie (także z 0:2).'],
        'tiebreaks' => ['Królowie tie-breaków', 'Wygrane decydujące sety przy remisie w setach.'],
        'deuce' => ['Nerwy ze stali', 'Wygrane sety na przewagi (np. 26:24, 16:14).'],
        'streak' => ['Najdłuższa seria setów', 'Wygrane sety z rzędu.'],
        'sweeps' => ['Bez straty seta', 'Mecze wygrane bez oddania seta.'],
        'sets_played' => ['Najwięcej rozegranych setów', 'Wszystkie sety drużyny w turnieju.'],
    ];

    /** Pozostałe sekcje do włączenia w panelu. */
    public const SECTIONS = [
        'records' => 'Rekordy (najwyższa wygrana w secie, najdłuższy set, najbardziej wyrównany mecz)',
        'totals' => 'Liczby (mecze, sety, punkty, tie-breaki)',
    ];

    public const SETTING_PREFIX = 'tournament_stats_';

    /**
     * Wszystkie statystyki do wyboru w panelu: klucz => nazwa.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::LEADERS)->map(fn (array $stat) => $stat[0])->all() + self::SECTIONS;
    }

    /**
     * Statystyki włączone dla turnieju (domyślnie wszystkie).
     *
     * @return list<string>
     */
    public static function enabled(Tournament $tournament): array
    {
        $stored = app(SiteSettings::class)->get(self::SETTING_PREFIX.$tournament->id);

        return is_array($stored)
            ? array_values(array_intersect(array_keys(self::options()), $stored))
            : array_keys(self::options());
    }

    /**
     * @param  list<string>  $keys
     */
    public static function saveEnabled(Tournament $tournament, array $keys): void
    {
        app(SiteSettings::class)->set([self::SETTING_PREFIX.$tournament->id => array_values(array_intersect(array_keys(self::options()), $keys))]);
    }

    /** @var Collection<int, array{match: TournamentMatch, category: TournamentCategory, home: TournamentParticipant, away: TournamentParticipant, sets: list<array{0: int, 1: int}>}> */
    private Collection $games;

    /**
     * @param  Collection<int, array{match: TournamentMatch, category: TournamentCategory, home: TournamentParticipant, away: TournamentParticipant, sets: list<array{0: int, 1: int}>}>  $games
     */
    private function __construct(Collection $games, private bool $withCategory = false)
    {
        $this->games = $games->sortBy(fn (array $game) => [$game['match']->starts_at?->getTimestamp() ?? PHP_INT_MAX, $game['match']->number ?? PHP_INT_MAX, $game['match']->id])->values();
    }

    /**
     * Statystyki kategorii, a z $groupId tylko meczów fazy grupowej tej grupy.
     */
    public static function forCategory(TournamentCategory $category, ?int $groupId = null): self
    {
        $matches = $category->matches->when($groupId !== null, fn (Collection $matches) => $matches->where('stage', TournamentMatch::STAGE_GROUP)->where('tournament_group_id', $groupId));

        return new self(self::games($category, $matches));
    }

    /**
     * Statystyki całego turnieju (drużyny z różnych kategorii podpisane kategorią).
     *
     * @param  Collection<int, TournamentCategory>  $categories
     */
    public static function forTournament(Collection $categories): self
    {
        return new self($categories->flatMap(fn (TournamentCategory $category) => self::games($category, $category->matches)), withCategory: true);
    }

    /**
     * Zakończone mecze z ustalonymi drużynami (w drabince z rozstrzygniętych źródeł).
     *
     * @param  Collection<int, TournamentMatch>  $matches
     * @return Collection<int, array{match: TournamentMatch, category: TournamentCategory, home: TournamentParticipant, away: TournamentParticipant, sets: list<array{0: int, 1: int}>}>
     */
    private static function games(TournamentCategory $category, Collection $matches): Collection
    {
        $bracket = null;

        return $matches->filter(fn (TournamentMatch $match) => $match->isFinished())->map(function (TournamentMatch $match) use ($category, &$bracket) {
            $home = $match->home;
            $away = $match->away;

            if (($home === null || $away === null) && ($match->home_source || $match->away_source)) {
                $bracket ??= TournamentBracket::for($category);
                $home ??= $bracket->side($match, 'home')['participant'];
                $away ??= $bracket->side($match, 'away')['participant'];
            }

            if ($home === null || $away === null) {
                return null;
            }

            $sets = collect((array) ($match->sets_score ?? []))
                ->map(fn ($set) => array_map('intval', array_values((array) $set) + [0, 0]))
                ->filter(fn (array $set) => $set[0] !== $set[1])
                ->map(fn (array $set) => [$set[0], $set[1]])
                ->values()->all();

            return ['match' => $match, 'category' => $category, 'home' => $home, 'away' => $away, 'sets' => $sets];
        })->filter()->values();
    }

    public function isEmpty(): bool
    {
        return $this->games->isEmpty();
    }

    /**
     * Set wygrany na przewagi: dwa punkty różnicy, a przegrany miał co najmniej 24 (w secie do 15: 14).
     *
     * @param  array{0: int, 1: int}  $set
     */
    public static function isDeuce(array $set): bool
    {
        $loser = min($set);

        return abs($set[0] - $set[1]) === 2 && $loser >= (max($set) < 25 ? 14 : 24);
    }

    /**
     * Czy ostatni set meczu był tie-breakiem (przed nim remis w setach).
     *
     * @param  list<array{0: int, 1: int}>  $sets
     */
    public static function hasTiebreak(array $sets): bool
    {
        if (count($sets) < 3) {
            return false;
        }

        $before = array_slice($sets, 0, -1);
        $home = count(array_filter($before, fn (array $set) => $set[0] > $set[1]));

        return $home * 2 === count($before);
    }

    /**
     * Liczby każdej drużyny.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public function teams(): Collection
    {
        $rows = [];
        $runs = [];

        foreach ($this->games as $game) {
            $match = $game['match'];
            $homeWon = $match->home_sets > $match->away_sets;
            $sets = $game['sets'];
            $tiebreak = self::hasTiebreak($sets);

            foreach (['home' => 0, 'away' => 1] as $side => $index) {
                $participant = $game[$side];
                $key = $game['category']->id.':'.$participant->id;
                $won = $side === 'home' ? $homeWon : ! $homeWon;
                $row = $rows[$key] ?? [
                    'participant' => $participant,
                    'name' => $participant->name().($this->withCategory ? ' · '.$game['category']->name : ''),
                    'own' => $participant->isOwnTeam(),
                    'logo' => $participant->logoPath(),
                    'team' => $participant->name(),
                    'matches' => 0, 'wins' => 0, 'sets_won' => 0, 'sets_lost' => 0, 'scored_sets' => 0,
                    'points' => 0, 'points_lost' => 0, 'comebacks' => 0, 'big_comebacks' => 0, 'tiebreaks' => 0, 'tiebreaks_played' => 0,
                    'deuce' => 0, 'streak' => 0, 'sweeps' => 0,
                ];

                $row['matches']++;
                $row['wins'] += (int) $won;
                $row['sets_won'] += $side === 'home' ? (int) $match->home_sets : (int) $match->away_sets;
                $row['sets_lost'] += $side === 'home' ? (int) $match->away_sets : (int) $match->home_sets;

                if ($sets !== []) {
                    $mine = fn (array $set) => $set[$index] > $set[1 - $index];
                    $setsLost = count(array_filter($sets, fn (array $set) => ! $mine($set)));
                    $row['scored_sets'] += count($sets);
                    $row['points'] += array_sum(array_column($sets, $index));
                    $row['points_lost'] += array_sum(array_column($sets, 1 - $index));
                    $row['comebacks'] += (int) ($won && ! $mine($sets[0]));
                    // Powrót z 0:2 w meczu do trzech wygranych setów.
                    $row['big_comebacks'] += (int) ($won && count($sets) >= 4 && ! $mine($sets[0]) && ! $mine($sets[1]));
                    $row['sweeps'] += (int) ($won && $setsLost === 0);
                    $row['tiebreaks_played'] += (int) $tiebreak;
                    $row['tiebreaks'] += (int) ($tiebreak && $mine(end($sets)));
                    $row['deuce'] += count(array_filter($sets, fn (array $set) => $mine($set) && self::isDeuce($set)));

                    foreach ($sets as $set) {
                        $runs[$key] = $mine($set) ? ($runs[$key] ?? 0) + 1 : 0;
                        $row['streak'] = max($row['streak'], $runs[$key]);
                    }
                } else {
                    $row['sweeps'] += (int) ($won && $row['sets_lost'] === 0 && ($side === 'home' ? $match->away_sets : $match->home_sets) === 0);
                }

                $rows[$key] = $row;
            }
        }

        return collect($rows)->map(fn (array $row) => $row + [
            'sets_played' => $row['sets_won'] + $row['sets_lost'],
            'points_diff' => $row['points'] - $row['points_lost'],
            'avg_lost' => $row['scored_sets'] > 0 && $row['matches'] >= 2 ? round($row['points_lost'] / $row['scored_sets'], 1) : null,
        ]);
    }

    /**
     * Podium każdej statystyki: do trzech miejsc (przy remisie to samo miejsce, a remisy na trzecim miejscu wchodzą wszystkie,
     * do pięciu drużyn). Statystyki bez wyniku (same zera) pomijamy.
     *
     * @param  list<string>|null  $only
     * @return Collection<string, array{label: string, help: string, rows: Collection<int, array{position: int, name: string, team: string, logo: ?string, own: bool, value: int|float, display: string}>}>
     */
    public function leaders(?array $only = null): Collection
    {
        $teams = $this->teams();
        $hasPoints = $this->games->contains(fn (array $game) => $game['sets'] !== []);

        return collect(self::LEADERS)
            ->filter(fn (array $stat, string $key) => $only === null || in_array($key, $only, true))
            ->reject(fn (array $stat, string $key) => ! $hasPoints && in_array($key, ['points', 'points_diff', 'avg_lost', 'comebacks', 'tiebreaks', 'deuce', 'streak'], true))
            ->map(function (array $stat, string $key) use ($teams) {
                $ascending = $key === 'avg_lost';
                $candidates = $teams->filter(fn (array $row) => $row[$key] !== null && ($ascending || $key === 'points_diff' || $row[$key] > 0))
                    ->sortBy([[$key, $ascending ? 'asc' : 'desc'], ['name', 'asc']])->values();

                $rows = collect();
                $position = 0;
                $previous = null;

                foreach ($candidates as $index => $row) {
                    if ($row[$key] !== $previous) {
                        $position = $index + 1;
                        $previous = $row[$key];
                    }

                    if ($position > 3 || $rows->count() >= 5) {
                        break;
                    }

                    $rows->push([
                        'position' => $position,
                        'name' => $row['name'],
                        'own' => $row['own'],
                        'logo' => $row['logo'],
                        'team' => $row['team'],
                        'value' => $row[$key],
                        'display' => self::display($key, $row),
                    ]);
                }

                return ['label' => __($stat[0]), 'help' => __($stat[1]), 'rows' => $rows];
            })
            ->filter(fn (array $stat) => $stat['rows']->isNotEmpty());
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private static function display(string $key, array $row): string
    {
        return match ($key) {
            'points_diff' => ($row[$key] > 0 ? '+' : '').$row[$key],
            'avg_lost' => number_format((float) $row[$key], 1, ',', ''),
            'tiebreaks' => $row['tiebreaks'].'/'.$row['tiebreaks_played'],
            default => (string) $row[$key],
        };
    }

    /**
     * Rekordy: najwyższa wygrana w secie, najdłuższy set, najbardziej wyrównany mecz i mecz z największą liczbą punktów.
     *
     * @return Collection<int, array{label: string, value: string, teams: list<array{name: string, logo: ?string, own: bool}>, match: string}>
     */
    public function records(): Collection
    {
        $sets = $this->games->flatMap(fn (array $game) => collect($game['sets'])->map(fn (array $set, int $index) => ['game' => $game, 'set' => $set, 'number' => $index + 1]));
        $scored = $this->games->filter(fn (array $game) => $game['sets'] !== []);

        $title = fn (array $game) => $this->withCategory ? $game['category']->name.' · ' : '';
        $points = fn (array $game) => array_sum(array_merge(...$game['sets']));
        $gap = fn (array $game) => abs(array_sum(array_column($game['sets'], 0)) - array_sum(array_column($game['sets'], 1)));

        $biggest = $sets->sortByDesc(fn (array $entry) => [abs($entry['set'][0] - $entry['set'][1]), -$entry['game']['match']->id])->first();
        $longest = $sets->sortByDesc(fn (array $entry) => [array_sum($entry['set']), -$entry['game']['match']->id])->first();
        $closest = $scored->sortBy(fn (array $game) => [$gap($game), -$points($game), $game['match']->id])->first();
        $most = $scored->sortByDesc(fn (array $game) => [$points($game), -$game['match']->id])->first();

        return collect([
            $biggest ? ['label' => __('Najwyższa wygrana w secie'), 'value' => implode(':', $biggest['set']), 'teams' => self::sides($biggest['game']), 'match' => $title($biggest['game']).__(':number. set', ['number' => $biggest['number']])] : null,
            $longest ? ['label' => __('Najdłuższy set'), 'value' => implode(':', $longest['set']), 'teams' => self::sides($longest['game']), 'match' => $title($longest['game']).__(':number. set', ['number' => $longest['number']])] : null,
            $closest ? ['label' => __('Najbardziej wyrównany mecz'), 'value' => trans_choice(':count punkt różnicy|:count punkty różnicy|:count punktów różnicy', $gap($closest)), 'teams' => self::sides($closest), 'match' => $title($closest).$closest['match']->home_sets.':'.$closest['match']->away_sets] : null,
            $most ? ['label' => __('Najwięcej punktów w meczu'), 'value' => (string) $points($most), 'teams' => self::sides($most), 'match' => $title($most).$most['match']->home_sets.':'.$most['match']->away_sets] : null,
        ])->filter()->values();
    }

    /**
     * @param  array{home: TournamentParticipant, away: TournamentParticipant}  $game
     * @return list<array{name: string, logo: ?string, own: bool}>
     */
    private static function sides(array $game): array
    {
        return array_map(fn (TournamentParticipant $participant) => ['name' => $participant->name(), 'logo' => $participant->logoPath(), 'own' => $participant->isOwnTeam()], [$game['home'], $game['away']]);
    }

    /**
     * Liczby zakresu: mecze, sety, punkty, tie-breaki, sety na przewagi, odwrócone mecze.
     *
     * @return array{matches: int, sets: int, points: int, avg_points: float|null, tiebreaks: int, deuce: int, comebacks: int, big_comebacks: int, sweeps: int, teams: int}
     */
    public function totals(): array
    {
        $scored = $this->games->filter(fn (array $game) => $game['sets'] !== []);
        $setCount = $scored->sum(fn (array $game) => count($game['sets']));
        $points = $scored->sum(fn (array $game) => array_sum(array_merge(...$game['sets'])));
        $teams = $this->teams();

        return [
            'matches' => $this->games->count(),
            'sets' => (int) $this->games->sum(fn (array $game) => $game['match']->home_sets + $game['match']->away_sets),
            'points' => (int) $points,
            'avg_points' => $setCount > 0 ? round($points / $setCount, 1) : null,
            'tiebreaks' => $scored->filter(fn (array $game) => self::hasTiebreak($game['sets']))->count(),
            'deuce' => (int) $scored->sum(fn (array $game) => count(array_filter($game['sets'], fn (array $set) => self::isDeuce($set)))),
            'comebacks' => (int) $teams->sum('comebacks'),
            'big_comebacks' => (int) $teams->sum('big_comebacks'),
            'sweeps' => (int) $teams->sum('sweeps'),
            'teams' => $teams->count(),
        ];
    }
}
