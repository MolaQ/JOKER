<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kopia wyników kategorii zrobiona przed wyczyszczeniem, zob. App\Support\TournamentResults.
 */
class TournamentResultBackup extends Model
{
    protected $fillable = [
        'tournament_category_id',
        'results_count',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TournamentCategory::class, 'tournament_category_id');
    }
}
