<?php

namespace App\Models\Concerns;

use App\Models\Gallery;
use App\Support\StreamLink;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Transmisje (YouTube, Facebook, inne) i galerie zdjęć meczu ligowego albo turniejowego.
 *
 * @mixin Model
 */
trait HasMatchMedia
{
    public function initializeHasMatchMedia(): void
    {
        $this->mergeCasts(['streams' => 'array']);
        $this->mergeFillable(['streams']);
    }

    public function galleries(): MorphMany
    {
        return $this->morphMany(Gallery::class, 'match')->orderBy('created_at')->orderBy('id');
    }

    public function publishedGalleries(): MorphMany
    {
        return $this->galleries()->where('is_published', true);
    }

    /**
     * Transmisje meczu z rozpoznaną platformą i adresem osadzenia YouTube.
     *
     * @return array<int, array{url: string, label: string, platform: string, platform_name: string, youtube_id: ?string, embed_url: ?string, thumbnail_url: ?string}>
     */
    public function normalizedStreams(): array
    {
        $streams = (array) ($this->streams ?? []);

        // Starsze pole „link do wideo” z edycji meczu traktujemy jak kolejną transmisję.
        if (filled($this->getAttribute('video_url'))) {
            $streams[] = ['url' => $this->getAttribute('video_url'), 'label' => ''];
        }

        return StreamLink::normalize($streams);
    }

    public function hasStreams(): bool
    {
        return $this->normalizedStreams() !== [];
    }

    /**
     * Jednoznaczny opis meczu do list wyboru w panelu (data, drużyna, rywale, rozgrywki).
     */
    abstract public function mediaLabel(): string;
}
