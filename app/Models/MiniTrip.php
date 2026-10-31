<?php

namespace App\Models;

use App\Support\SearchIndex;
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
        'status',
        'pending_results',
        'submitted_by',
    ];

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_PENDING = 'pending';

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'pending_results' => 'array'];
    }

    public function teams(): HasMany
    {
        return $this->hasMany(MiniTripTeam::class)->orderBy('name');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->published()->latest('published_at');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * Turnieje widoczne na stronie (zgłoszone przez trenera mini czekają na zatwierdzenie).
     *
     * @param  Builder<MiniTrip>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED);
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
        $query->where('status', self::STATUS_PUBLISHED)->whereDate('starts_on', '>=', today())->orderBy('starts_on');
    }

    /**
     * Turniej u nas: miasto turnieju jest w adresie klubu (Ustawienia → Kontakt), np. „Piła”.
     */
    public function isHome(): bool
    {
        $city = SearchIndex::normalize((string) $this->city);

        return $city !== '' && str_contains(SearchIndex::normalize((string) app(SiteSettings::class)['contact_address']), $city);
    }

    public function isPast(): bool
    {
        return ($this->ends_on ?? $this->starts_on)->lt(today());
    }

    /**
     * Opis turnieju w propozycjach treści i panelu: „Minisiatkówka: Turniej w Ostrowie (sob 12.10)”.
     */
    public function mediaLabel(): string
    {
        return __('Minisiatkówka').': '.($this->title ?: $this->city).' ('.$this->dateLabel().')';
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
