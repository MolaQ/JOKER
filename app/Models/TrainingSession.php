<?php

namespace App\Models;

use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Stały, cotygodniowy trening drużyny: dzień, godziny i miejsce (główne boisko, siłownia albo jedno z trzech boisk).
 */
class TrainingSession extends Model
{
    /** @var array<string, string> */
    public const VENUES = [
        'main' => 'Główne boisko',
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
        return self::venueNames()[$this->venue] ?? $this->venue;
    }

    /**
     * Nazwy miejsc z ustawień klubu (np. „Sektor I”), w tej samej kolejności co VENUES.
     *
     * @return array<string, string>
     */
    public static function venueNames(): array
    {
        $site = app(SiteSettings::class);

        return collect(self::VENUES)->map(fn (string $default, string $venue) => trim((string) $site->get('venue_'.$venue)) ?: __($default))->all();
    }

    /**
     * Boisko wpisane przy meczu pokazane nazwą z ustawień (stare „Boisko 1” → „Sektor I”); inne nazwy bez zmian.
     */
    public static function courtLabel(?string $court): ?string
    {
        $venue = self::venueForCourt($court);

        return $venue ? self::venueNames()[$venue] : $court;
    }

    /**
     * Symbol miejsca dla boiska wpisanego tekstem (np. w meczu turnieju): „Boisko 2” → court2, „Główne boisko” → main.
     */
    public static function venueForCourt(?string $court): ?string
    {
        $court = mb_strtolower(trim((string) $court));

        if ($court === '') {
            return null;
        }

        foreach (self::venueNames() as $venue => $name) {
            if ($venue !== 'gym' && mb_strtolower($name) === $court) {
                return $venue;
            }
        }

        $roman = ['i' => 1, 'ii' => 2, 'iii' => 3];

        return match (true) {
            (bool) preg_match('/^(?:boisko|sektor)\s*([123])$/u', $court, $found) => 'court'.$found[1],
            (bool) preg_match('/^(?:boisko|sektor)\s*(i{1,3})$/u', $court, $found) => 'court'.$roman[$found[1]],
            in_array($court, ['główne boisko', 'boisko główne', 'cała sala'], true) => 'main',
            default => null,
        };
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
