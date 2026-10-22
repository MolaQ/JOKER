<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Oznaczenie na zdjęciu: drużyna, zawodnik albo osoba ze sztabu (link do profilu).
 * Oznaczenia od rodziców i zawodników czekają na zatwierdzenie.
 */
class PhotoTag extends Model
{
    public const STATUS_APPROVED = 'approved';

    public const STATUS_PENDING = 'pending';

    protected $fillable = ['gallery_photo_id', 'taggable_type', 'taggable_id', 'status', 'proposed_by', 'reviewed_by'];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(GalleryPhoto::class, 'gallery_photo_id');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Zawodnika bez zgody na wizerunek nie pokazujemy z nazwiska.
     */
    public function isVisible(): bool
    {
        return match (true) {
            $this->taggable instanceof Player => $this->taggable->image_consent,
            default => $this->taggable !== null,
        };
    }

    public function label(): string
    {
        return match (true) {
            $this->taggable instanceof Player => $this->taggable->full_name,
            $this->taggable instanceof Team, $this->taggable instanceof User => (string) $this->taggable->name,
            default => '',
        };
    }

    public function url(): ?string
    {
        return match (true) {
            $this->taggable instanceof Player => route('players.show', $this->taggable),
            $this->taggable instanceof Team => route('teams.show', $this->taggable),
            $this->taggable instanceof User && $this->taggable->isStaffMember() => route('trainers.show', $this->taggable),
            default => null,
        };
    }

    /**
     * Zatwierdza redaktor, szef, administrator albo trener drużyny oznaczonego zawodnika.
     */
    public function isReviewableBy(User $user): bool
    {
        if ($user->can('proposals.content')) {
            return true;
        }

        return $this->taggable instanceof Player && $this->taggable->isReviewableBy($user);
    }
}
