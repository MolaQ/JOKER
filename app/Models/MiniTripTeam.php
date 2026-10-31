<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Drużyna wystawiona na wyjazd (np. „Joker 1” w czwórkach) z wynikiem: miejsce na ile drużyn.
 */
class MiniTripTeam extends Model
{
    protected $fillable = [
        'mini_trip_id',
        'mini_group_id',
        'name',
        'place',
        'teams_count',
        'note',
        'photo_path',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(MiniTrip::class, 'mini_trip_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(MiniGroup::class, 'mini_group_id');
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(MiniPlayer::class)->orderBy('last_name')->orderBy('first_name');
    }

    /**
     * Liczba rywali: „10 drużyn”, a w singlach (1 gracz na boisku) „15 zawodników”.
     */
    public function fieldLabel(): ?string
    {
        if (! $this->teams_count) {
            return null;
        }

        $n = (int) $this->teams_count;
        $few = $n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20);
        [$one, $fewWord, $many] = (int) $this->group?->players_on_court === 1 ? ['zawodnik', 'zawodników', 'zawodników'] : ['drużyna', 'drużyny', 'drużyn'];

        return $n.' '.__($n === 1 ? $one : ($few ? $fewWord : $many));
    }

    public function photoUrl(): ?string
    {
        return Media::url($this->photo_path);
    }

    /**
     * „2. miejsce na 18 drużyn”.
     */
    public function resultLabel(): ?string
    {
        if (! $this->place) {
            return null;
        }

        return $this->teams_count
            ? __(':place. miejsce na :count drużyn', ['place' => $this->place, 'count' => $this->teams_count])
            : __(':place. miejsce', ['place' => $this->place]);
    }
}
