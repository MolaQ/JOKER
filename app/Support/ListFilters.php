<?php

namespace App\Support;

/**
 * Wspólne ustawienia filtrów list w panelu (trait App\Livewire\Concerns\FiltersList, components/filters).
 */
final class ListFilters
{
    /** Skróty dat do wyboru jednym kliknięciem: klucz => etykieta. */
    public const DATE_PRESETS = [
        'today' => 'Dziś',
        'week' => 'Ten tydzień',
        'month' => 'Ten miesiąc',
        'upcoming' => 'Nadchodzące',
        'past' => 'Minione',
        'season' => 'Bieżący sezon',
    ];
}
