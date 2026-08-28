<?php

namespace App\Models;

use Database\Factories\GamePhotoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GamePhoto extends Model
{
    /** @use HasFactory<GamePhotoFactory> */
    use HasFactory;

    protected $fillable = [
        'game_id',
        'uploaded_by',
        'photo_path',
        'caption',
        'display_order',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
