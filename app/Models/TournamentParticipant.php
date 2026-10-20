<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Drużyna w kategorii turnieju: rywal z bazy rywali albo nasza drużyna, z opcjonalnym składem.
 */
class TournamentParticipant extends Model
{
    protected $fillable = [
        'tournament_category_id',
        'tournament_group_id',
        'competitor_type',
        'competitor_id',
        'display_name',
        'roster',
        'final_position',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'tournament_group_id');
    }

    public function competitor(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOwnTeam(): bool
    {
        return $this->competitor_type === 'team';
    }

    public function name(): string
    {
        if (filled($this->display_name)) {
            return $this->display_name;
        }

        return match (true) {
            $this->competitor instanceof RivalTeam => $this->competitor->full_name,
            $this->competitor instanceof Team => 'LOMS Joker Piła ('.mb_strtolower($this->competitor->name).')',
            default => '—',
        };
    }

    public function logoPath(): ?string
    {
        return $this->competitor instanceof RivalTeam ? $this->competitor->logoPath() : $this->competitor?->logo_path;
    }

    /**
     * Skład jako lista (jedna osoba w linii, np. „7 Jan Nowak”).
     *
     * @return array<int, string>
     */
    public function rosterLines(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->roster))));
    }
}
