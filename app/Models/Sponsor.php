<?php

namespace App\Models;

use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory;

    /**
     * Poziomy sponsoringu od najważniejszego: nazwa w panelu i podpis rzędu logotypów na stronie.
     * Na stronie głównej im wyższy poziom, tym większe logo (jak na stronach klubów PlusLigi).
     *
     * @var array<string, array{admin: string, label: string}>
     */
    public const TIERS = [
        'platinum' => ['admin' => 'Platynowy', 'label' => 'Sponsor główny'],
        'gold' => ['admin' => 'Złoty', 'label' => 'Sponsorzy'],
        'silver' => ['admin' => 'Srebrny', 'label' => 'Partnerzy'],
        'bronze' => ['admin' => 'Brązowy', 'label' => 'Przyjaciele klubu'],
    ];

    protected $fillable = [
        'name',
        'description',
        'logo_path',
        'website_url',
        'tier',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
