<?php

namespace App\Models;

use Database\Factories\CompetitionLevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompetitionLevel extends Model
{
    /** @use HasFactory<CompetitionLevelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'display_order',
    ];

    public function competitions(): HasMany
    {
        return $this->hasMany(Competition::class, 'level_id');
    }
}
