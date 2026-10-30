<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Grupa minisiatkówki: czwórki, trójki, dwójki albo singielki (liczba graczy na boisku i kategoria wiekowa).
 */
class MiniGroup extends Model
{
    /** @var array<string, string> */
    public const GENDERS = ['boys' => 'Chłopcy', 'girls' => 'Dziewczęta'];

    protected $fillable = [
        'name',
        'slug',
        'players_on_court',
        'age_label',
        'birth_year_from',
        'birth_year_to',
        'gender',
        'photo_path',
        'lead',
        'rules',
        'history',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (MiniGroup $group) {
            $group->slug = $group->slug ?: Str::slug($group->name) ?: 'grupa';
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<MiniGroup>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('display_order')->orderByDesc('players_on_court')->orderBy('name');
    }

    public function players(): HasMany
    {
        return $this->hasMany(MiniPlayer::class)->orderBy('last_name')->orderBy('first_name');
    }

    public function tripTeams(): HasMany
    {
        return $this->hasMany(MiniTripTeam::class);
    }

    /**
     * Zdjęcie grupy albo zdjęcie zastępcze (jak przy drużynach), żeby kafel nie był pusty.
     */
    public function photoUrl(): string
    {
        return $this->photo_path ? Media::url($this->photo_path) : Media::fallback('team_fallbacks', (int) $this->display_order + 4);
    }

    /**
     * „U-13 · rocznik 2013–2014”.
     */
    public function ageLine(): ?string
    {
        $years = match (true) {
            $this->birth_year_from && $this->birth_year_to && $this->birth_year_from !== $this->birth_year_to => __('rocznik').' '.$this->birth_year_from.'–'.$this->birth_year_to,
            (bool) ($this->birth_year_from ?: $this->birth_year_to) => __('rocznik').' '.($this->birth_year_from ?: $this->birth_year_to),
            default => null,
        };

        return collect([$this->age_label, $years, self::GENDERS[$this->gender] ?? null])->filter()->implode(' · ') ?: null;
    }

    public function nextTrip(): ?MiniTrip
    {
        return MiniTrip::upcoming()->whereHas('teams', fn ($query) => $query->where('mini_group_id', $this->id))->first();
    }

    public function lastResult(): ?MiniTripTeam
    {
        return $this->tripTeams()->with('trip')->whereNotNull('place')->get()->sortByDesc(fn (MiniTripTeam $team) => $team->trip->starts_on)->first();
    }

    /**
     * Jedna żywa informacja na kafel: najbliższy wyjazd, ostatni wynik albo zdanie z panelu.
     */
    public function tileLine(): ?string
    {
        if ($trip = $this->nextTrip()) {
            return __('Najbliższy wyjazd').': '.$trip->dateLabel().($trip->city ? ', '.$trip->city : '');
        }

        if ($result = $this->lastResult()) {
            return __('Ostatnio').': '.$result->place.'. '.__('miejsce').', '.($result->trip->city ?: $result->trip->title);
        }

        return $this->lead;
    }

    /**
     * Medale grupy (miejsca 1–3 drużyn z wyjazdów), od najnowszych.
     *
     * @return Collection<int, MiniTripTeam>
     */
    public function medals(): Collection
    {
        return $this->tripTeams()->with('trip')->whereBetween('place', [1, 3])->get()
            ->sortByDesc(fn (MiniTripTeam $team) => $team->trip->starts_on)->values();
    }
}
