<?php

namespace App\Models;

use Database\Factories\RivalClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Klub rywali: grupuje jego drużyny (roczniki, sekcje, zespoły „1” i „2”) i daje im wspólne logo.
 */
class RivalClub extends Model
{
    /** @use HasFactory<RivalClubFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'logo_path',
    ];

    public function teams(): HasMany
    {
        return $this->hasMany(RivalTeam::class)->orderBy('full_name');
    }
}
