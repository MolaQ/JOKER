<?php

namespace App\Models;

use App\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jedna rola jednego konta (konto może mieć ich kilka).
 */
class RoleAssignment extends Model
{
    protected $table = 'role_user';

    protected $primaryKey = null;

    public $incrementing = false;

    protected $fillable = ['user_id', 'role'];

    protected function casts(): array
    {
        return ['role' => UserRole::class];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
