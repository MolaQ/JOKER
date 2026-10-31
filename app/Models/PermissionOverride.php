<?php

namespace App\Models;

use App\Permission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uprawnienie nadane albo odebrane jednemu kontu niezależnie od jego ról.
 */
class PermissionOverride extends Model
{
    protected $table = 'permission_user';

    protected $fillable = ['user_id', 'permission', 'allowed', 'expires_at'];

    protected function casts(): array
    {
        return ['permission' => Permission::class, 'allowed' => 'boolean', 'expires_at' => 'datetime'];
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
