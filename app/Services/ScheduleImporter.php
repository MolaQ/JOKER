<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Support\SearchIndex;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Import terminarza i wyników ze stron związku (np. https://wzps.poznan.pl/obsady/).
 *
 * Czyta każdy wiersz tabeli z linkiem do meczu (match_id=…): datę, opcjonalnie godzinę,
 * gospodarza, gościa i wynik z setami. Mecze z udziałem naszych drużyn trafiają do
 * zakładki Mecze. Nasza drużyna może w różnych rozgrywkach występować pod inną nazwą
 * (np. „LOMS Joker Piła II”), dlatego nazwę z danych rozgrywek ustawia się przy drużynie.
 */
class ScheduleImporter
{
    /** Ile stron szczegółów meczu (hala, godzina, wynik) pobrać przy jednej synchronizacji. */
    public const DETAILS_PER_SYNC = 30;

    /**
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>, errors: array<int, string>, tables: int}
     */
    public function sync(Competition $competition): array
    {
        $resultRows = collect();
        $listRows = collect();
        $tables = collect();
        $errors = [];

        foreach ($competition->sourceUrls() as $url) {
            $html = $this->fetch($url, $errors);

            if ($html === null) {
                continue;
            }

            $page = $this->parsePage($html, $url);
            $tables = $tables->merge($page['tables']);

            // Strona z wynikami (tabele rozgrywek) wyznacza, które mecze należą do rozgrywek.
            // Strony drużyn i listy meczów tylko uzupełniają godziny.
            $page['matches']->contains('score_known', true)
                ? $resultRows = $resultRows->merge($page['matches'])
                : $listRows = $listRows->merge($page['matches']);
        }

        $rows = match (true) {
            $resultRows->isNotEmpty() => $resultRows->merge($listRows->whereIn('external_id', $resultRows->pluck('external_id')->unique()->all())),
            // Nie wiadomo, które mecze są nasze (np. strona z wynikami się nie pobrała) - tylko uzupełniamy znane.
            $errors !== [] => $listRows->whereIn('external_id', $competition->games()->whereNotNull('external_id')->pluck('external_id')->all()),
            default => $this->withoutMatchesOfOtherCompetitions($competition, $listRows),
        };

        $standingsImported = $this->importStandings($competition, $tables);
        $result = $this->import($competition, $rows, recalculateStandings: ! $standingsImported) + ['errors' => $errors];
        $this->importDetails($competition, $rows, $errors);

        $result['errors'] = $errors;
        $result['tables'] = $standingsImported;

        $competition->forceFill([
            'synced_at' => now(),
            'sync_message' => $this->summary($result),
        ])->save();

        return $result;
    }

    /**
     * Rozgrywki bez strony z wynikami (np. liga seniorów widoczna tylko na stronie drużyny)
     * biorą mecze, których nie ma w innych rozgrywkach.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function withoutMatchesOfOtherCompetitions(Competition $competition, Collection $rows): Collection
    {
        $claimed = Game::whereIn('external_id', $rows->pluck('external_id')->unique()->all())
            ->whereNotNull('competition_id')
            ->where('competition_id', '!=', $competition->id)
            ->pluck('external_id')
            ->all();

        return $rows->reject(fn (array $row) => in_array($row['external_id'], $claimed, true))->values();
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function fetch(string $url, array &$errors): ?string
    {
        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; JokerPilaBot/1.0; +'.config('app.url').')'])
                ->get($url)
                ->throw();

            return $this->toUtf8($response->body(), (string) $response->header('Content-Type'));
        } catch (Throwable $exception) {
            $errors[] = 'Nie udało się pobrać '.$url.' ('.class_basename($exception).')';

            return null;
        }
    }

    /**
     * Zapisuje mecze z już odczytanych wierszy (osobno, by dało się to testować bez sieci).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>}
     */
    public function import(Competition $competition, Collection $rows, bool $recalculateStandings = true): array
    {
        $ourTeams = $this->ourTeamsByName($competition);
        $created = 0;
        $updated = 0;
        $unmatched = [];
        $finishedChanged = false;

        // Ten sam mecz może być na kilku stronach (np. tabela z wynikami i strona drużyny z godziną).
        $matches = $rows->groupBy('external_id')->map(fn (Collection $versions) => $this->mergeVersions($versions));

        foreach ($matches as $row) {
            $homeTeam = $ourTeams[self::key($row['home'])] ?? null;
            $awayTeam = $ourTeams[self::key($row['away'])] ?? null;

            foreach ([$row['home'], $row['away']] as $name) {
                if (! isset($ourTeams[self::key($name)]) && str_contains(self::key($name), 'joker')) {
                    $unmatched[$name] = $name;
                }
            }

            if (! $homeTeam && ! $awayTeam) {
                continue;
            }

            $isHome = (bool) $homeTeam;
            $ourTeam = $homeTeam ?? $awayTeam;
            $opponentName = $isHome ? $row['away'] : $row['home'];
            $opponentIsOurs = $homeTeam && $awayTeam;

            $game = $this->findGame($competition, $row, $ourTeam->id);
            $wasNew = ! $game->exists;
            $wasFinished = $game->status === 'finished';

            $game->fill([
                'team_id' => $ourTeam->id,
                'season_id' => $competition->season_id,
                'competition_id' => $competition->id,
                'external_id' => $row['external_id'],
                'opponent' => $opponentName,
                'opponent_team_id' => $opponentIsOurs ? null : $this->rivalId($opponentName),
                'is_home' => $isHome,
                'game_date' => $this->gameDate($row, $game),
                'league' => $competition->name,
            ]);

            // Strona drużyny nie podaje wyników - wtedy nie ruszamy wyniku zapisanego wcześniej.
            if ($row['score_known']) {
                $game->fill([
                    'status' => $row['home_score'] !== null ? 'finished' : 'scheduled',
                    'home_score' => $row['home_score'],
                    'away_score' => $row['away_score'],
                    'sets_score' => $row['sets'] ?: null,
                    'home_points' => $row['sets'] ? array_sum(array_column($row['sets'], 0)) : null,
                    'away_points' => $row['sets'] ? array_sum(array_column($row['sets'], 1)) : null,
                ]);
            } elseif (! $game->exists) {
                $game->status = 'scheduled';
            }

            if ($game->isDirty()) {
                $finishedChanged = $finishedChanged || $game->status === 'finished' || $wasFinished;
                $game->save();
                $wasNew ? $created++ : $updated++;
            }
        }

        if ($finishedChanged && $recalculateStandings) {
            StandingsCalculator::recalculate($competition);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'rows' => $matches->count(),
            'unmatched' => array_values($unmatched),
        ];
    }

    /**
     * Odczytuje mecze z HTML strony związku.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function parse(string $html): Collection
    {
        return $this->parsePage($html)['matches'];
    }

    /**
     * Czyta stronę w kolejności dokumentu: nagłówki „Etap N” i „Grupa N”, wiersze tabel
     * (Lp. | Zespół | Mecze | Pkt | Zw. | Sety + | Sety - | pkt + | pkt -) oraz mecze.
     *
     * @return array{matches: Collection<int, array<string, mixed>>, tables: Collection<int, array{stage: ?string, group: ?string, rows: array<int, array<string, mixed>>}>}
     */
    public function parsePage(string $html, string $url = ''): array
    {
        $matches = collect();
        $tables = [];

        if (trim($html) === '') {
            return ['matches' => $matches, 'tables' => collect()];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $stage = null;
        $group = null;

        foreach ($xpath->query('//text()[normalize-space()] | //tr') as $node) {
            if (! $node instanceof DOMElement) {
                $text = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $node->textContent)));

                if (preg_match('/^Etap\s+(\S+)$/iu', $text, $m)) {
                    [$stage, $group] = [$m[1], null];
                } elseif (preg_match('/^Grupa\s+(\S+)$/iu', $text, $m)) {
                    $group = 'Grupa '.$m[1];
                }

                continue;
            }

            $cells = [];
            foreach ($xpath->query('./td|./th', $node) as $cell) {
                $cells[] = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $cell->textContent)));
            }

            $link = $xpath->query('.//a[contains(@href, "match_id=")]', $node)->item(0);

            if ($link instanceof DOMElement) {
                $href = $link->getAttribute('href');
                $row = preg_match('/match_id=(\d+)/', $href, $idMatch) ? $this->parseCells($cells) : null;

                if ($row) {
                    $matches->push(['external_id' => $idMatch[1]] + $row + ['detail_url' => $this->absoluteUrl($href, $url)]);
                }

                continue;
            }

            $standing = $this->parseStandingCells($cells);

            if ($standing) {
                $tables[($stage ?? '').'|'.($group ?? '')] ??= ['stage' => $stage, 'group' => $group, 'rows' => []];
                $tables[($stage ?? '').'|'.($group ?? '')]['rows'][] = $standing;
            }
        }

        return ['matches' => $matches, 'tables' => collect(array_values($tables))];
    }

    /**
     * @param  array<int, string>  $cells
     * @return array<string, mixed>|null
     */
    private function parseStandingCells(array $cells): ?array
    {
        if (count($cells) !== 9 || ! ctype_digit($cells[0]) || $cells[1] === '') {
            return null;
        }

        foreach (array_slice($cells, 2) as $value) {
            if (! preg_match('/^-?\d+$/', $value)) {
                return null;
            }
        }

        [$position, $name, $played, $points, $won, $setsWon, $setsLost, $pointsFor, $pointsAgainst] = $cells;

        return [
            'position' => (int) $position,
            'name' => $name,
            'played' => (int) $played,
            'points' => (int) $points,
            'won' => (int) $won,
            'lost' => max(0, (int) $played - (int) $won),
            'sets_won' => (int) $setsWon,
            'sets_lost' => (int) $setsLost,
            'points_for' => (int) $pointsFor,
            'points_against' => (int) $pointsAgainst,
        ];
    }

    private function absoluteUrl(string $href, string $base): ?string
    {
        if (preg_match('#^https?://#i', $href)) {
            return $href;
        }

        if (! preg_match('#^(https?://[^?\#]*/)#i', $base, $m)) {
            return null;
        }

        return $m[1].ltrim($href, '/');
    }

    /**
     * Zapisuje tabele z najnowszego etapu, w którym grają nasze drużyny (wszystkie ich grupy).
     *
     * @param  Collection<int, array{stage: ?string, group: ?string, rows: array<int, array<string, mixed>>}>  $tables
     */
    private function importStandings(Competition $competition, Collection $tables): bool
    {
        $ourTeams = $this->ourTeamsByName($competition);

        // Ta sama tabela może być na kilku pobranych stronach.
        $tables = $tables->unique(fn (array $table) => $table['stage'].'|'.$table['group']);

        $ours = $tables->filter(fn (array $table) => collect($table['rows'])->contains(fn (array $row) => isset($ourTeams[self::key($row['name'])])));

        if ($ours->isEmpty()) {
            return false;
        }

        $latestStage = $ours->pluck('stage')->sortByDesc(fn (?string $stage) => (int) $stage)->first();
        $selected = $ours->where('stage', $latestStage)->values();

        DB::transaction(function () use ($competition, $selected, $ourTeams) {
            LeagueStanding::where('competition_id', $competition->id)->delete();
            $saved = [];

            foreach ($selected as $table) {
                foreach ($table['rows'] as $row) {
                    if (isset($saved[self::key($row['name'])])) {
                        continue;
                    }

                    $saved[self::key($row['name'])] = true;
                    $team = $ourTeams[self::key($row['name'])] ?? null;

                    LeagueStanding::create(Arr::except($row, ['name']) + [
                        'competition_id' => $competition->id,
                        'season_id' => $competition->season_id,
                        'group_name' => $selected->count() > 1 ? $table['group'] : null,
                        'competitor_type' => $team ? 'team' : 'rival_team',
                        'competitor_id' => $team ? $team->id : $this->rivalId($row['name']),
                        'team_id' => $team?->id,
                        // Tabela pochodzi ze związku - automatyczne przeliczanie jej nie zmienia.
                        'is_manual_override' => true,
                    ]);
                }
            }
        });

        return true;
    }

    /**
     * Strona meczu podaje halę i godzinę (oraz wynik, gdy nie ma go na liście).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $errors
     */
    private function importDetails(Competition $competition, Collection $rows, array &$errors): void
    {
        $urls = $rows->whereNotNull('detail_url')->pluck('detail_url', 'external_id');

        $games = Game::where('competition_id', $competition->id)
            ->whereIn('external_id', $urls->keys()->all())
            ->where(fn ($query) => $query
                ->whereNull('venue')
                ->orWhere('venue', '')
                ->orWhere(fn ($query) => $query->where('status', '!=', 'finished')->where('game_date', '<', now()))
                ->orWhere(fn ($query) => $query->whereTime('game_date', '00:00:00')->where('game_date', '>=', today())))
            ->orderBy('game_date')
            ->limit(self::DETAILS_PER_SYNC)
            ->get();

        foreach ($games as $game) {
            $html = $this->fetch($urls[$game->external_id], $errors);
            $details = $html === null ? null : $this->parseDetails($html);

            if (! $details) {
                continue;
            }

            if ($details['venue'] && blank($game->venue)) {
                $game->venue = $details['venue'];
            }

            if ($details['time'] && $game->game_date->format('H:i') === '00:00') {
                $game->game_date = $game->game_date->copy()->setTimeFromTimeString($details['time']);
            }

            if ($details['home_score'] !== null && $game->status !== 'finished') {
                $game->fill([
                    'status' => 'finished',
                    'home_score' => $details['home_score'],
                    'away_score' => $details['away_score'],
                    'sets_score' => $details['sets'] ?: null,
                    'home_points' => $details['sets'] ? array_sum(array_column($details['sets'], 0)) : null,
                    'away_points' => $details['sets'] ? array_sum(array_column($details['sets'], 1)) : null,
                ]);
            }

            $game->save();
        }
    }

    /**
     * @return array{venue: ?string, time: ?string, home_score: ?int, away_score: ?int, sets: array<int, array{0: int, 1: int}>}|null
     */
    public function parseDetails(string $html): ?array
    {
        if (trim($html) === '') {
            return null;
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $lines = [];
        foreach ((new DOMXPath($document))->query('//body//text()[normalize-space()]') as $node) {
            $lines[] = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $node->textContent)));
        }

        $text = implode("\n", $lines);

        if (! str_contains($text, 'Rozgrywany dnia')) {
            return null;
        }

        $venue = preg_match('/Mecz rozgrywany w:?\s*\n?([^\n]+)/u', $text, $m) ? trim($m[1]) : null;
        $time = preg_match('/godzina meczu:?\s*\n?\s*(\d{1,2}:\d{2})/u', $text, $m) ? sprintf('%05s', $m[1]) : null;
        $score = ['home_score' => null, 'away_score' => null, 'sets' => []];

        if (preg_match('/wynik:?\s*\n?\s*(\d)\s*:\s*(\d)(?:\s*\(([^)]*)\))?/u', $text, $m) && (int) $m[1] + (int) $m[2] > 0) {
            preg_match_all('/(\d+)\s*:\s*(\d+)/', $m[3] ?? '', $setMatches, PREG_SET_ORDER);
            $score = [
                'home_score' => (int) $m[1],
                'away_score' => (int) $m[2],
                'sets' => $this->playedSets($setMatches),
            ];
        }

        return ['venue' => $venue ?: null, 'time' => $time] + $score;
    }

    /**
     * Sety „0:0” to puste miejsca w formularzu związku (np. młodzicy grają do dwóch wygranych).
     *
     * @param  array<int, array<int, string>>  $setMatches
     * @return array<int, array{0: int, 1: int}>
     */
    private function playedSets(array $setMatches): array
    {
        return array_values(array_filter(
            array_map(fn (array $set) => [(int) $set[1], (int) $set[2]], $setMatches),
            fn (array $set) => $set[0] + $set[1] > 0,
        ));
    }

    /**
     * @param  array<int, string>  $cells
     * @return array<string, mixed>|null
     */
    private function parseCells(array $cells): ?array
    {
        $dateIndex = null;
        $date = null;

        foreach ($cells as $index => $text) {
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $text, $m)) {
                [$dateIndex, $date] = [$index, "{$m[1]}-{$m[2]}-{$m[3]}"];
                break;
            }
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $text, $m)) {
                [$dateIndex, $date] = [$index, sprintf('%s-%02d-%02d', $m[3], $m[2], $m[1])];
                break;
            }
        }

        if ($dateIndex === null) {
            return null;
        }

        $separatorIndex = null;
        foreach ($cells as $index => $text) {
            if ($index > $dateIndex && in_array($text, ['-', '–', '—', 'vs', ':'], true)) {
                $separatorIndex = $index;
                break;
            }
        }

        if ($separatorIndex !== null && $separatorIndex - 1 > $dateIndex && isset($cells[$separatorIndex + 1])) {
            $home = $cells[$separatorIndex - 1];
            $away = $cells[$separatorIndex + 1];
            $afterTeams = array_slice($cells, $separatorIndex + 2);
            $betweenDateAndTeams = array_slice($cells, $dateIndex + 1, $separatorIndex - $dateIndex - 2);
        } else {
            // Gospodarz i gość w jednej komórce: „Drużyna A - Drużyna B”.
            $teamsIndex = collect($cells)->search(fn (string $text, int $index) => $index > $dateIndex && preg_match('/\S\s+[-–]\s+\S/u', $text));

            if ($teamsIndex === false) {
                return null;
            }

            [$home, $away] = array_map('trim', preg_split('/\s+[-–]\s+/u', $cells[$teamsIndex], 2));
            $afterTeams = array_slice($cells, $teamsIndex + 1);
            $betweenDateAndTeams = array_slice($cells, $dateIndex + 1, $teamsIndex - $dateIndex - 1);
        }

        $time = collect($betweenDateAndTeams)->first(fn (string $text) => preg_match('/^\d{1,2}:\d{2}$/', $text));

        $homeScore = null;
        $awayScore = null;
        $sets = [];
        $scoreKnown = false;

        foreach ($afterTeams as $text) {
            if (preg_match('/^(\d)\s*:\s*(\d)(?:\s*\((.*)\))?/u', $text, $m)) {
                // Strona z wynikami: 0:0 oznacza mecz jeszcze nierozegrany.
                $scoreKnown = true;

                if ((int) $m[1] + (int) $m[2] > 0) {
                    $homeScore = (int) $m[1];
                    $awayScore = (int) $m[2];
                    preg_match_all('/(\d+)\s*:\s*(\d+)/', $m[3] ?? '', $setMatches, PREG_SET_ORDER);
                    $sets = $this->playedSets($setMatches);
                }
                break;
            }
        }

        if ($home === '' || $away === '') {
            return null;
        }

        return [
            'date' => $date,
            'time' => $time ? sprintf('%05s', $time) : null,
            'home' => $home,
            'away' => $away,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'sets' => $sets,
            'score_known' => $scoreKnown,
        ];
    }

    /**
     * Klucz porównania nazw: bez wielkości liter, polskich znaków, spacji i kropek
     * („LOMS Joker PiłaII” = „LOMS Joker Piła II”).
     */
    public static function key(string $name): string
    {
        return preg_replace('/[^a-z0-9]/', '', SearchIndex::normalize($name));
    }

    /**
     * @return array<string, Team>
     */
    private function ourTeamsByName(Competition $competition): array
    {
        $teams = [];

        foreach ($competition->teams()->get() as $team) {
            $names = preg_split('/[;\n]+/', (string) $team->pivot->external_name) ?: [];
            $names[] = $team->name;

            foreach (array_filter(array_map('trim', $names)) as $name) {
                $teams[self::key($name)] = $team;
            }
        }

        return $teams;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $versions
     * @return array<string, mixed>
     */
    private function mergeVersions(Collection $versions): array
    {
        $merged = $versions->first();

        foreach ($versions->skip(1) as $version) {
            $merged['time'] ??= $version['time'];

            if (! $merged['score_known'] && $version['score_known']) {
                $merged['home_score'] = $version['home_score'];
                $merged['away_score'] = $version['away_score'];
                $merged['sets'] = $version['sets'];
                $merged['score_known'] = true;
            }
        }

        return $merged;
    }

    /**
     * Istniejący mecz z importu albo wpisany wcześniej ręcznie (ta sama drużyna i dzień).
     *
     * @param  array<string, mixed>  $row
     */
    private function findGame(Competition $competition, array $row, int $teamId): Game
    {
        return Game::where('team_id', $teamId)->where('external_id', $row['external_id'])->first()
            ?? Game::where('competition_id', $competition->id)->where('external_id', $row['external_id'])->first()
            ?? Game::whereNull('external_id')
                ->where('team_id', $teamId)
                ->where(fn ($query) => $query->whereNull('competition_id')->orWhere('competition_id', $competition->id))
                ->whereDate('game_date', $row['date'])
                ->first()
            ?? new Game;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function gameDate(array $row, Game $game): Carbon
    {
        $time = $row['time'];

        // Godzina wpisana ręcznie zostaje, dopóki związek jej nie poda.
        if (! $time && $game->game_date && $game->game_date->toDateString() === $row['date']) {
            $time = $game->game_date->format('H:i');
        }

        return Carbon::parse($row['date'].' '.($time ?? '00:00'));
    }

    private function rivalId(string $name): int
    {
        $existing = RivalTeam::all(['id', 'full_name', 'short_name'])
            ->first(fn (RivalTeam $rival) => self::key($rival->full_name) === self::key($name));

        return $existing->id ?? RivalTeam::create(['full_name' => $name])->id;
    }

    private function toUtf8(string $body, string $contentType): string
    {
        if (preg_match('/charset=([\w-]+)/i', $contentType.' '.substr($body, 0, 2000), $m) && ! preg_match('/utf-?8/i', $m[1])) {
            return mb_convert_encoding($body, 'UTF-8', $m[1] === 'windows-1250' ? 'Windows-1250' : 'ISO-8859-2');
        }

        return mb_check_encoding($body, 'UTF-8') ? $body : mb_convert_encoding($body, 'UTF-8', 'ISO-8859-2');
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function summary(array $result): string
    {
        $parts = ["Nowe mecze: {$result['created']}, zaktualizowane: {$result['updated']}."];

        if ($result['unmatched']) {
            $parts[] = 'Nieprzypisane nazwy: '.implode(', ', $result['unmatched']).'.';
        }

        if ($result['tables'] ?? false) {
            $parts[] = 'Tabela pobrana ze związku.';
        }

        if ($result['errors']) {
            $parts[] = implode(' ', $result['errors']);
        }

        return mb_substr(implode(' ', $parts), 0, 500);
    }
}
