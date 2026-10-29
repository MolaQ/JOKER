<?php

namespace App\Models;

use App\Models\Concerns\HasReadableRouteKey;
use App\Services\ScheduleImporter;
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

    protected function routeKeySeparator(): string
    {
        return '-';
    }

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
        'withdrawn_teams',
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
            'withdrawn_teams' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /** Dopisek przy meczach odwołanych z powodu wycofania rywala (po nim poznajemy, co można przywrócić). */
    public const WITHDRAWN_NOTE = 'rywal wycofał się z rozgrywek';

    /**
     * Czy drużyna o tej nazwie wycofała się z tych rozgrywek (porównanie bez wielkości liter i znaków).
     */
    public function hasWithdrawn(?string ...$names): bool
    {
        $withdrawn = collect($this->withdrawn_teams ?? [])->map(fn (string $name) => ScheduleImporter::key($name))->filter();

        return collect($names)->filter()->contains(fn (string $name) => $withdrawn->contains(ScheduleImporter::key($name)));
    }

    /**
     * Odwołuje nierozegrane mecze z wycofanymi drużynami, a mecze z drużynami skreślonymi z listy przywraca.
     * Rozegranych meczów (także walkowerów z wynikiem) nie zmienia.
     *
     * @return int liczba zmienionych meczów
     */
    public function applyWithdrawals(): int
    {
        $changed = 0;

        foreach ($this->games()->with('opponentTeam')->where('status', '!=', 'finished')->get() as $game) {
            $withdrawn = $this->hasWithdrawn($game->opponent, $game->opponentTeam?->full_name, ...($game->opponentTeam?->aliases ?? []));

            if ($withdrawn && $game->status !== 'cancelled') {
                $game->update(['status' => 'cancelled', 'result_note' => self::WITHDRAWN_NOTE]);
                $changed++;
            } elseif (! $withdrawn && $game->status === 'cancelled' && $game->result_note === self::WITHDRAWN_NOTE) {
                $game->update(['status' => 'scheduled', 'result_note' => null]);
                $changed++;
            }
        }

        return $changed;
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
     * Jak podpisać naszą drużynę w tabelach i kalendarzu tych rozgrywek: nazwa klubu ustawiona
     * w panelu (np. „LOMS Joker Piła”, domyślnie „Joker Piła”) i plakietka z nazwą drużyny (np. „Kadeci II”).
     *
     * @return array{name: string, badge: ?string}
     */
    public function teamLabel(?Team $team): array
    {
        $pivot = $team ? $this->teams->firstWhere('id', $team->id)?->pivot : null;

        return ['name' => filled($pivot?->display_name) ? $pivot->display_name : 'Joker Piła', 'badge' => $team?->name];
    }

    /**
     * Domyślny podpis drużyny w nowych rozgrywkach: „LOMS Joker Piła”, a drużyny ASBL „KS ASBL Piła”.
     * Bez dopisku w nawiasie - którą drużynę widać, mówi plakietka.
     *
     * @return array{display_name: string, display_suffix: ?string}
     */
    public static function defaultTeamLabel(string $teamName, string $teamSlug, ?string $externalName): array
    {
        return [
            'display_name' => str_contains(mb_strtoupper((string) $externalName), 'ASBL') ? 'KS ASBL Piła' : 'LOMS Joker Piła',
            'display_suffix' => null,
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

    /**
     * Przenosi rozgrywki z poprzedniego sezonu do nowego: nazwy, poziomy, punktacja, drużyny z ich podpisami
     * i adresy stron związku, w których rok sezonu (np. „26-27”, „2026/2027”) zmienia się na nowy.
     * Rozgrywek, które w nowym sezonie już są (ta sama nazwa), nie dubluje.
     *
     * @return int liczba skopiowanych rozgrywek
     */
    public static function copyToSeason(Season $from, Season $to): int
    {
        $fromYear = (int) $from->start_date->format('Y');
        $toYear = (int) $to->start_date->format('Y');
        $years = [
            $fromYear.'/'.($fromYear + 1) => $toYear.'/'.($toYear + 1),
            $fromYear.'-'.($fromYear + 1) => $toYear.'-'.($toYear + 1),
            substr((string) $fromYear, 2).'-'.substr((string) ($fromYear + 1), 2) => substr((string) $toYear, 2).'-'.substr((string) ($toYear + 1), 2),
        ];
        $existing = self::where('season_id', $to->id)->pluck('name');
        $copied = 0;

        foreach (self::with('teams')->where('season_id', $from->id)->orderBy('display_order')->get() as $competition) {
            if ($existing->contains($competition->name)) {
                continue;
            }

            $copy = $competition->replicate(['synced_at', 'sync_message']);
            $copy->season_id = $to->id;
            $copy->source_url = $competition->source_url ? strtr($competition->source_url, $years) : null;
            $copy->description = $competition->description ? str_replace($from->name, $to->name, $competition->description) : null;
            $copy->save();

            $copy->teams()->sync($competition->teams->mapWithKeys(fn (Team $team) => [$team->id => [
                'external_name' => $team->pivot->external_name,
                'display_name' => $team->pivot->display_name,
                'display_suffix' => $team->pivot->display_suffix,
            ]])->all());
            $copied++;
        }

        return $copied;
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
