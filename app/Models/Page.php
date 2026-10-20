<?php

namespace App\Models;

use App\Models\Concerns\HasEnglishVersion;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasEnglishVersion, HasFactory;

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
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
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
}
