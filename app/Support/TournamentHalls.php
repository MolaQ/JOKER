<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentCategory;
use App\Models\TrainingSession;

/**
 * Obiekty turnieju. Hala klubowa (trzy sektory i całe boisko) jest zawsze; turniej może mieć dodatkowe obiekty,
 * np. „MOSiR” albo „SP 6”, każdy z adresem, kolorem i dowolnymi salami. Sala ma 1–4 sektory i może mieć
 * całe boisko (zajmuje wszystkie sektory sali naraz), np. SP 6: „Hala 1” z trzema sektorami i całym boiskiem,
 * „Hala 2” z dwoma boiskami.
 *
 * Boisko zapisujemy przy meczu jako tekst „Obiekt · Sala · Sektor II” („Obiekt · Sala”, gdy sala nie jest podzielona),
 * więc mecz można w każdej chwili przenieść do dowolnego obiektu, sali i sektora.
 */
class TournamentHalls
{
    public const SEPARATOR = ' · ';

    /** Kolory obiektów po kolei, gdy nie wybrano własnego. */
    public const COLORS = ['#f97316', '#10b981', '#a855f7', '#ef4444', '#eab308', '#0ea5e9'];

    private const ROMAN = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    /** @var array<string, list<string>> całe boisko sali (małymi literami) → jej sektory */
    private static array $whole = [];

    public static function mainName(): string
    {
        return trim((string) app(SiteSettings::class)->get('venue_hall')) ?: __('Hala klubowa');
    }

    public static function wholeLabel(): string
    {
        return __('Całe boisko');
    }

    public static function sectorLabel(int $number): string
    {
        return __('Sektor').' '.(self::ROMAN[$number] ?? $number);
    }

    /**
     * Dodatkowe obiekty turnieju z salami i boiskami gotowymi do wyboru.
     * Starszy zapis (obiekt z listą boisk) czytamy jako sale bez podziału o tych nazwach, więc zapisane mecze się nie zmieniają.
     *
     * @return list<array{name: string, address: string, color: string, rooms: list<array{name: string, sectors: int, whole: bool, courts: list<array{value: string, label: string, whole: bool, index: int|null}>}>}>
     */
    public static function extra(?Tournament $tournament): array
    {
        $facilities = [];

        foreach (array_values($tournament?->halls ?? []) as $position => $raw) {
            $name = trim((string) ($raw['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $rooms = isset($raw['rooms'])
                ? array_map(fn ($room) => ['name' => trim((string) ($room['name'] ?? '')), 'sectors' => max(1, min(4, (int) ($room['sectors'] ?? 1))), 'whole' => (bool) ($room['whole'] ?? false)], (array) $raw['rooms'])
                : array_map(fn ($court) => ['name' => trim((string) $court), 'sectors' => 1, 'whole' => false], array_filter((array) ($raw['courts'] ?? []), fn ($court) => trim((string) $court) !== ''));

            if ($rooms === []) {
                continue;
            }

            $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($raw['color'] ?? '')) ? $raw['color'] : self::COLORS[$position % count(self::COLORS)];

            $facilities[] = [
                'name' => $name,
                'address' => trim((string) ($raw['address'] ?? '')),
                'color' => $color,
                'rooms' => array_map(fn (array $room) => $room + ['courts' => self::roomCourts($name, $room)], array_values($rooms)),
            ];
        }

        return $facilities;
    }

    /**
     * Boiska sali: sektory (gdy podzielona) i całe boisko (gdy niepodzielona albo zaznaczone).
     *
     * @param  array{name: string, sectors: int, whole: bool}  $room
     * @return list<array{value: string, label: string, whole: bool, index: int|null}>
     */
    private static function roomCourts(string $facility, array $room): array
    {
        $prefix = $room['name'] === '' ? $facility : $facility.self::SEPARATOR.$room['name'];

        if ($room['sectors'] === 1) {
            return [['value' => $prefix, 'label' => $room['name'] !== '' ? $room['name'] : self::wholeLabel(), 'whole' => true, 'index' => null]];
        }

        $courts = [];
        for ($number = 1; $number <= $room['sectors']; $number++) {
            $courts[] = ['value' => $prefix.self::SEPARATOR.self::sectorLabel($number), 'label' => self::sectorLabel($number), 'whole' => false, 'index' => $number - 1];
        }

        if ($room['whole']) {
            $whole = $prefix.self::SEPARATOR.self::wholeLabel();
            // Całe boisko zajmuje wszystkie sektory sali: tak liczymy nakładanie i układanie godzin.
            self::$whole[mb_strtolower($whole)] = array_map(fn (array $court) => mb_strtolower($court['value']), $courts);
            $courts[] = ['value' => $whole, 'label' => self::wholeLabel(), 'whole' => true, 'index' => null];
        }

        return $courts;
    }

    /**
     * Części sali zajęte przez boisko (całe boisko innej sali = jej sektory).
     *
     * @return list<string>|null
     */
    public static function physical(string $key): ?array
    {
        return self::$whole[$key] ?? null;
    }

    /**
     * Wartości boisk obiektu, na które układamy godziny: sektory sal podzielonych, całe sale niepodzielone.
     *
     * @return list<string>
     */
    public static function courtValues(?Tournament $tournament, string $facility): array
    {
        $found = collect(self::extra($tournament))->firstWhere('name', $facility);

        return $found ? collect($found['rooms'])->flatMap(fn ($room) => collect($room['courts'])->reject(fn ($court) => $court['whole'] && $room['sectors'] > 1)->pluck('value'))->values()->all() : [];
    }

    /**
     * Gdzie jest boisko meczu: obiekt, sala i boisko; null dla hali klubowej i nieznanych nazw.
     *
     * @return array{facility: array<string, mixed>, room: array<string, mixed>, court: array<string, mixed>}|null
     */
    public static function find(?Tournament $tournament, ?string $court): ?array
    {
        $court = (string) $court;

        if ($court === '') {
            return null;
        }

        foreach (self::extra($tournament) as $facility) {
            foreach ($facility['rooms'] as $room) {
                foreach ($room['courts'] as $entry) {
                    if ($entry['value'] === $court) {
                        return ['facility' => $facility, 'room' => $room, 'court' => $entry];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Obiekt meczu: nazwa dodatkowego obiektu albo null (hala klubowa).
     */
    public static function hallOf(?Tournament $tournament, ?string $court): ?string
    {
        return self::find($tournament, $court)['facility']['name'] ?? null;
    }

    /**
     * Nazwa obiektu i sali do nagłówka w obłożeniu („SP 6 · Hala 1”).
     */
    public static function roomOf(?Tournament $tournament, ?string $court): ?string
    {
        $found = self::find($tournament, $court);

        if (! $found) {
            return null;
        }

        // Sala bez podziału jest jedną kolumną obiektu; sala z sektorami ma własną grupę kolumn.
        return $found['room']['name'] === '' || $found['room']['sectors'] === 1
            ? $found['facility']['name']
            : $found['facility']['name'].self::SEPARATOR.$found['room']['name'];
    }

    /**
     * Wartość boiska dla podanego obiektu i nazwy (zgodność ze starszym zapisem „Obiekt · Boisko”).
     */
    public static function value(string $facility, string $court): string
    {
        return $facility.self::SEPARATOR.$court;
    }

    /**
     * Ikona boiska: sektor hali klubowej (venue) albo część sali innego obiektu w kolorze obiektu.
     *
     * @return array{venue?: string, parts?: int, index?: int|null, whole?: bool, color?: string}|null
     */
    public static function icon(?string $court, ?int $categoryId): ?array
    {
        if (blank($court)) {
            return null;
        }

        $found = self::find($categoryId ? self::tournamentOfCategory($categoryId) : null, $court);

        if ($found) {
            return self::iconFor($found['facility'], $found['room'], $found['court']);
        }

        $venue = TrainingSession::venueForCourt($court);

        return $venue ? ['venue' => $venue] : null;
    }

    /**
     * @param  array<string, mixed>  $facility
     * @param  array<string, mixed>  $room
     * @param  array<string, mixed>  $court
     * @return array{parts: int, index: int|null, whole: bool, color: string}
     */
    public static function iconFor(array $facility, array $room, array $court): array
    {
        return ['parts' => $room['sectors'], 'index' => $court['index'], 'whole' => $court['whole'], 'color' => $facility['color']];
    }

    private static function tournamentOfCategory(int $categoryId): ?Tournament
    {
        if (! app()->bound('tournament-halls.cache')) {
            app()->instance('tournament-halls.cache', new \ArrayObject);
        }

        $cache = app('tournament-halls.cache');

        if (! $cache->offsetExists($categoryId)) {
            $cache[$categoryId] = Tournament::whereKey(TournamentCategory::whereKey($categoryId)->select('tournament_id'))->first(['id', 'halls', 'venue', 'city']);
        }

        return $cache[$categoryId];
    }

    /**
     * Obiekty do legendy: klubowy (z adresem turnieju) i dodatkowe, z salami, boiskami i ikonami.
     *
     * @return list<array{name: string, address: string, color: string|null, rooms: list<array{name: string, courts: list<array{value: string, label: string, icon: array<string, mixed>}>}>}>
     */
    public static function legend(Tournament $tournament): array
    {
        $names = TrainingSession::venueNames();
        $legend = [[
            'name' => self::mainName(),
            'address' => trim(implode(', ', array_filter([(string) $tournament->venue, (string) $tournament->city]))),
            'color' => null,
            'rooms' => [['name' => '', 'courts' => array_map(fn (string $venue) => ['value' => $names[$venue], 'label' => $names[$venue], 'icon' => ['venue' => $venue]], ['court3', 'court2', 'court1', 'main'])]],
        ]];

        foreach (self::extra($tournament) as $facility) {
            $legend[] = [
                'name' => $facility['name'],
                'address' => $facility['address'],
                'color' => $facility['color'],
                'rooms' => array_map(fn (array $room) => [
                    'name' => $room['name'],
                    'courts' => array_map(fn (array $court) => ['value' => $court['value'], 'label' => $court['label'], 'icon' => self::iconFor($facility, $room, $court)], $room['courts']),
                ], $facility['rooms']),
            ];
        }

        return $legend;
    }

    /**
     * Samo boisko bez nazwy obiektu i sali (kolumna w obłożeniu sali).
     */
    public static function courtName(?Tournament $tournament, ?string $court): ?string
    {
        return self::find($tournament, $court)['court']['label'] ?? TrainingSession::courtLabel($court);
    }
}
