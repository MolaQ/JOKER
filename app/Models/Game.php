<?php

namespace App\Models;

use App\Models\Concerns\Likeable;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory, Likeable;

    protected $fillable = [
        'team_id',
        'season_id',
        'competition_id',
        'opponent',
        'opponent_team_id',
        'is_home',
        'game_date',
        'venue',
        'league',
        'status',
        'home_score',
        'away_score',
        'home_points',
        'away_points',
        'sets_score',
        'match_report',
        'video_url',
    ];

    protected function casts(): array
    {
        return [
            'game_date' => 'datetime',
            'is_home' => 'boolean',
            'sets_score' => 'array',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    public function opponentTeam(): BelongsTo
    {
        return $this->belongsTo(RivalTeam::class, 'opponent_team_id');
    }

    /**
     * Nazwa przeciwnika do wyświetlenia: preferuje powiązaną drużynę rywala,
     * w przeciwnym razie wolny tekst wpisany ręcznie.
     */
    public function opponentDisplayName(): string
    {
        return $this->opponentTeam?->full_name ?: $this->opponent;
    }

    public function opponentShortName(): string
    {
        return $this->opponentTeam?->displayName() ?: $this->opponent;
    }

    /**
     * Liczba setów wygranych/przegranych przez naszą drużynę w tym meczu.
     *
     * @return array{0: int, 1: int}|null
     */
    public function ourSetsResult(): ?array
    {
        if (is_null($this->home_score) || is_null($this->away_score)) {
            return null;
        }

        return $this->is_home
            ? [$this->home_score, $this->away_score]
            : [$this->away_score, $this->home_score];
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GameComment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GamePhoto::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->hasMany(GameComment::class)->where('is_approved', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('game_date', '>', now())
            ->where('status', 'scheduled')
            ->orderBy('game_date');
    }

    public function scopeFinished($query)
    {
        return $query->where('status', 'finished')
            ->orderBy('game_date', 'desc');
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function isUpcoming(): bool
    {
        return $this->status === 'scheduled' && $this->game_date > now();
    }
}
