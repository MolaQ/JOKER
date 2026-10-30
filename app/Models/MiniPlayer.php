<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Dziecko w minisiatkówce. Zdjęcie pokazujemy tylko ze zgodą rodzica na wizerunek.
 */
class MiniPlayer extends Model
{
    protected $fillable = [
        'mini_group_id',
        'first_name',
        'last_name',
        'birth_year',
        'photo_path',
        'image_consent',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['image_consent' => 'boolean', 'is_active' => 'boolean'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MiniGroup::class, 'mini_group_id');
    }

    public function tripTeams(): BelongsToMany
    {
        return $this->belongsToMany(MiniTripTeam::class);
    }

    public function name(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Zdjęcie na stronie: tylko gdy rodzic zgodził się na publikację wizerunku.
     */
    public function photoUrl(): ?string
    {
        return $this->image_consent ? Media::url($this->photo_path) : null;
    }
}
