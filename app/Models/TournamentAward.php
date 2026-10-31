<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Wyróżnienie indywidualne w kategorii turnieju (MVP, najlepsza atakująca…).
 * Gdy dostaje je nasz zawodnik, trafia też do jego osiągnięć w profilu (PlayerAchievement z tournament_award_id).
 */
class TournamentAward extends Model
{
    protected $fillable = [
        'tournament_category_id',
        'name',
        'player_id',
        'person_name',
        'tournament_participant_id',
        'display_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'tournament_participant_id');
    }

    public function achievement(): HasOne
    {
        return $this->hasOne(PlayerAchievement::class);
    }

    public function personLabel(): string
    {
        return $this->player?->full_name ?? (string) $this->person_name;
    }

    public function teamLabel(): ?string
    {
        return $this->participant?->name();
    }

    /**
     * Wpis w osiągnięciach zawodnika: „MVP turnieju · XXV Memoriał… (kadetki)”, sezon jak w klubie.
     */
    public function syncAchievement(): void
    {
        $this->achievement()->delete();

        if (! $this->player_id) {
            return;
        }

        $category = $this->category()->with('tournament.season')->first();
        $tournament = $category->tournament;
        $start = $tournament->start_date;
        $season = $tournament->season?->name ?? ($start ? ($start->month >= 8 ? $start->year.'/'.substr((string) ($start->year + 1), 2) : ($start->year - 1).'/'.substr((string) $start->year, 2)) : null);

        PlayerAchievement::create([
            'player_id' => $this->player_id,
            'tournament_award_id' => $this->id,
            'season' => $season,
            'title' => $this->name.' · '.$tournament->fullName().($tournament->categories()->count() > 1 ? ' ('.mb_strtolower($category->name).')' : ''),
            'display_order' => 0,
        ]);
    }
}
