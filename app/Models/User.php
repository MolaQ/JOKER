<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar_path',
        'instagram',
        'additional_info',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTrainer(): bool
    {
        return $this->role === UserRole::Trainer;
    }

    public function canManageContent(): bool
    {
        return $this->role->canManageContent();
    }

    public function canComment(): bool
    {
        return $this->role->canComment();
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function profileChangeRequests(): HasMany
    {
        return $this->hasMany(ProfileChangeRequest::class);
    }

    /**
     * Drużyny, w których użytkownik jest trenerem głównym.
     */
    public function headCoachTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'trainer_id');
    }

    /**
     * Drużyny, w których użytkownik jest trenerem pomocniczym.
     */
    public function assistantTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_trainers')->withTimestamps();
    }

    /**
     * Wszystkie drużyny prowadzone przez użytkownika (główny + pomocniczy), bez duplikatów.
     *
     * @return Collection<int, Team>
     */
    public function allCoachedTeams()
    {
        return $this->headCoachTeams->merge($this->assistantTeams)->unique('id')->values();
    }
}
