<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Team;
use App\Support\SearchIndex;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
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
    /**
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>, errors: array<int, string>}
     */
    public function sync(Competition $competition): array
    {
        $rows = collect();
        $errors = [];

        foreach ($competition->sourceUrls() as $url) {
            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; JokerPilaBot/1.0; +'.config('app.url').')'])
                    ->get($url)
                    ->throw();

                $rows = $rows->merge($this->parse($this->toUtf8($response->body(), (string) $response->header('Content-Type'))));
            } catch (Throwable $exception) {
                $errors[] = 'Nie udało się pobrać '.$url.' ('.class_basename($exception).')';
            }
        }

        $result = $this->import($competition, $rows) + ['errors' => $errors];

        $competition->forceFill([
            'synced_at' => now(),
            'sync_message' => $this->summary($result),
        ])->save();

        return $result;
    }

    /**
     * Zapisuje mecze z już odczytanych wierszy (osobno, by dało się to testować bez sieci).
     *
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{created: int, updated: int, rows: int, unmatched: array<int, string>}
     */
    public function import(Competition $competition, Collection $rows): array
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

        if ($finishedChanged) {
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
     * @return Collection<int, array{external_id: string, date: string, time: ?string, home: string, away: string, home_score: ?int, away_score: ?int, sets: array<int, array{0: int, 1: int}>, score_known: bool}>
     */
    public function parse(string $html): Collection
    {
        if (trim($html) === '') {
            return collect();
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $rows = collect();

        foreach ($xpath->query('//tr[.//a[contains(@href, "match_id=")]]') as $tableRow) {
            /** @var DOMElement $tableRow */
            $link = $xpath->query('.//a[contains(@href, "match_id=")]', $tableRow)->item(0);

            if (! preg_match('/match_id=(\d+)/', (string) $link?->getAttribute('href'), $idMatch)) {
                continue;
            }

            $cells = [];
            foreach ($xpath->query('./td|./th', $tableRow) as $cell) {
                $cells[] = trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', $cell->textContent)));
            }

            $row = $this->parseCells($cells);

            if ($row) {
                $rows->push(['external_id' => $idMatch[1]] + $row);
            }
        }

        return $rows;
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
                    $sets = array_map(fn (array $set) => [(int) $set[1], (int) $set[2]], $setMatches);
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
        return Game::where('competition_id', $competition->id)->where('external_id', $row['external_id'])->first()
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

        if ($result['errors']) {
            $parts[] = implode(' ', $result['errors']);
        }

        return mb_substr(implode(' ', $parts), 0, 500);
    }
}
