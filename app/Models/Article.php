<?php

namespace App\Models;

use App\Models\Concerns\HasEnglishVersion;
use App\Models\Concerns\HasSlugRouteKey;
use App\Models\Concerns\Likeable;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    protected static function booted(): void
    {
        // Opublikowany bez daty byłby niewidoczny na stronie: pusta data = teraz.
        static::saving(function (Article $article) {
            if ($article->status === 'published' && $article->published_at === null) {
                $article->published_at = now();
            }
        });
    }

    use HasEnglishVersion, HasSlugRouteKey;

    /** @use HasFactory<ArticleFactory> */
    use HasFactory, Likeable;

    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'excerpt',
        'excerpt_en',
        'content',
        'content_en',
        'featured_image',
        'featured_image_focus',
        'author_id',
        'author_name',
        'status',
        'published_at',
        'views_count',
        'is_featured',
        'allow_comments',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Podpis autora: wpisany ręcznie (osoba spoza społeczności) albo nazwa konta.
     */
    public function authorName(): ?string
    {
        return filled($this->author_name) ? $this->author_name : $this->author?->name;
    }

    /**
     * Link do profilu autora, gdy jest w społeczności klubu.
     */
    public function authorUrl(): ?string
    {
        return blank($this->author_name) && $this->author ? $this->author->profileUrl() : null;
    }

    /**
     * Mecze, których dotyczy artykuł (relacja, zapowiedź) - otwierane z kalendarza.
     */
    public function games(): BelongsToMany
    {
        return $this->belongsToMany(Game::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('is_approved', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc');
    }

    /**
     * Kolejność na stronie: najpierw wyróżnione (przypięte), potem najnowsze.
     */
    public function scopeForListing($query)
    {
        return $query->published()->reorder()->orderByDesc('is_featured')->orderByDesc('published_at')->orderByDesc('id');
    }

    /**
     * Stan do pokazania w panelu: szkic, zaplanowany, opublikowany albo archiwum.
     *
     * @return array{0: string, 1: string}
     */
    public function statusBadge(): array
    {
        return match (true) {
            $this->status === 'published' && $this->published_at?->isFuture() => ['Zaplanowany na '.$this->published_at->format('d.m H:i'), 'bg-sky-50 text-sky-700 ring-sky-600/20'],
            $this->status === 'published' => ['Opublikowany', 'bg-green-50 text-green-700 ring-green-600/20'],
            $this->status === 'archived' => ['Archiwum (ukryty)', 'bg-yellow-50 text-yellow-800 ring-yellow-600/20'],
            default => ['Szkic (ukryty)', 'bg-gray-50 text-gray-600 ring-gray-500/10'],
        };
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function localizedExcerpt(): ?string
    {
        return $this->localized('excerpt');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' &&
               $this->published_at &&
               $this->published_at <= now();
    }

    /**
     * Punkt zdjęcia, który zostaje widoczny przy przycinaniu (CSS object-position).
     */
    public function imageFocus(): string
    {
        return preg_match('/^\d{1,3}% \d{1,3}%$/', (string) $this->featured_image_focus) ? $this->featured_image_focus : '50% 50%';
    }
}
