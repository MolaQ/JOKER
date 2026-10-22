<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Dziennik zmian: kto, kiedy i komu zmienił role, uprawnienia albo aktywował konto.
 */
class AuditLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'description', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public static function record(string $action, string $description, ?Model $subject = null, array $details = []): self
    {
        return static::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'details' => $details ?: null,
        ]);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
