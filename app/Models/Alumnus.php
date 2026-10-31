<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wychowanek klubu, który gra wyżej: w PlusLidze, w reprezentacji albo w innych rozgrywkach centralnych.
 */
class Alumnus extends Model
{
    protected $table = 'alumni';

    /** Podpowiedzi poziomu w panelu (można wpisać inny). */
    public const LEVELS = ['Reprezentacja Polski', 'PlusLiga', 'Tauron 1. Liga', '2. liga', 'Liga zagraniczna', 'Trener'];

    protected $fillable = [
        'name',
        'photo_path',
        'position',
        'years_in_club',
        'current_club',
        'level',
        'achievements',
        'profile_url',
        'player_id',
        'is_featured',
        'is_published',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true)->orderByDesc('is_featured')->orderBy('display_order')->orderBy('name');
    }

    public function photoUrl(): ?string
    {
        return Media::url($this->photo_path) ?? Media::url($this->player?->photo_path);
    }

    /**
     * Osiągnięcia: każda linia osobno.
     *
     * @return list<string>
     */
    public function achievementList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $this->achievements))));
    }

    public function link(): ?string
    {
        return $this->player ? route('players.show', $this->player) : ($this->profile_url ?: null);
    }
}
