<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TrainingSession;

/**
 * Hale turnieju. Hala klubowa (trzy sektory i całe boisko) jest zawsze; turniej może mieć dodatkowe hale
 * z własnymi boiskami, np. „MOSiR” z sektorami albo „SP 3” z halą 1 i halą 2.
 * Boisko innej hali zapisujemy przy meczu jako „Nazwa hali · Boisko”, więc mecz można w każdej chwili
 * przenieść do dowolnej hali i dowolnego boiska, bez osobnych pól.
 */
class TournamentHalls
{
    public const SEPARATOR = ' · ';

    public static function mainName(): string
    {
        return trim((string) app(SiteSettings::class)->get('venue_hall')) ?: __('Hala klubowa');
    }

    /**
     * Dodatkowe hale turnieju.
     *
     * @return list<array{name: string, courts: list<string>, address: string}>
     */
    public static function extra(?Tournament $tournament): array
    {
        return collect($tournament?->halls ?? [])
            ->map(fn ($hall) => [
                'name' => trim((string) ($hall['name'] ?? '')),
                'courts' => array_values(array_filter(array_map(fn ($court) => trim((string) $court), (array) ($hall['courts'] ?? [])), 'strlen')),
                'address' => trim((string) ($hall['address'] ?? '')),
            ])
            ->filter(fn (array $hall) => $hall['name'] !== '' && $hall['courts'] !== [])
            ->values()
            ->all();
    }

    /**
     * Zapisywana wartość boiska w dodatkowej hali.
     */
    public static function value(string $hall, string $court): string
    {
        return $hall.self::SEPARATOR.$court;
    }

    /**
     * Wartości boisk dodatkowej hali (to, co zapisujemy przy meczu).
     *
     * @return list<string>
     */
    public static function courtValues(?Tournament $tournament, string $hall): array
    {
        $found = collect(self::extra($tournament))->firstWhere('name', $hall);

        return $found ? array_map(fn (string $court) => self::value($hall, $court), $found['courts']) : [];
    }

    /**
     * Hala meczu: nazwa dodatkowej hali albo null (hala klubowa).
     */
    public static function hallOf(?Tournament $tournament, ?string $court): ?string
    {
        $court = (string) $court;

        foreach (self::extra($tournament) as $hall) {
            if (str_starts_with($court, $hall['name'].self::SEPARATOR)) {
                return $hall['name'];
            }
        }

        return null;
    }

    /**
     * Samo boisko bez nazwy hali (dla kolumn obłożenia danej hali).
     */
    public static function courtName(?Tournament $tournament, ?string $court): ?string
    {
        $hall = self::hallOf($tournament, $court);

        return $hall ? mb_substr((string) $court, mb_strlen($hall.self::SEPARATOR)) : TrainingSession::courtLabel($court);
    }
}
