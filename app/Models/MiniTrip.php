<?php

namespace App\Models;

use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Wyjazd dzieci na turniej minisiatkówki (albo turniej u nas). Szczegóły organizacyjne
 * (zbiórka, transport, koszt) widzą tylko zalogowani rodzice i osoby z panelu klubu.
 */
class MiniTrip extends Model
{
    protected $fillable = [
        'title',
        'starts_on',
        'ends_on',
        'city',
        'venue',
        'organizer',
        'description',
        'parent_info',
        'tournament_id',
    ];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(MiniTripTeam::class)->orderBy('name');
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    /**
     * @param  Builder<MiniTrip>  $query
     */
    public function scopeUpcoming(Builder $query): void
    {
        $query->whereDate('starts_on', '>=', today())->orderBy('starts_on');
    }

    public function isPast(): bool
    {
        return ($this->ends_on ?? $this->starts_on)->lt(today());
    }

    public function dateLabel(): string
    {
        $start = $this->starts_on->translatedFormat('D j.m');

        return $this->ends_on && ! $this->ends_on->isSameDay($this->starts_on) ? $start.'–'.$this->ends_on->translatedFormat('D j.m') : $start;
    }

    public static function canSeeParentInfo(?User $user): bool
    {
        return app(SiteSettings::class)['mini_show_parent_info'] && $user !== null && ($user->hasRole(UserRole::Parent) || $user->canAccessPanel());
    }
}
