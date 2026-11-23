<?php

namespace App\Models;

use App\Permission;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Propozycja treści od rodzica, zawodnika albo współpracownika: artykuł, zdjęcia z meczu, transmisja albo logo rywala.
 */
class ContentProposal extends Model
{
    public const TYPE_ARTICLE = 'article';

    public const TYPE_PHOTOS = 'photos';

    public const TYPE_STREAM = 'stream';

    public const TYPE_LOGO = 'logo';

    public const TYPES = [
        self::TYPE_ARTICLE => 'Artykuł',
        self::TYPE_PHOTOS => 'Zdjęcia',
        self::TYPE_STREAM => 'Transmisja',
        self::TYPE_LOGO => 'Logo rywala',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'type',
        'match_type',
        'match_id',
        'title',
        'excerpt',
        'content',
        'url',
        'files',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'files' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Pliki odrzuconej lub usuniętej propozycji nie są już potrzebne.
        static::deleting(fn (ContentProposal $proposal) => $proposal->deleteFiles());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Czego dotyczy propozycja: meczu ligowego (Game), turniejowego (TournamentMatch), wyjazdu minisiatkówki (MiniTrip),
     * a przy logo drużyny rywali (RivalTeam).
     */
    public function match(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function deleteFiles(): void
    {
        collect($this->files ?? [])->each(fn (string $path) => Storage::disk('public')->delete($path));
    }

    /**
     * Rodzic proponuje artykuły, zdjęcia i transmisje, zawodnik zdjęcia i transmisje,
     * a konto z uprawnieniem „Zgłaszanie relacji, transmisji i logotypów” relacje, transmisje i logotypy rywali.
     *
     * @return list<string>
     */
    public static function typesFor(?User $user): array
    {
        if (! $user?->is_active) {
            return [];
        }

        $types = match (true) {
            $user->hasRole(UserRole::Parent) => [self::TYPE_ARTICLE, self::TYPE_PHOTOS, self::TYPE_STREAM],
            $user->hasRole(UserRole::Player) => [self::TYPE_PHOTOS, self::TYPE_STREAM],
            default => [],
        };

        if ($user->hasPermission(Permission::Contributions) && ! $user->isAdmin()) {
            $types = [...$types, self::TYPE_ARTICLE, self::TYPE_STREAM, self::TYPE_LOGO];
        }

        return array_values(array_intersect(array_keys(self::TYPES), $types));
    }
}
