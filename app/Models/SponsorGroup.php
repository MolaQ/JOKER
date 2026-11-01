<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Kategoria w bloku „Wspierają nas” (np. Instytucje i samorządy, Sponsorzy).
 */
class SponsorGroup extends Model
{
    /** Wysokość kafelków logotypów w kategorii. */
    public const SIZES = [
        'xl' => 'duże',
        'lg' => 'średnie',
        'md' => 'standardowe',
    ];

    protected $fillable = ['name', 'size', 'display_order'];

    public function sponsors(): HasMany
    {
        return $this->hasMany(Sponsor::class)->orderBy('display_order')->orderBy('name');
    }

    /**
     * Kategorie z widocznymi logotypami, do bloku nad stopką i paska na ekranie TV.
     *
     * @return Collection<int, SponsorGroup>
     */
    public static function forBlock(): Collection
    {
        return once(fn () => self::query()
            ->with(['sponsors' => fn ($sponsors) => $sponsors->where('is_active', true)->whereNotNull('logo_path')])
            ->orderBy('display_order')->orderBy('id')
            ->get()
            ->filter(fn (SponsorGroup $group) => $group->sponsors->isNotEmpty())
            ->values());
    }
}
