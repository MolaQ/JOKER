<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Strony lig na wielkopolskasiatkowka.com.pl (WordPress): nagłówki „ETAP 2”, „Grupa D”,
 * „1. kolejka: 17.10.2026” (albo „26.09.2026 – przełożony na 27.09.2026”), po nich tabele
 * meczów (Nr meczu | gospodarz | gość | wynik) i tabele ligowe (Lp. | Zespół | Mecze | Pkt | …).
 * Nie ma tu godzin, hal ani numerów meczów z WZPS - te bierzemy z WZPS.
 */
class WielkopolskaSiatkowkaParser
{
    public const HOST = 'wielkopolskasiatkowka.com.pl';

    public static function handles(string $url): bool
    {
        return str_contains(parse_url($url, PHP_URL_HOST) ?? '', self::HOST);
    }

    /**
     * Treść strony pobieramy przez API WordPressa (czysty HTML bez menu i reklam).
     */
    public static function apiUrl(string $url): ?string
    {
        $slug = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($slug === '' || str_contains($slug, 'wp-json')) {
            return null;
        }

        return 'https://'.self::HOST.'/wp-json/wp/v2/pages?slug='.rawurlencode(basename($slug)).'&_fields=content,modified';
    }

    /**
     * Z odpowiedzi API wyciąga HTML strony; inną odpowiedź traktuje jako zwykły HTML.
     */
    public static function contentFrom(string $body): string
    {
        $json = json_decode($body, true);

        if (is_array($json) && isset($json[0]['content']['rendered'])) {
            return (string) $json[0]['content']['rendered'];
        }

        return $body;
    }

    /**
     * @return array{matches: Collection<int, array<string, mixed>>, tables: Collection<int, array{stage: ?string, group: ?string, rows: array<int, array<string, mixed>>}>}
     */
    public function parse(string $html): array
    {
        $matches = collect();
        $tables = collect();
        $withdrawn = [];

        if (trim($html) === '') {
            return ['matches' => $matches, 'tables' => $tables, 'withdrawn' => []];
        }

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>');
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $stage = null;
        $group = null;
        $date = null;
        $round = null;
        $occurrences = [];

        $blocks = $xpath->query('//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6 or self::p or self::table]');

        foreach ($blocks as $block) {
            /** @var DOMElement $block */
            if ($block->nodeName !== 'table') {
                $text = self::clean($block->textContent);

                if (($name = self::withdrawnTeam($text)) !== null) {
                    $withdrawn[ScheduleImporter::key($name)] = $name;
                }

                if ($text === '' || mb_strlen($text) > 120) {
                    continue;
                }

                if (preg_match('/^etap\s+(\S+)/iu', $text, $m)) {
                    [$stage, $group, $round] = ['Etap '.mb_strtoupper($m[1]), null, null];

                    continue;
                }

                if (preg_match('/^grupa\s+([^\s–-]+)/iu', $text, $m)) {
                    [$group, $round] = ['Grupa '.$m[1], null];
                }

                if (preg_match('/(\d+)\.\s*kolejka/iu', $text, $m)) {
                    $round = $m[1].'. kolejka';
                }

                $date = self::dateFrom($text) ?? $date;

                continue;
            }

            $rows = [];
            foreach ($xpath->query('.//tr', $block) as $tableRow) {
                $cells = [];
                foreach ($xpath->query('./td|./th', $tableRow) as $cell) {
                    $cells[] = self::clean($cell->textContent);

                    if (($name = self::withdrawnTeam(end($cells))) !== null) {
                        $withdrawn[ScheduleImporter::key($name)] = $name;
                    }
                }
                $rows[] = $cells;
            }

            if ($rows === []) {
                continue;
            }

            $header = array_map(fn (string $label) => self::label($label), $rows[0]);

            if (in_array('zespół', $header, true)) {
                $standingRows = $this->standingRows($header, array_slice($rows, 1));

                if ($standingRows !== []) {
                    $tables->push(['stage' => $stage, 'group' => $group, 'rows' => $standingRows]);
                }

                continue;
            }

            $columns = [
                'nr' => self::column($header, ['nr', 'nrmeczu']),
                'home' => self::column($header, ['gospodarz']),
                'away' => self::column($header, ['gość', 'gosc']),
                'result' => self::column($header, ['wynik']),
            ];

            if ($columns['home'] === null || $columns['away'] === null) {
                continue;
            }

            foreach (array_slice($rows, 1) as $cells) {
                $match = $this->matchRow($cells, $columns, $date);

                if (! $match) {
                    continue;
                }

                // Bez numeru meczu: stały klucz z etapu, grupy, drużyn i kolejności (ta sama para gra kilka razy).
                $pairKey = implode('|', [$stage, $group, ScheduleImporter::key($match['home']), ScheduleImporter::key($match['away'])]);
                $occurrences[$pairKey] = ($occurrences[$pairKey] ?? 0) + 1;

                $matches->push($match + [
                    'stage' => $stage,
                    'group' => $group,
                    'round' => $round,
                    'source_key' => $match['nr'] ? 'nr'.$match['nr'] : substr(md5($pairKey.'|'.$occurrences[$pairKey]), 0, 12),
                ]);
            }
        }

        return ['matches' => $matches, 'tables' => $tables, 'withdrawn' => array_values($withdrawn)];
    }

    /**
     * Nazwa drużyny z dopisku o wycofaniu, np. „NKS Czarnków – zespół wycofał się”,
     * „NKS Czarnków (wycofana)” albo „Drużyna NKS Czarnków wycofała się z rozgrywek”.
     */
    public static function withdrawnTeam(string $text): ?string
    {
        $patterns = [
            '/^(.{3,80}?)\s*[–—-]\s*(?:zespół|drużyna|klub)?\s*(?:wycofał[a]?\s+się|wycofan[ya]|wycofanie)/iu',
            '/^(.{3,80}?)\s*\(\s*(?:wycofał[a]?\s+się|wycofan[ya]|wycofanie)[^)]*\)/iu',
            '/(?:zespół|drużyna)\s+(.{3,80}?)\s+wycofał[a]?\s+się/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                return trim($m[1], " \t\n\r\0\x0B.,:;\"„”");
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $cells
     * @param  array<string, ?int>  $columns
     * @return array<string, mixed>|null
     */
    private function matchRow(array $cells, array $columns, ?string $date): ?array
    {
        $home = $cells[$columns['home']] ?? '';
        $away = $cells[$columns['away']] ?? '';
        $result = $columns['result'] !== null ? ($cells[$columns['result']] ?? '') : '';
        $nr = null;

        if ($home === '' || $away === '') {
            return null;
        }

        if ($columns['nr'] !== null && preg_match('/^(\d+)(?:\s*[–-]\s*(\d{1,2})\.(\d{1,2})(?:\.(\d{4}))?)?/u', $cells[$columns['nr']] ?? '', $m)) {
            $nr = $m[1];

            // „16 – 27.09”: mecz z innej kolejki rozegrany w podanym dniu.
            if (! empty($m[2])) {
                $date = self::dayInYearOf((int) $m[2], (int) $m[3], ! empty($m[4]) ? (int) $m[4] : null, $date);
            }
        }

        $date = self::dateFrom($result) ?? $date;

        if (! $date) {
            return null;
        }

        $score = ScheduleImporter::parseScore($result);
        $note = null;

        if (preg_match('/\b(vo|wo|walkower)\b/iu', $result)) {
            $note = 'walkower';
        } elseif (preg_match('/przełożon/iu', $result)) {
            $note = 'mecz przełożony';
        }

        return [
            'nr' => $nr,
            'date' => $date,
            'home' => $home,
            'away' => $away,
            'note' => $note,
        ] + $score;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function standingRows(array $header, array $rows): array
    {
        $map = [
            'position' => self::column($header, ['lp.', 'lp']),
            'name' => self::column($header, ['zespół']),
            'played' => self::column($header, ['mecze']),
            'points' => self::column($header, ['pkt']),
            'won' => self::column($header, ['zw.', 'zw']),
            'sets_won' => self::column($header, ['sety+']),
            'sets_lost' => self::column($header, ['sety-']),
            'points_for' => self::column($header, ['pkt+']),
            'points_against' => self::column($header, ['pkt-']),
        ];

        if (in_array(null, $map, true)) {
            return [];
        }

        $standings = [];

        foreach ($rows as $cells) {
            if (! ctype_digit($cells[$map['position']] ?? '') || ($cells[$map['name']] ?? '') === '') {
                continue;
            }

            $row = ['name' => $cells[$map['name']]];
            foreach (array_diff_key($map, ['name' => true]) as $key => $index) {
                $row[$key] = (int) ($cells[$index] ?? 0);
            }
            $row['lost'] = max(0, $row['played'] - $row['won']);

            $standings[] = $row;
        }

        return $standings;
    }

    /**
     * Data z tekstu; „przełożony na 27.09.2026” ma pierwszeństwo przed pierwotną datą.
     */
    private static function dateFrom(string $text): ?string
    {
        if (preg_match('/przełożon\w*\s+na\s+(\d{1,2})\.(\d{1,2})\.(\d{4})/iu', $text, $m)
            || preg_match('/(\d{1,2})\.(\d{1,2})\.(\d{4})/', $text, $m)) {
            return checkdate((int) $m[2], (int) $m[1], (int) $m[3]) ? sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]) : null;
        }

        return null;
    }

    private static function dayInYearOf(int $day, int $month, ?int $year, ?string $near): ?string
    {
        if (! checkdate($month, $day, $year ?? 2000)) {
            return $near;
        }

        if ($year === null) {
            $reference = $near ? Carbon::parse($near) : now();
            $year = $reference->year;

            // Np. „27.09” przy kolejce ze stycznia to wrzesień poprzedniego roku.
            if ($month - $reference->month > 6) {
                $year--;
            } elseif ($reference->month - $month > 6) {
                $year++;
            }
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, string>  $names
     */
    private static function column(array $header, array $names): ?int
    {
        foreach ($header as $index => $label) {
            if (in_array($label, $names, true)) {
                return $index;
            }
        }

        return null;
    }

    private static function label(string $text): string
    {
        return str_replace(['–', '—', ' '], ['-', '-', ''], mb_strtolower($text));
    }

    private static function clean(string $text): string
    {
        return trim(preg_replace('/\s+/u', ' ', str_replace("\u{00A0}", ' ', html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'))));
    }
}
