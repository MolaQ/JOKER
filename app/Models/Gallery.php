<?php

namespace App\Models;

use App\Models\Concerns\HasReadableRouteKey;
use App\Models\Concerns\Likeable;
use Database\Factories\GalleryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Galeria zdjęć z meczu (ligowego albo turniejowego) lub z wydarzenia bez meczu
 * (obóz, trening otwarty, festyn). Ma własną stronę w dziale Galerie.
 */
class Gallery extends Model
{
    /** @use HasFactory<GalleryFactory> */
    use HasFactory, HasReadableRouteKey, Likeable;

    protected $fillable = [
        'title',
        'description',
        'match_type',
        'match_id',
        'is_published',
        'event_date',
        'team_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'event_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Pliki zdjęć usuwamy razem z galerią (rekordy usuwa klucz obcy).
        static::deleting(function (Gallery $gallery) {
            $gallery->photos()->pluck('path')->each(fn (string $path) => Storage::disk('public')->delete($path));
        });
    }

    public function match(): MorphTo
    {
        return $this->morphTo();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('display_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Liczba zdjęć w opublikowanych galeriach meczów jednej kategorii turnieju (klucz: id meczu).
     * Wynik jest zapamiętany na czas żądania, by karty meczów nie pytały bazy osobno.
     *
     * @return Collection<int, int>
     */
    public static function photoCountsForTournamentCategory(?int $categoryId): Collection
    {
        if ($categoryId === null) {
            return collect();
        }

        $key = 'gallery-photo-counts.category.'.$categoryId;
        $attributes = request()->attributes;

        if (! $attributes->has($key)) {
            $attributes->set($key, static::query()
                ->published()
                ->where('match_type', (new TournamentMatch)->getMorphClass())
                ->whereIn('match_id', TournamentMatch::where('tournament_category_id', $categoryId)->select('id'))
                ->withCount('photos')
                ->get(['id', 'match_id'])
                ->groupBy('match_id')
                ->map(fn (Collection $galleries) => (int) $galleries->sum('photos_count')));
        }

        return $attributes->get($key);
    }

    /**
     * Data do wyświetlenia: data meczu, a dla galerii z wydarzenia data wydarzenia.
     */
    public function displayDate(): ?Carbon
    {
        return match (true) {
            $this->match instanceof Game => $this->match->game_date,
            $this->match instanceof TournamentMatch => $this->match->starts_at ?? $this->event_date,
            default => $this->event_date,
        };
    }

    /**
     * Strona meczu, z którego są zdjęcia (null dla galerii z wydarzenia).
     */
    public function matchUrl(): ?string
    {
        if ($this->match instanceof Game) {
            return route('games.show', $this->match);
        }

        $tournament = $this->match instanceof TournamentMatch ? $this->match->tournament() : null;

        return $tournament?->has_page ? route('tournaments.matches.show', [$tournament, $this->match]) : null;
    }

    protected function routeSlugSource(): string
    {
        return (string) $this->title;
    }
}
