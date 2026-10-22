<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Stały, cotygodniowy trening drużyny: dzień, godziny i miejsce (siłownia albo jedno z trzech boisk).
 */
class TrainingSession extends Model
{
    /** @var array<string, string> */
    public const VENUES = [
        'gym' => 'Siłownia',
        'court1' => 'Boisko 1',
        'court2' => 'Boisko 2',
        'court3' => 'Boisko 3',
    ];

    /** @var array<int, string> */
    public const WEEKDAYS = [
        1 => 'Poniedziałek',
        2 => 'Wtorek',
        3 => 'Środa',
        4 => 'Czwartek',
        5 => 'Piątek',
        6 => 'Sobota',
        7 => 'Niedziela',
    ];

    protected $fillable = [
        'team_id',
        'weekday',
        'starts_at',
        'ends_at',
        'venue',
        'note',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('weekday')->orderBy('starts_at');
    }

    public function weekdayLabel(): string
    {
        return __(self::WEEKDAYS[$this->weekday] ?? '');
    }

    public function venueLabel(): string
    {
        return __(self::VENUES[$this->venue] ?? $this->venue);
    }

    public function timeRange(): string
    {
        return substr((string) $this->starts_at, 0, 5).'–'.substr((string) $this->ends_at, 0, 5);
    }

    /**
     * Inne treningi w tym samym miejscu i dniu, które nakładają się godzinami.
     *
     * @return Collection<int, TrainingSession>
     */
    public static function conflicts(int $weekday, string $venue, string $startsAt, string $endsAt, ?int $ignoreId = null): Collection
    {
        return self::with('team')
            ->where('weekday', $weekday)
            ->where('venue', $venue)
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->get();
    }
}
