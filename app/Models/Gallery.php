<?php

namespace App\Models;

use App\Models\Concerns\Likeable;
use Database\Factories\GalleryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Galeria zdjęć z konkretnego meczu: ligowego (Game) albo turniejowego (TournamentMatch).
 */
class Gallery extends Model
{
    /** @use HasFactory<GalleryFactory> */
    use HasFactory, Likeable;

    protected $fillable = [
        'title',
        'description',
        'match_type',
        'match_id',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
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
}
