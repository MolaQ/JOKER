<?php

namespace App\Livewire\Concerns;

use App\Models\Season;
use App\Support\ListFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Wspólne filtry list w panelu: zakres dat z gotowymi skrótami (dziś, ten tydzień, nadchodzące…),
 * przycisk „Wyczyść filtry” i licznik aktywnych filtrów. Stan jest w adresie strony, więc wynik da się odświeżyć i wysłać linkiem.
 *
 * Klasa podaje w filterDefaults() swoje pola filtrów z wartościami domyślnymi.
 */
trait FiltersList
{
    #[Url(as: 'od', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'do', except: '')]
    public string $dateTo = '';

    /**
     * Pola filtrów tej listy z wartościami, do których wraca „Wyczyść filtry”.
     *
     * @return array<string, mixed>
     */
    abstract protected function filterDefaults(): array;

    /**
     * Ustawia pole filtra (przyciski filtrów); drugie kliknięcie tej samej wartości wyłącza filtr.
     */
    public function setFilter(string $field, string $value): void
    {
        $defaults = $this->filterDefaults();

        if (! array_key_exists($field, $defaults)) {
            return;
        }

        $this->{$field} = $value === '' || (string) $this->{$field} === $value ? $defaults[$field] : $value;
        $this->resetPage();
    }

    public function setDatePreset(string $preset): void
    {
        [$from, $to] = $this->presetRange($preset) ?? ['', ''];

        // Drugie kliknięcie aktywnego skrótu wyłącza zakres dat.
        if ($this->activeDatePreset() === $preset) {
            [$from, $to] = ['', ''];
        }

        [$this->dateFrom, $this->dateTo] = [$from, $to];
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        foreach ($this->filterDefaults() as $field => $default) {
            $this->{$field} = $default;
        }

        [$this->dateFrom, $this->dateTo] = ['', ''];
        $this->resetPage();
    }

    public function activeFiltersCount(): int
    {
        $count = collect($this->filterDefaults())->filter(fn ($default, $field) => $this->{$field} !== $default)->count();

        return $count + ($this->dateFrom !== '' || $this->dateTo !== '' ? 1 : 0);
    }

    /**
     * Skrót, któremu odpowiada wybrany zakres dat (do podświetlenia przycisku), albo null.
     */
    public function activeDatePreset(): ?string
    {
        if ($this->dateFrom === '' && $this->dateTo === '') {
            return null;
        }

        foreach (array_keys(ListFilters::DATE_PRESETS) as $preset) {
            if ($this->presetRange($preset) === [$this->dateFrom, $this->dateTo]) {
                return $preset;
            }
        }

        return null;
    }

    /**
     * Zawęża zapytanie do wybranego zakresu dat (obie granice włącznie, cały dzień).
     */
    protected function applyDateFilter(Builder $query, string $column): Builder
    {
        $from = $this->parseDate($this->dateFrom);
        $to = $this->parseDate($this->dateTo);

        return $query
            ->when($from, fn (Builder $q) => $q->where($column, '>=', $from->startOfDay()))
            ->when($to, fn (Builder $q) => $q->where($column, '<=', $to->endOfDay()));
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function presetRange(string $preset): ?array
    {
        $today = Carbon::today();
        $format = fn (?Carbon $date) => $date?->format('Y-m-d') ?? '';

        return match ($preset) {
            'today' => [$format($today), $format($today)],
            'week' => [$format($today->copy()->startOfWeek()), $format($today->copy()->endOfWeek())],
            'month' => [$format($today->copy()->startOfMonth()), $format($today->copy()->endOfMonth())],
            'upcoming' => [$format($today), ''],
            'past' => ['', $format($today->copy()->subDay())],
            'season' => ($season = Season::current()->first())
                ? [$format($season->start_date ? Carbon::parse($season->start_date) : null), $format($season->end_date ? Carbon::parse($season->end_date) : null)]
                : null,
            default => null,
        };
    }

    private function parseDate(string $value): ?Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
