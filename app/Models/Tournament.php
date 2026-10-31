<?php

namespace App\Models;

use App\Models\Concerns\HasReadableRouteKey;
use App\Models\Concerns\Likeable;
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
    use HasFactory, HasReadableRouteKey, Likeable;

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
        'scoring',
        'rules',
        'guest_info',
        'has_page',
    ];

    /** Seria edycji Memoriału im. Włodzimierza Winklera (zakładka w menu). */
    public const SERIES_MEMORIAL = 'memorial';

    protected function casts(): array
    {
        return [
            'scoring' => 'array',
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

    /**
     * Osoby wpisujące wyniki tego turnieju (panel turnieju → „Wpisujący wyniki”).
     */
    public function scorers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_scorers')->withTimestamps();
    }

    /**
     * Wpisujący wyniki mają dostęp od dnia przed turniejem do końca dnia po nim.
     */
    public function scoringOpen(): bool
    {
        return now()->between($this->start_date->copy()->subDay()->startOfDay(), ($this->end_date ?? $this->start_date)->copy()->addDay()->endOfDay());
    }

    /**
     * Czy osoba może wpisywać wyniki: zarządzający turniejami zawsze, wpisujący tylko w dniach turnieju.
     */
    public function canBeScoredBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->can('tournaments.manage') || ($this->scoringOpen() && $this->scorers()->whereKey($user->id)->exists());
    }

    public function categories(): HasMany
    {
        return $this->hasMany(TournamentCategory::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * Adres „/tournaments/1/memorial-wlodzimierza-winklera-2026” (z kolumny slug „1-memorial-...”).
     */
    protected function routeSlugSource(): string
    {
        return preg_replace('/^\d+-/', '', (string) $this->slug) ?: $this->fullName();
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
