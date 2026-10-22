<?php

namespace App\Models;

use App\Permission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uprawnienie nadane albo odebrane jednemu kontu niezależnie od jego ról.
 */
class PermissionOverride extends Model
{
    protected $table = 'permission_user';

    protected $fillable = ['user_id', 'permission', 'allowed'];

    protected function casts(): array
    {
        return ['permission' => Permission::class, 'allowed' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
