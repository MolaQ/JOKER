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
