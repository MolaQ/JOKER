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
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>, errors: array<int, string>, tables: bool}
     */
    public function sync(Competition $competition): array
    {
        $resultRows = collect();
        $listRows = collect();
        $siteRows = collect();
        $wzpsTables = collect();
        $siteTables = collect();
        $errors = [];

        foreach ($competition->sourceUrls() as $url) {
            // wielkopolskasiatkowka.com.pl: wyniki i tabele (pierwszeństwo przed WZPS), bez godzin i hal.
            if (WielkopolskaSiatkowkaParser::handles($url)) {
                $body = $this->fetch(WielkopolskaSiatkowkaParser::apiUrl($url) ?? $url, $errors);

                if ($body !== null) {
                    $page = app(WielkopolskaSiatkowkaParser::class)->parse(WielkopolskaSiatkowkaParser::contentFrom($body));
                    $siteRows = $siteRows->merge($page['matches']);
                    $siteTables = $siteTables->merge($page['tables']);
                }

                continue;
            }

            $html = $this->fetch($url, $errors);

            if ($html === null) {
                continue;
            }

            $page = $this->parsePage($html, $url);
            $wzpsTables = $wzpsTables->merge($page['tables']);

            // Strona z wynikami (tabele rozgrywek) wyznacza, które mecze należą do rozgrywek.
            // Strony drużyn i listy meczów tylko uzupełniają godziny i hale.
            $page['matches']->contains('score_known', true)
                ? $resultRows = $resultRows->merge($page['matches'])
                : $listRows = $listRows->merge($page['matches']);
        }

        $rows = match (true) {
            $resultRows->isNotEmpty() => $resultRows->merge($listRows->whereIn('external_id', $resultRows->pluck('external_id')->unique()->all())),
            // Mecze wyznacza strona wielkopolskasiatkowka, a WZPS tylko je uzupełnia (dopasowanie po dacie i rywalu).
            $siteRows->isNotEmpty() => collect(),
            // Nie wiadomo, które mecze są nasze (np. strona z wynikami się nie pobrała) - tylko uzupełniamy znane.
            $errors !== [] => $listRows->whereIn('external_id', $competition->games()->whereNotNull('external_id')->pluck('external_id')->all()),
            default => $this->withoutMatchesOfOtherCompetitions($competition, $listRows),
        };

        $standingsImported = $this->importStandings($competition, $siteTables)
            || $this->importStandings($competition, $wzpsTables);

        $result = $this->import($competition, $rows, $siteRows, $listRows, recalculateStandings: ! $standingsImported);
        $this->importDetails($competition, $rows->merge($listRows), $errors);

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
     * Wiersze WZPS mają numer meczu; wiersze z wielkopolskasiatkowka.com.pl ($siteRows) go nie mają,
     * więc łączymy je z meczami WZPS po drużynach i dacie (±21 dni - mecze bywają przekładane).
     * Wynik: ręczny z panelu > wielkopolskasiatkowka > WZPS.
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @param  Collection<int, array<string, mixed>>|null  $siteRows
     * @param  Collection<int, array<string, mixed>>|null  $listRows
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>}
     */
    public function import(Competition $competition, Collection $rows, ?Collection $siteRows = null, ?Collection $listRows = null, bool $recalculateStandings = true): array
    {
        $ourTeams = $this->ourTeamsByName($competition);
        $created = 0;
        $updated = 0;
        $unmatched = [];
        $finishedChanged = false;

        $resolve = function (array $row, bool $report = true) use ($ourTeams, &$unmatched): array {
            foreach ($report ? [$row['home'], $row['away']] : [] as $name) {
                if (! isset($ourTeams[self::key($name)]) && str_contains(self::key($name), 'joker')) {
                    $unmatched[$name] = $name;
                }
            }

            return $row + ['home_team' => $ourTeams[self::key($row['home'])] ?? null, 'away_team' => $ourTeams[self::key($row['away'])] ?? null];
        };
        $isOurs = fn (array $row) => $row['home_team'] || $row['away_team'];

        // Ten sam mecz może być na kilku stronach (np. tabela z wynikami i strona drużyny z godziną).
        $matches = $rows->groupBy('external_id')
            ->map(fn (Collection $versions) => $resolve($this->mergeVersions($versions)))
            ->filter($isOurs)
            ->values()
            ->all();

        // Strony drużyn w WZPS mają też mecze innych rozgrywek - nie zgłaszamy z nich nieznanych nazw.
        $listCandidates = ($listRows ?? collect())->groupBy('external_id')
            ->map(fn (Collection $versions) => $resolve($this->mergeVersions($versions), false))
            ->filter($isOurs)
            ->values()
            ->all();

        foreach (($siteRows ?? collect())->map(fn (array $row) => $resolve($row))->filter($isOurs) as $siteRow) {
            $index = $this->pairIndex($siteRow, $matches);

            if ($index !== null) {
                $matches[$index]['site'] = $siteRow;

                continue;
            }

            // Mecz tylko na wielkopolskasiatkowka - numer, godzinę i halę bierzemy z listy WZPS, jeśli tam jest.
            $listIndex = $this->pairIndex($siteRow, $listCandidates);
            $base = $listIndex !== null ? $listCandidates[$listIndex] : null;
            $listIndex !== null && $listCandidates[$listIndex]['paired'] = true;

            $matches[] = [
                'external_id' => $base['external_id'] ?? null,
                'date' => $base['date'] ?? $siteRow['date'],
                'time' => $base['time'] ?? null,
                'home' => $base['home'] ?? $siteRow['home'],
                'away' => $base['away'] ?? $siteRow['away'],
                'home_score' => $base['home_score'] ?? null,
                'away_score' => $base['away_score'] ?? null,
                'sets' => $base['sets'] ?? [],
                'score_known' => $base['score_known'] ?? false,
                'detail_url' => $base['detail_url'] ?? null,
                'home_team' => $siteRow['home_team'],
                'away_team' => $siteRow['away_team'],
                'site' => $siteRow,
            ];
        }

        foreach ($matches as $row) {
            $site = $row['site'] ?? null;
            $siteKey = $site ? 'ws-'.$competition->id.'-'.$site['source_key'] : null;
            $row['external_id'] ??= $siteKey;

            $homeTeam = $row['home_team'];
            $awayTeam = $row['away_team'];
            $isHome = (bool) $homeTeam;
            $ourTeam = $homeTeam ?? $awayTeam;
            $opponentName = $isHome ? $row['away'] : $row['home'];
            $opponentIsOurs = $homeTeam && $awayTeam;

            $game = $this->findGame($competition, $row, $ourTeam->id, $siteKey);
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
                'round_label' => $this->roundLabel($site ?? $row) ?? $game->round_label,
            ]);

            if ($game->result_source !== Game::RESULT_MANUAL) {
                if ($site && $site['home_score'] !== null) {
                    $this->fillScore($game, $site, Game::RESULT_SITE);
                    $game->result_note = $site['note'];
                } elseif ($row['home_score'] !== null && $game->result_source !== Game::RESULT_SITE) {
                    // WZPS wpisuje wynik, gdy na wielkopolskasiatkowka jeszcze go nie ma.
                    $this->fillScore($game, $row, Game::RESULT_WZPS);
                } elseif ($site && $site['note'] && $game->status !== 'finished') {
                    $game->result_note = $site['note'];
                }
            }

            if (! $game->exists && ! $game->status) {
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
            'rows' => count($matches),
            'unmatched' => array_values($unmatched),
        ];
    }

    /**
     * @param  array<string, mixed>  $source
     */
    private function fillScore(Game $game, array $source, string $origin): void
    {
        $game->fill([
            'status' => 'finished',
            'home_score' => $source['home_score'],
            'away_score' => $source['away_score'],
            'sets_score' => $source['sets'] ?: null,
            'home_points' => $source['sets'] ? array_sum(array_column($source['sets'], 0)) : null,
            'away_points' => $source['sets'] ? array_sum(array_column($source['sets'], 1)) : null,
            'result_source' => $origin,
        ]);
    }

    /**
     * Np. „Etap 2 · Grupa D · 1. kolejka”.
     *
     * @param  array<string, mixed>  $row
     */
    private function roundLabel(array $row): ?string
    {
        $label = implode(' · ', array_filter([$row['stage'] ?? null, $row['group'] ?? null, $row['round'] ?? null]));

        return $label !== '' ? mb_substr($label, 0, 120) : null;
    }

    /**
     * Ten sam mecz w drugim źródle: te same strony (nasza drużyna albo podobna nazwa rywala), data ±21 dni.
     *
     * @param  array<string, mixed>  $row
     * @param  array<int, array<string, mixed>>  $candidates
     */
    private function pairIndex(array $row, array $candidates): ?int
    {
        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $index => $candidate) {
            if (isset($candidate['site']) || ! empty($candidate['paired'])) {
                continue;
            }

            $days = abs(Carbon::parse($row['date'])->diffInDays(Carbon::parse($candidate['date'])));

            if ($days > 21) {
                continue;
            }

            $score = 0;
            foreach (['home', 'away'] as $side) {
                $ours = $row[$side.'_team'];
                $theirs = $candidate[$side.'_team'];

                if ($ours || $theirs) {
                    if (! $ours || ! $theirs || $ours->id !== $theirs->id) {
                        continue 2;
                    }
                    $score += 100;
                } else {
                    $similarity = self::similarity($row[$side], $candidate[$side]);

                    if ($similarity === 0) {
                        continue 2;
                    }
                    $score += $similarity;
                }
            }

            $score -= (int) $days * 3;

            if ($score > $bestScore) {
                [$best, $bestScore] = [$index, $score];
            }
        }

        return $best;
    }

    /**
     * Podobieństwo nazw rywali z dwóch źródeł, np. „LUKS Wilki Wilczyn”
     * i „Ogrobet Bejenka Budownictwo LUKS Wilki Wilczyn” (0 - różne drużyny).
     */
    public static function similarity(string $first, string $second): int
    {
        [$a, $b] = [self::key($first), self::key($second)];

        if ($a === $b) {
            return 100;
        }

        if (min(strlen($a), strlen($b)) >= 5 && (str_contains($a, $b) || str_contains($b, $a))) {
            return 80;
        }

        $generic = ['volley', 'siatkowka', 'klub', 'sport', 'sportowy', 'mlodziezowa', 'team'];
        $tokens = fn (string $name) => array_diff(
            array_filter(preg_split('/[^a-z0-9]+/', SearchIndex::normalize($name)), fn (string $token) => strlen($token) >= 4),
            $generic,
        );

        return min(60, count(array_intersect($tokens($first), $tokens($second))) * 20);
    }

    /**
     * Wynik w postaci „3:1 (25:18, 17:25, 25:19, 25:22)”; „0:0” to mecz jeszcze nierozegrany.
     *
     * @return array{home_score: ?int, away_score: ?int, sets: array<int, array{0: int, 1: int}>, score_known: bool}
     */
    public static function parseScore(string $text): array
    {
        if (! preg_match('/(?:^|\s)(\d)\s*:\s*(\d)(?:\s*\(([^)]*)\))?/u', $text, $m)) {
            return ['home_score' => null, 'away_score' => null, 'sets' => [], 'score_known' => false];
        }

        if ((int) $m[1] + (int) $m[2] === 0) {
            return ['home_score' => null, 'away_score' => null, 'sets' => [], 'score_known' => true];
        }

        preg_match_all('/(\d+)\s*:\s*(\d+)/', $m[3] ?? '', $setMatches, PREG_SET_ORDER);

        return [
            'home_score' => (int) $m[1],
            'away_score' => (int) $m[2],
            'sets' => self::playedSets($setMatches),
            'score_known' => true,
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
                    $matches->push(['external_id' => $idMatch[1]] + $row + [
                        'detail_url' => $this->absoluteUrl($href, $url),
                        'stage' => $stage ? 'Etap '.$stage : null,
                        'group' => $group,
                    ]);
                }

                continue;
            }

            $standing = $this->parseStandingCells($cells);

            if ($standing) {
                $tables[($stage ?? '').'|'.($group ?? '')] ??= ['stage' => $stage ? 'Etap '.$stage : null, 'group' => $group, 'rows' => []];
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
     * Zapisuje tabele wszystkich etapów i grup danego źródła, jeśli występuje w nich któraś z naszych drużyn.
     *
     * @param  Collection<int, array{stage: ?string, group: ?string, rows: array<int, array<string, mixed>>}>  $tables
     */
    private function importStandings(Competition $competition, Collection $tables): bool
    {
        $ourTeams = $this->ourTeamsByName($competition);

        // Ta sama tabela może być na kilku pobranych stronach.
        $tables = $tables->unique(fn (array $table) => $table['stage'].'|'.$table['group'])->values();

        $hasOurTeam = $tables->contains(fn (array $table) => collect($table['rows'])->contains(fn (array $row) => isset($ourTeams[self::key($row['name'])])));

        if (! $hasOurTeam) {
            return false;
        }

        DB::transaction(function () use ($competition, $tables, $ourTeams) {
            LeagueStanding::where('competition_id', $competition->id)->delete();

            foreach ($tables as $table) {
                $saved = [];

                foreach ($table['rows'] as $row) {
                    if (isset($saved[self::key($row['name'])])) {
                        continue;
                    }

                    $saved[self::key($row['name'])] = true;
                    $team = $ourTeams[self::key($row['name'])] ?? null;

                    LeagueStanding::create(Arr::except($row, ['name']) + [
                        'competition_id' => $competition->id,
                        'season_id' => $competition->season_id,
                        'stage_name' => $table['stage'],
                        'group_name' => $table['group'],
                        'source_name' => $row['name'],
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
                'sets' => self::playedSets($setMatches),
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
    private static function playedSets(array $setMatches): array
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
                    $sets = self::playedSets($setMatches);
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
    private function findGame(Competition $competition, array $row, int $teamId, ?string $siteKey = null): Game
    {
        return Game::where('team_id', $teamId)->where('external_id', $row['external_id'])->first()
            ?? ($siteKey ? Game::where('team_id', $teamId)->where('external_id', $siteKey)->first() : null)
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
