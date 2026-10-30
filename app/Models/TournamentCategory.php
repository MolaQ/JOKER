<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Kategoria wiekowa turnieju (np. młodzicy, kadeci, juniorzy) - każda ma własne drużyny, grupy i mecze.
 */
class TournamentCategory extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'display_order',
        'match_interval',
        'schedule_days',
    ];

    protected function casts(): array
    {
        return [
            'schedule_days' => 'array',
        ];
    }

    /**
     * Część adresu kategorii: /tournaments/1/memorial-2026/kadeci.
     */
    public function slug(): string
    {
        return Str::slug($this->name) ?: (string) $this->id;
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(TournamentGroup::class)->orderBy('display_order')->orderBy('name');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class)->orderBy('starts_at')->orderBy('id');
    }
}
