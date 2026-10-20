<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mecz turnieju: grupowy, w drabince (ćwierćfinał, półfinał…) albo o konkretne miejsce (np. o 7. miejsce).
 */
class TournamentMatch extends Model
{
    public const STAGE_GROUP = 'group';

    public const STAGE_PLAYOFF = 'playoff';

    public const STAGE_PLACEMENT = 'placement';

    /** @var array<string, string> */
    public const STAGES = [
        self::STAGE_GROUP => 'Faza grupowa',
        self::STAGE_PLAYOFF => 'Drabinka (play-off)',
        self::STAGE_PLACEMENT => 'Mecz o miejsce',
    ];

    protected $fillable = [
        'tournament_category_id',
        'tournament_group_id',
        'stage',
        'label',
        'placement',
        'bracket_round',
        'bracket_slot',
        'home_participant_id',
        'away_participant_id',
        'home_placeholder',
        'away_placeholder',
        'starts_at',
        'court',
        'home_sets',
        'away_sets',
        'sets_score',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'sets_score' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(TournamentGroup::class, 'tournament_group_id');
    }

    public function home(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'home_participant_id');
    }

    public function away(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'away_participant_id');
    }

    public function isFinished(): bool
    {
        return $this->home_sets !== null && $this->away_sets !== null && $this->home_sets !== $this->away_sets;
    }

    public function winner(): ?TournamentParticipant
    {
        if (! $this->isFinished()) {
            return null;
        }

        return $this->home_sets > $this->away_sets ? $this->home : $this->away;
    }

    public function loser(): ?TournamentParticipant
    {
        if (! $this->isFinished()) {
            return null;
        }

        return $this->home_sets > $this->away_sets ? $this->away : $this->home;
    }

    public function homeName(): string
    {
        return $this->home?->name() ?? ($this->home_placeholder ?: '?');
    }

    public function awayName(): string
    {
        return $this->away?->name() ?? ($this->away_placeholder ?: '?');
    }

    /**
     * Nazwa meczu: własna („Półfinał 1”), o miejsce („Mecz o 7. miejsce”) albo grupa.
     */
    public function title(): string
    {
        return match (true) {
            filled($this->label) => $this->label,
            $this->stage === self::STAGE_PLACEMENT && $this->placement !== null => $this->placement === 1 ? __('Finał') : __('Mecz o :place. miejsce', ['place' => $this->placement]),
            $this->stage === self::STAGE_PLAYOFF && $this->bracketFrom() > 1 => __('Play-off o miejsca od :place.', ['place' => $this->bracketFrom()]),
            $this->group !== null => $this->group->name,
            default => __(self::STAGES[$this->stage] ?? ''),
        };
    }

    /**
     * Drabinka, do której należy mecz play-off: 1 = główna, 5 = o miejsca 5-8 itd.
     */
    public function bracketFrom(): int
    {
        return $this->stage === self::STAGE_PLAYOFF ? max(1, (int) $this->placement) : 0;
    }

    public function setsLabel(): ?string
    {
        if (! is_array($this->sets_score) || $this->sets_score === []) {
            return null;
        }

        return collect($this->sets_score)->map(fn ($set) => implode(':', (array) $set))->implode(', ');
    }
}
