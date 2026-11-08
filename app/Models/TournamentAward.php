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

    protected static function booted(): void
    {
        // Usunięte wyróżnienie znika też z profilu zawodnika.
        static::deleting(function (self $award) {
            $award->achievement()->delete();
            PlayerAward::where('tournament_award_id', $award->id)->delete();
        });
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

        $this->syncPlayerAward();
    }

    /**
     * Kopia w nagrodach indywidualnych zawodnika (do edycji w panelu zawodnika). Istniejącej kopii tego samego
     * zawodnika nie nadpisujemy, żeby zostały poprawki z panelu; nowa trafia na górę listy.
     */
    public function syncPlayerAward(): void
    {
        PlayerAward::where('tournament_award_id', $this->id)->where('player_id', '!=', (int) $this->player_id)->delete();

        if (! $this->player_id || PlayerAward::where('tournament_award_id', $this->id)->exists()) {
            return;
        }

        $category = $this->category()->with('tournament')->first();
        $tournament = $category->tournament;

        // Nowa nagroda na górę: pozostałe o jedno miejsce niżej (kolejność bez liczb ujemnych, kolumna jest bez znaku).
        PlayerAward::where('player_id', $this->player_id)->increment('display_order');

        PlayerAward::create([
            'player_id' => $this->player_id,
            'tournament_award_id' => $this->id,
            'name' => mb_substr($this->name, 0, 150),
            'event' => $tournament->fullName(),
            'awarded_on' => $tournament->lastDay(),
            'place' => $tournament->awardPlace(),
            'note' => mb_substr(collect([$tournament->categories()->count() > 1 ? $category->name : null, $this->teamLabel()])->filter()->implode(' · '), 0, 200) ?: null,
            'display_order' => 0,
        ]);
    }
}
