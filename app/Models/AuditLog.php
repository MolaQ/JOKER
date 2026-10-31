<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Dziennik zmian: kto, kiedy i co zmienił: role, uprawnienia, konta i treści strony (App\Support\ChangeLog).
 * Wpisy starsze niż rok usuwa codzienne model:prune.
 */
class AuditLog extends Model
{
    use Prunable;

    public const RETENTION_DAYS = 366;

    /** Grupy wpisów do filtra w panelu: nazwa i początki nazw akcji, np. „roles.” dla roles.changed. */
    public const GROUPS = [
        'role' => ['Role kont', ['roles']],
        'uprawnienia' => ['Uprawnienia', ['permissions']],
        'konta' => ['Aktywacja, blokada i zgody kont', ['account', 'consent']],
        'zatwierdzenia' => ['Zatwierdzone zgłoszenia', ['proposal', 'tag']],
    ];

    protected $fillable = ['actor_id', 'action', 'subject_type', 'subject_id', 'description', 'details'];

    protected function casts(): array
    {
        return ['details' => 'array'];
    }

    public static function record(string $action, string $description, ?Model $subject = null, array $details = []): self
    {
        // Kolumna subject_id jest liczbowa; rekordy z kluczem tekstowym (np. ustawienia strony) zapisują go w szczegółach,
        // bo MySQL odrzuca tekst w kolumnie liczbowej.
        $key = $subject?->getKey();

        if ($key !== null && ! is_int($key) && ! ctype_digit((string) $key)) {
            $details = ['klucz' => (string) $key] + $details;
            $key = null;
        }

        return static::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $key,
            'description' => $description,
            'details' => $details ?: null,
        ]);
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(self::RETENTION_DAYS));
    }

    /**
     * Zmienione pola jako ['pole' => [przed, po]].
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function changes(): array
    {
        return is_array($this->details['zmiany'] ?? null) ? $this->details['zmiany'] : [];
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
