<?php

namespace App\Models;

use App\Support\ImageOptimizer;
use App\Support\Media;
use App\Support\Trash;
use Database\Factories\GalleryPhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    /** @use HasFactory<GalleryPhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'gallery_id',
        'path',
        'caption',
        'display_order',
    ];

    protected static function booted(): void
    {
        // Każde nowe zdjęcie galerii (panel, transmisje i galerie, propozycje rodziców) jest od razu zmniejszane.
        static::created(fn (GalleryPhoto $photo) => ImageOptimizer::process($photo->path));
        static::deleted(fn (GalleryPhoto $photo) => Trash::$keepFiles || Storage::disk('public')->delete(ImageOptimizer::thumbPath($photo->path)));
    }

    public function gallery(): BelongsTo
    {
        return $this->belongsTo(Gallery::class);
    }

    public function url(): ?string
    {
        return Media::url($this->path);
    }

    /**
     * Mała wersja do siatki galerii; starsze zdjęcia bez miniatury pokazują oryginał.
     */
    public function thumbUrl(): ?string
    {
        $thumb = ImageOptimizer::thumbPath((string) $this->path);

        return Storage::disk('public')->exists($thumb) ? Media::url($thumb) : $this->url();
    }

    public function tags(): HasMany
    {
        return $this->hasMany(PhotoTag::class);
    }

    /**
     * Zatwierdzone oznaczenia, które można pokazać (bez zawodników, którzy nie mają zgody na wizerunek).
     *
     * @return Collection<int, PhotoTag>
     */
    public function visibleTags(): Collection
    {
        return $this->tags
            ->filter(fn (PhotoTag $tag) => $tag->status === PhotoTag::STATUS_APPROVED && $tag->isVisible())
            ->sortBy(fn (PhotoTag $tag) => [$tag->taggable instanceof Team ? 0 : 1, $tag->label()])
            ->values();
    }

    /**
     * Tekst alternatywny: opis zdjęcia albo oznaczone osoby i drużyny (pomaga też w wyszukiwarkach).
     */
    public function altText(string $fallback = ''): string
    {
        $names = $this->visibleTags()->map->label()->join(', ');

        return trim(collect([$this->caption, $names])->filter()->join(' · ')) ?: $fallback;
    }
}
