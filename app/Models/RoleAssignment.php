<?php

namespace App\Models;

use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedna rola jednego konta (konto może mieć ich kilka), bezterminowo albo do daty „ważne do”.
 */
class RoleAssignment extends Model
{
    protected $table = 'role_user';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = ['user_id', 'role', 'expires_at'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'expires_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Bez daty „ważne do” albo z datą jeszcze nieminiętą.
     */
    public function scopeActive(Builder $query): void
    {
        $query->where(fn (Builder $query) => $query->whereNull($query->qualifyColumn('expires_at'))->orWhere($query->qualifyColumn('expires_at'), '>', now()));
    }

    public function scopeExpired(Builder $query): void
    {
        $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
    }
}
