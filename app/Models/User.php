<?php

namespace App\Models;

use App\BirthdayDisplay;
use App\Models\Concerns\CountsViews;
use App\Models\Concerns\HasReadableRouteKey;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Permission;
use App\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class User extends Authenticatable
{
    use CountsViews;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasReadableRouteKey;

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
        'birth_date',
        'birthday_display',
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
            'birth_date' => 'date',
            'birthday_display' => BirthdayDisplay::class,
        ];
    }

    /**
     * Role do zapisania po zapisie konta (ustawiane przez pole "role").
     *
     * @var list<UserRole>|null
     */
    protected ?array $pendingRoles = null;

    /** @var Collection<int, Permission>|null */
    protected ?Collection $resolvedPermissions = null;

    /**
     * Zapisuje role ustawione polem "role". Bez zdarzeń modelu, bo seedery je wyłączają.
     */
    public function save(array $options = []): bool
    {
        $saved = parent::save($options);

        if ($saved && $this->pendingRoles !== null) {
            $roles = $this->pendingRoles;
            $this->pendingRoles = null;
            $this->syncRoles($roles);
        }

        return $saved;
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(PermissionOverride::class);
    }

    /**
     * Role konta od najważniejszej.
     *
     * @return Collection<int, UserRole>
     */
    public function roles(): Collection
    {
        $order = array_flip(array_column(UserRole::cases(), 'value'));

        return $this->roleAssignments
            ->map(fn (RoleAssignment $assignment) => $assignment->role)
            ->sortBy(fn (UserRole $role) => $order[$role->value])
            ->values();
    }

    /**
     * Główna (najważniejsza) rola konta. Przypisanie zastępuje wszystkie role konta,
     * np. User::create(['role' => UserRole::Trainer]) albo ['role' => [Admin, Parent]].
     */
    protected function role(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->roles()->first(),
            set: function ($value) {
                $this->pendingRoles = collect(is_array($value) ? $value : [$value])
                    ->filter()
                    ->map(fn ($role) => $role instanceof UserRole ? $role : UserRole::from($role))
                    ->all();

                return [];
            },
        );
    }

    /**
     * @param  iterable<UserRole|string>  $roles
     */
    public function syncRoles(iterable $roles): void
    {
        $new = collect($roles)->map(fn ($role) => $role instanceof UserRole ? $role : UserRole::from($role))->unique(fn (UserRole $role) => $role->value);
        $old = $this->roles();

        $added = $new->reject(fn (UserRole $role) => $old->contains($role));
        $removed = $old->reject(fn (UserRole $role) => $new->contains($role));

        if ($added->isEmpty() && $removed->isEmpty()) {
            return;
        }

        RoleAssignment::where('user_id', $this->id)->whereIn('role', $removed->map->value->all())->delete();
        $added->each(fn (UserRole $role) => RoleAssignment::create(['user_id' => $this->id, 'role' => $role]));

        $this->unsetRelation('roleAssignments');
        $this->resolvedPermissions = null;

        if (Auth::check()) {
            AuditLog::record('roles.changed', 'Zmiana ról konta '.$this->name, $this, [
                'dodane' => $added->map->label()->values()->all(),
                'odebrane' => $removed->map->label()->values()->all(),
            ]);
        }
    }

    public function assignRole(UserRole $role): void
    {
        $this->syncRoles($this->roles()->push($role));
    }

    public function removeRole(UserRole $role): void
    {
        $this->syncRoles($this->roles()->reject(fn (UserRole $current) => $current === $role));
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return $this->roles()->contains(fn (UserRole $role) => in_array($role, $roles, true));
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole(...$roles);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(UserRole::Trainer);
    }

    /**
     * Konta z daną rolą (którąkolwiek z podanych).
     */
    public function scopeWithRole(Builder $query, UserRole ...$roles): void
    {
        $query->whereHas('roleAssignments', fn ($assignments) => $assignments->whereIn('role', array_map(fn (UserRole $role) => $role->value, $roles)));
    }

    /**
     * Uprawnienia wynikające z ról, z poprawkami nadanymi temu kontu.
     * Administrator ma zawsze wszystkie.
     *
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        if ($this->resolvedPermissions !== null) {
            return $this->resolvedPermissions;
        }

        if ($this->isAdmin()) {
            return $this->resolvedPermissions = collect(Permission::cases());
        }

        $map = RolePermission::map();
        $granted = $this->roles()->flatMap(fn (UserRole $role) => $map[$role->value] ?? [])->unique();
        $overrides = $this->permissionOverrides->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->allowed]);

        return $this->resolvedPermissions = collect(Permission::cases())
            ->filter(fn (Permission $permission) => $overrides[$permission->value] ?? $granted->contains($permission->value))
            ->values();
    }

    public function hasPermission(Permission $permission): bool
    {
        return $this->permissions()->contains($permission);
    }

    /**
     * Panel klubu widzi każdy, kto ma choć jedno uprawnienie do prowadzenia strony.
     */
    public function canAccessPanel(): bool
    {
        return $this->permissions()->contains(fn (Permission $permission) => $permission !== Permission::AllTeams);
    }

    public function canManageContent(): bool
    {
        return $this->canAccessPanel();
    }

    /**
     * Drużyny, którymi konto zarządza: null oznacza wszystkie,
     * inaczej tylko te, które prowadzi jako trener główny lub pomocniczy.
     *
     * @return Collection<int, int>|null
     */
    public function managedTeamIds(): ?Collection
    {
        if ($this->hasPermission(Permission::AllTeams)) {
            return null;
        }

        return $this->allCoachedTeams()->pluck('id');
    }

    public function managesTeam(?int $teamId): bool
    {
        $teamIds = $this->managedTeamIds();

        return $teamIds === null || ($teamId !== null && $teamIds->contains($teamId));
    }

    /**
     * Zapomina wyliczone uprawnienia (np. po zmianie ról w panelu).
     */
    public function refreshPermissions(): void
    {
        $this->resolvedPermissions = null;
        $this->unsetRelation('roleAssignments');
        $this->unsetRelation('permissionOverrides');
    }

    /**
     * Zawodnicy, których rodzicem (opiekunem) jest użytkownik.
     */
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'player_guardian')->withTimestamps();
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
        return $this->belongsToMany(Team::class, 'team_trainers')->withPivot('role')->withTimestamps();
    }

    /**
     * Czy użytkownik należy do sztabu jakiejkolwiek drużyny (ma publiczny profil).
     */
    public function isStaffMember(): bool
    {
        return $this->hasRole(UserRole::Trainer, UserRole::Staff)
            || $this->headCoachTeams()->exists()
            || $this->assistantTeams()->exists();
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

    protected function routeSlugSource(): string
    {
        return (string) $this->name;
    }
}
