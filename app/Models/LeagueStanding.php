<?php

namespace App\Models;

use Database\Factories\LeagueStandingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LeagueStanding extends Model
{
    /** @use HasFactory<LeagueStandingFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'competitor_type',
        'competitor_id',
        'season_id',
        'competition_id',
        'position',
        'played',
        'won',
        'lost',
        'sets_won',
        'sets_lost',
        'points_for',
        'points_against',
        'points',
        'is_manual_override',
    ];

    protected function casts(): array
    {
        return [
            'is_manual_override' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Drużyna (nasza lub rywala), której dotyczy wpis w tabeli.
     */
    public function competitor(): MorphTo
    {
        return $this->morphTo();
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function competitorName(): string
    {
        if ($this->competitor instanceof RivalTeam) {
            return $this->competitor->full_name;
        }

        return $this->competitor?->name ?? '—';
    }

    public function competitorShortName(): string
    {
        if ($this->competitor instanceof RivalTeam) {
            return $this->competitor->displayName();
        }

        return $this->competitor?->name ?? '—';
    }

    public function competitorLogoPath(): ?string
    {
        return $this->competitor?->logo_path;
    }

    public function isOwnTeam(): bool
    {
        return $this->competitor_type === 'team';
    }

    public function setsRatio(): string
    {
        return "{$this->sets_won}:{$this->sets_lost}";
    }

    public function pointsRatio(): string
    {
        return "{$this->points_for}:{$this->points_against}";
    }
}
