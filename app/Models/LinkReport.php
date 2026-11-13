<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Zgłoszenie, że link do filmu albo transmisji nie działa. Administrator odpina link albo uznaje, że działa.
 */
class LinkReport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_REMOVED = 'removed';

    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = ['reportable_type', 'reportable_id', 'url', 'user_id', 'status', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    /**
     * Mecz ligowy, turniejowy albo turniej minisiatkówki, przy którym jest link.
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }
}
