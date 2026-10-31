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
