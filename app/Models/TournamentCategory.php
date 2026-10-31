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
        'color',
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

    /** @var list<string> Kolory domyślne kolejnych kategorii. */
    public const COLORS = ['#45a0e0', '#f59e0b', '#10b981', '#8b5cf6', '#f43f5e', '#84cc16', '#06b6d4', '#ec4899'];

    /**
     * Kolor kategorii: ustawiony w panelu albo kolejny z palety według kolejności kategorii w turnieju.
     */
    public function color(): string
    {
        if ($this->color) {
            return $this->color;
        }

        $index = $this->tournament->categories()->orderBy('display_order')->orderBy('id')->pluck('id')->search($this->id);

        return self::COLORS[(int) $index % count(self::COLORS)];
    }

    public function awards(): HasMany
    {
        return $this->hasMany(TournamentAward::class)->orderBy('display_order')->orderBy('id');
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

    public function resultBackups(): HasMany
    {
        return $this->hasMany(TournamentResultBackup::class)->latest('id');
    }
}
