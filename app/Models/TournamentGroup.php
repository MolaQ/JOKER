<?php

namespace App\Models;

use App\Support\TournamentTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class TournamentGroup extends Model
{
    protected $fillable = [
        'tournament_category_id',
        'name',
        'display_order',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(TournamentParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Tabela grupy liczona z rozegranych meczów.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function table(): Collection
    {
        return TournamentTable::for($this->participants, $this->matches);
    }
}
