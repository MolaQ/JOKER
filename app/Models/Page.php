<?php

namespace App\Models;

use App\Models\Concerns\HasEnglishVersion;
use App\Models\Concerns\Likeable;
use App\Permission;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasEnglishVersion, HasFactory, Likeable;

    protected $fillable = [
        'title',
        'title_en',
        'slug',
        'lead',
        'lead_en',
        'content',
        'content_en',
        'image_path',
        'is_published',
        'display_order',
        'parent_id',
        'in_menu',
        'footer_link',
        'image_is_banner',
        'theme_primary',
        'theme_accent',
        'highlights',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'in_menu' => 'boolean',
            'footer_link' => 'boolean',
            'image_is_banner' => 'boolean',
            'highlights' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function localizedLead(): ?string
    {
        return $this->localized('lead');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Ukryta podstrona to projekt przed publikacją: widzi ją tylko ktoś z uprawnieniem „Podgląd ukrytych podstron”
     * (administrator, szef), razem z linkami w menu działu i stopce.
     */
    public static function canPreview(?User $user): bool
    {
        return (bool) $user?->hasPermission(Permission::PagePreview);
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return self::canPreview($user) ? $query : $query->where('is_published', true)
            ->where(fn (Builder $query) => $query->whereNull('parent_id')->orWhereHas('parent', fn (Builder $parent) => $parent->where('is_published', true)));
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->is_published && (! $this->parent_id || $this->parent?->is_published) || self::canPreview($user);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Page::class, 'parent_id')->orderBy('display_order')->orderBy('title');
    }

    /**
     * Menu działu: podstrony najwyższego poziomu z ich podstronami, w kolejności z panelu.
     *
     * @return Collection<int, Page>
     */
    public static function menuFor(?User $user): Collection
    {
        $pages = self::query()->visibleTo($user)->where('in_menu', true)
            ->orderBy('display_order')->orderBy('title')
            ->get(['id', 'parent_id', 'title', 'title_en', 'slug', 'is_published', 'theme_accent']);

        return $pages->whereNull('parent_id')->values()
            ->each(fn (Page $page) => $page->setRelation('children', $pages->where('parent_id', $page->id)->values()));
    }

    /**
     * Linki w stopce (kolumna z linkami klubu), np. programy, w których klub bierze udział.
     *
     * @return Collection<int, Page>
     */
    public static function footerLinksFor(?User $user): Collection
    {
        return self::query()->visibleTo($user)->where('footer_link', true)
            ->orderBy('display_order')->orderBy('title')
            ->get(['id', 'title', 'title_en', 'slug', 'is_published']);
    }

    /**
     * Kolory strony (np. w barwach programu): zmienne CSS dla nagłówka, menu i wyróżnień.
     */
    public function themeStyle(): ?string
    {
        if (! $this->theme_primary && ! $this->theme_accent) {
            return null;
        }

        return collect(['--page-primary' => $this->theme_primary, '--page-accent' => $this->theme_accent])
            ->filter()
            ->map(fn ($color, $name) => $name.': '.e($color))
            ->implode('; ');
    }
}
