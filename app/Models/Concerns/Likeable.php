<?php

namespace App\Models\Concerns;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Polubienia (serduszka) treści: artykułów, meczów, zawodników, drużyn, podstron, turniejów, galerii i profili sztabu.
 * Model, który ma już własne likes() w innym znaczeniu (User: polubienia dane przez konto), nadpisuje likesRelation().
 *
 * @mixin Model
 */
trait Likeable
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Polubienia otrzymane przez ten rekord.
     */
    public function likesRelation(): MorphMany
    {
        return $this->likes();
    }

    public function likesCount(): int
    {
        return $this->likesRelation()->count();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likesRelation()->where('user_id', $user->id)->exists();
    }

    public function toggleLikeFor(User $user): bool
    {
        $like = $this->likesRelation()->where('user_id', $user->id)->first();

        if ($like) {
            $like->delete();

            return false;
        }

        $this->likesRelation()->create(['user_id' => $user->id]);

        return true;
    }
}
