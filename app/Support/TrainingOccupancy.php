<?php

namespace App\Support;

use App\Models\TrainingSession;
use Illuminate\Support\Collection;

/**
 * Obłożenie sali w planie treningów: dla każdego dnia tygodnia bloki zajęć na osi czasu.
 * Sektory leżą obok siebie jak w hali (III, II, I), główne boisko zajmuje wszystkie trzy, siłownia ma własną kolumnę.
 * Zajęcia, które nakładają się w tej samej części sali, dzielą szerokość i są oznaczone.
 */
class TrainingOccupancy
{
    /** Kolejność sektorów jak w sali: III z lewej, I z prawej. */
    public const SECTORS = ['court3', 'court2', 'court1'];

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     * @return Collection<int, array{start: int, end: int, courts: list<array<string, mixed>>, gym: list<array<string, mixed>>}>
     */
    public static function days(Collection $sessions): Collection
    {
        return $sessions->groupBy('weekday')->sortKeys()->map(function (Collection $day) {
            $blocks = $day->map(fn (TrainingSession $session) => [
                'session' => $session,
                'from' => self::minutes($session->starts_at),
                'to' => self::minutes($session->ends_at),
                'span' => self::span($session->venue),
                'color' => $session->colors()[0],
                'colors' => $session->colors(),
            ])->filter(fn (array $block) => $block['to'] > $block['from']);

            return [
                // oś od pełnej godziny przed pierwszymi zajęciami do pełnej godziny po ostatnich
                'start' => (int) (floor($blocks->min('from') / 60) * 60),
                'end' => (int) (ceil($blocks->max('to') / 60) * 60),
                'courts' => self::layout($blocks->filter(fn ($block) => $block['session']->venue !== 'gym')->values()->all(), 3),
                'gym' => self::layout($blocks->filter(fn ($block) => $block['session']->venue === 'gym')->values()->all(), 1),
            ];
        })->filter(fn (array $day) => $day['courts'] !== [] || $day['gym'] !== []);
    }

    /**
     * Położenie bloków w procentach szerokości: nakładające się zajęcia dostają osobne „pasy”.
     *
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private static function layout(array $blocks, int $columns): array
    {
        usort($blocks, fn ($a, $b) => [$a['from'], $b['to']] <=> [$b['from'], $a['to']]);
        $clashes = fn (array $a, array $b) => $a['from'] < $b['to'] && $b['from'] < $a['to'] && $a['span'][0] < $b['span'][1] && $b['span'][0] < $a['span'][1];

        foreach ($blocks as $i => $block) {
            $taken = [];
            foreach (array_slice($blocks, 0, $i) as $earlier) {
                if ($clashes($block, $earlier)) {
                    $taken[] = $earlier['lane'];
                }
            }
            $lane = 0;
            while (in_array($lane, $taken, true)) {
                $lane++;
            }
            $blocks[$i]['lane'] = $lane;
        }

        foreach ($blocks as $i => $block) {
            $lanes = 1 + max([$block['lane'], ...array_map(fn ($other) => $clashes($block, $other) ? $other['lane'] : 0, $blocks)]);
            $width = ($block['span'][1] - $block['span'][0]) / $columns * 100 / $lanes;
            $blocks[$i] += [
                'left' => $block['span'][0] / $columns * 100 + $width * $block['lane'],
                'width' => $width,
                'conflict' => $lanes > 1,
            ];
        }

        return $blocks;
    }

    /**
     * Które kolumny zajmują zajęcia: [od, do) w kolumnach sektorów (siłownia ma jedną własną).
     *
     * @return array{0: int, 1: int}
     */
    private static function span(string $venue): array
    {
        if ($venue === 'main') {
            return [0, 3];
        }

        $index = (int) array_search($venue, self::SECTORS, true);

        return [$index, $index + 1];
    }

    private static function minutes(?string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr((string) $time, 0, 5)) + [0, 0]);

        return $hours * 60 + $minutes;
    }

    public static function clock(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
