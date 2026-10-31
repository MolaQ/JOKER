<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stan ról, wyjątków w uprawnieniach i koordynowanych drużyn konta sprzed zmiany (do „Przywróć ten stan”).
 */
class AccessSnapshot extends Model
{
    protected $fillable = ['user_id', 'actor_id', 'note', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
