<?php

namespace App\Models;

use App\Models\Concerns\HasReadableRouteKey;
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

    use HasReadableRouteKey;

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
        'source_url',
        'synced_at',
        'sync_message',
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
            'synced_at' => 'datetime',
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
        return $this->belongsToMany(Team::class, 'competition_team')
            ->withPivot(['external_name', 'display_name', 'display_suffix'])
            ->withTimestamps();
    }

    /**
     * Jak podpisać naszą drużynę w tabelach i kalendarzu tych rozgrywek: nazwa ustawiona
     * w panelu (np. „LOMS Joker Piła”) i opcjonalny dopisek kursywą (np. „(kadeci)”).
     *
     * @return array{name: string, suffix: ?string}
     */
    public function teamLabel(?Team $team): array
    {
        $pivot = $team ? $this->teams->firstWhere('id', $team->id)?->pivot : null;

        if (filled($pivot?->display_name)) {
            return ['name' => $pivot->display_name, 'suffix' => filled($pivot->display_suffix) ? $pivot->display_suffix : null];
        }

        return ['name' => $team?->name ?? 'Joker Piła', 'suffix' => filled($pivot?->display_suffix) ? $pivot->display_suffix : null];
    }

    /**
     * Domyślny podpis drużyny w nowych rozgrywkach: seniorzy jako „LOMS Joker Piła”,
     * młodzież z dopiskiem rocznika, np. „(kadeci II)”; drużyny ASBL jako „KS ASBL Piła”.
     *
     * @return array{display_name: string, display_suffix: ?string}
     */
    public static function defaultTeamLabel(string $teamName, string $teamSlug, ?string $externalName): array
    {
        return [
            'display_name' => str_contains(mb_strtoupper((string) $externalName), 'ASBL') ? 'KS ASBL Piła' : 'LOMS Joker Piła',
            'display_suffix' => $teamSlug === 'seniorzy' ? null : '('.mb_strtolower(mb_substr($teamName, 0, 1)).mb_substr($teamName, 1).')',
        ];
    }

    /**
     * Adresy stron z terminarzem (jeden na linię).
     *
     * @return array<int, string>
     */
    public function sourceUrls(): array
    {
        return collect(preg_split('/\s+/', (string) $this->source_url))
            ->filter(fn (string $url) => str_starts_with($url, 'http'))
            ->values()
            ->all();
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

    protected function routeSlugSource(): string
    {
        return (string) $this->name;
    }
}
