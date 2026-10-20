<?php

namespace App\Models;

use Database\Factories\TournamentFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Turniej w trakcie sezonu (np. organizowany przez klub Turniej im. Włodzimierza Winklera
 * albo wyjazd Kadetów II na turniej w Sulęcinie) z miejscami zajętymi przez nasze drużyny.
 */
class Tournament extends Model
{
    /** @use HasFactory<TournamentFactory> */
    use HasFactory;

    protected $fillable = [
        'season_id',
        'name',
        'edition',
        'start_date',
        'end_date',
        'venue',
        'city',
        'is_organizer',
        'description',
        'slug',
        'series',
        'image_path',
        'rules',
        'guest_info',
        'has_page',
    ];

    /** Seria edycji Memoriału im. Włodzimierza Winklera (zakładka w menu). */
    public const SERIES_MEMORIAL = 'memorial';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_organizer' => 'boolean',
            'has_page' => 'boolean',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('placement')->orderBy('display_order');
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class)->orderBy('game_date');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TournamentCategory::class)->orderBy('display_order')->orderBy('id');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saved(function (Tournament $tournament) {
            if (blank($tournament->slug)) {
                $tournament->slug = $tournament->id.'-'.Str::slug($tournament->fullName());
                $tournament->saveQuietly();
            }
        });
    }

    /**
     * Pozostałe edycje tej samej serii (archiwum), od najnowszej.
     *
     * @return Collection<int, Tournament>
     */
    public function otherEditions(): Collection
    {
        if (blank($this->series)) {
            return new Collection;
        }

        return static::where('series', $this->series)->whereKeyNot($this->id)->where('has_page', true)->orderByDesc('start_date')->get();
    }

    public function lastDay(): Carbon
    {
        return ($this->end_date ?? $this->start_date)->copy();
    }

    /**
     * Pełna nazwa z edycją, np. „XII Turniej im. Włodzimierza Winklera”.
     */
    public function fullName(): string
    {
        return trim(($this->edition ? $this->edition.' ' : '').$this->name);
    }

    /**
     * „24.10” albo „24–25.10” albo „31.10–1.11”.
     */
    public function dateRangeLabel(): string
    {
        $end = $this->end_date;

        if (! $end || $end->isSameDay($this->start_date)) {
            return $this->start_date->format('j.m');
        }

        return $end->month === $this->start_date->month
            ? $this->start_date->format('j').'–'.$end->format('j.m')
            : $this->start_date->format('j.m').'–'.$end->format('j.m');
    }
}
