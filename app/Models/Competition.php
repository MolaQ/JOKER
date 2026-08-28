<?php

namespace App\Models;

use Database\Factories\CompetitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    /** @use HasFactory<CompetitionFactory> */
    use HasFactory;

    /**
     * Domyślna kolejność kryteriów sortowania tabeli, gdy rozgrywki nie mają
     * własnej konfiguracji.
     *
     * @var array<int, string>
     */
    public const DEFAULT_STANDINGS_CRITERIA = ['points', 'sets_ratio', 'points_ratio'];

    protected $fillable = [
        'name',
        'level',
        'level_id',
        'season_id',
        'description',
        'display_order',
        'points_win_3_0',
        'points_win_3_1',
        'points_win_3_2',
        'points_loss_2_3',
        'points_loss_1_3',
        'points_loss_0_3',
        'standings_criteria',
    ];

    protected function casts(): array
    {
        return [
            'standings_criteria' => 'array',
        ];
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function competitionLevel(): BelongsTo
    {
        return $this->belongsTo(CompetitionLevel::class, 'level_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'competition_team')->withTimestamps();
    }

    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    public function standings(): HasMany
    {
        return $this->hasMany(LeagueStanding::class)->orderBy('position');
    }

    public function levelName(): ?string
    {
        return $this->competitionLevel?->name ?? $this->level;
    }

    /**
     * @return array<int, string>
     */
    public function sortCriteria(): array
    {
        return $this->standings_criteria ?: self::DEFAULT_STANDINGS_CRITERIA;
    }

    /**
     * Punkty ligowe dla wyniku setowego, np. "3:0" => 3 (zwycięzca).
     */
    public function pointsForSetResult(int $setsWon, int $setsLost): int
    {
        if ($setsWon > $setsLost) {
            return match (true) {
                $setsLost === 0 => $this->points_win_3_0,
                $setsLost === 1 => $this->points_win_3_1,
                default => $this->points_win_3_2,
            };
        }

        return match (true) {
            $setsWon === 2 => $this->points_loss_2_3,
            $setsWon === 1 => $this->points_loss_1_3,
            default => $this->points_loss_0_3,
        };
    }
}
