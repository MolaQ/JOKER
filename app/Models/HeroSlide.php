<?php

namespace App\Models;

use Database\Factories\HeroSlideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    /** @use HasFactory<HeroSlideFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'cta_label',
        'cta_url',
        'display_order',
        'is_active',
        'publish_from',
        'publish_to',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'publish_from' => 'datetime',
            'publish_to' => 'datetime',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderByDesc('id');
    }

    public function scopeActiveNow($query)
    {
        return $query->where('is_active', true)
            ->where(function ($subQuery) {
                $subQuery->whereNull('publish_from')
                    ->orWhere('publish_from', '<=', now());
            })
            ->where(function ($subQuery) {
                $subQuery->whereNull('publish_to')
                    ->orWhere('publish_to', '>=', now());
            });
    }
}
