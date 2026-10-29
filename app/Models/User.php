<?php

namespace App\Models;

use App\AccountStatus;
use App\BirthdayDisplay;
use App\Models\Concerns\CountsViews;
use App\Models\Concerns\HasReadableRouteKey;
use App\Models\Concerns\Likeable;
use App\Permission;
use App\UserRole;
use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use CountsViews;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasReadableRouteKey;
    use Likeable;

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
        'is_active',
        'requested_role',
        'requested_team_id',
        'registration_note',
        'activated_at',
        'activated_by',
        'privacy_consent_at',
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
            'notify_approvals' => 'boolean',
            'approvals_notified_at' => 'datetime',
            'notify_messages' => 'boolean',
            'messages_emailed_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'birthday_display' => BirthdayDisplay::class,
            'is_active' => 'boolean',
            'requested_role' => UserRole::class,
            'activated_at' => 'datetime',
            'privacy_consent_at' => 'datetime',
            'rejected_at' => 'datetime',
            'disabled_at' => 'datetime',
            'anonymized_at' => 'datetime',
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

    /**
     * Role, które jeszcze obowiązują (bezterminowe albo przed datą „ważne do”).
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class)->active();
    }

    /**
     * Wyjątki w uprawnieniach, które jeszcze obowiązują.
     */
    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(PermissionOverride::class)->active();
    }

    /**
     * Daty „ważne do” ról konta, np. ['trainer' => Carbon]. Bez wpisu = bezterminowo.
     *
     * @return array<string, Carbon>
     */
    public function roleExpiries(): array
    {
        return $this->roleAssignments
            ->filter(fn (RoleAssignment $assignment) => $assignment->expires_at !== null)
            ->mapWithKeys(fn (RoleAssignment $assignment) => [$assignment->role->value => $assignment->expires_at])
            ->all();
    }

    /**
     * Ustawia datę „ważne do” jednej z ról konta (null = bezterminowo). Rola działa do końca podanego dnia.
     */
    public function setRoleExpiry(UserRole $role, ?CarbonInterface $until): void
    {
        $until = $until ? Carbon::instance($until)->endOfDay() : null;
        $current = $this->roleExpiries()[$role->value] ?? null;

        if (! $this->hasRole($role) || $current?->toDateString() === $until?->toDateString()) {
            return;
        }

        RoleAssignment::where('user_id', $this->id)->where('role', $role)->update(['expires_at' => $until]);
        $this->unsetRelation('roleAssignments');
        $this->resolvedPermissions = null;

        if (Auth::check()) {
            AuditLog::record('roles.expiry', 'Rola '.$role->label().' konta '.$this->name.': '.($until ? 'ważna do '.$until->format('d.m.Y') : 'bezterminowo'), $this, [
                'rola' => $role->value,
                'zmiany' => ['ważne do' => [$current?->format('d.m.Y') ?? 'bezterminowo', $until?->format('d.m.Y') ?? 'bezterminowo']],
            ]);
        }
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

        // Wygasłe role znikają, żeby można je było nadać od nowa.
        RoleAssignment::where('user_id', $this->id)->expired()->delete();
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
     * Aktywne konta z publicznym profilem: sztab i administracja klubu.
     */
    public function scopeWithPublicProfile(Builder $query): void
    {
        $query->withAccountStatus(AccountStatus::Active)->where(fn (Builder $query) => $query
            ->withRole(UserRole::Admin, UserRole::Boss, UserRole::Trainer, UserRole::Staff)
            ->orWhereHas('headCoachTeams')
            ->orWhereHas('assistantTeams'));
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

        if (! $this->is_active) {
            return $this->resolvedPermissions = collect();
        }

        if ($this->isAdmin()) {
            return $this->resolvedPermissions = collect(Permission::cases());
        }

        $granted = $this->rolePermissionValues();
        $overrides = $this->permissionOverrides->mapWithKeys(fn (PermissionOverride $override) => [$override->permission->value => $override->allowed]);

        return $this->resolvedPermissions = collect(Permission::cases())
            ->filter(fn (Permission $permission) => $overrides[$permission->value] ?? $granted->contains($permission->value))
            ->values();
    }

    /**
     * Uprawnienia z samych ról (bez wyjątków). Konto bez roli traktujemy jak kibica.
     *
     * @return Collection<int, string>
     */
    public function rolePermissionValues(): Collection
    {
        $map = RolePermission::map();
        $roles = $this->roles();

        return ($roles->isEmpty() ? collect([UserRole::Fan]) : $roles)
            ->flatMap(fn (UserRole $role) => $map[$role->value] ?? [])
            ->unique()
            ->values();
    }

    /**
     * Czy konto może pisać komentarze: aktywne i z uprawnieniem (można je odebrać jednej osobie).
     */
    public function canComment(): bool
    {
        return $this->hasPermission(Permission::WriteComments);
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
        return $this->permissions()->contains(fn (Permission $permission) => $permission !== Permission::AllTeams && ! $permission->isPublic());
    }

    public function canManageContent(): bool
    {
        return $this->canAccessPanel();
    }

    /**
     * Komentarz kibica (konta bez innej roli w klubie) czeka na akceptację moderatora.
     */
    public function commentsNeedApproval(): bool
    {
        return ! $this->hasPermission(Permission::Comments)
            && $this->roles()->every(fn (UserRole $role) => $role === UserRole::Fan);
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

        return $this->allCoachedTeams()->pluck('id')
            ->merge($this->hasRole(UserRole::Coordinator) ? $this->coordinatedTeams->pluck('id') : [])
            ->unique()
            ->values();
    }

    public function managesTeam(?int $teamId): bool
    {
        $teamIds = $this->managedTeamIds();

        return $teamIds === null || ($teamId !== null && $teamIds->contains($teamId));
    }

    /**
     * Drużyna wskazana przy rejestracji (pomaga trenerowi rozpoznać, kogo aktywuje).
     */
    public function requestedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'requested_team_id');
    }

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Profil zawodnika powiązany z kontem.
     */
    public function playerProfile(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    /**
     * Aktywuje konto z podanymi rolami (dopisuje je do obecnych) i zapisuje to w dzienniku.
     */
    public function activate(UserRole ...$roles): void
    {
        foreach ($roles as $role) {
            $this->assignRole($role);
        }

        if (! $this->is_active) {
            $this->forceFill(['is_active' => true, 'activated_at' => now(), 'activated_by' => Auth::id()])->save();
            $this->refreshPermissions();
            AuditLog::record('account.activated', 'Aktywowano konto '.$this->name, $this, ['role' => array_map(fn (UserRole $role) => $role->label(), $roles)]);
        }
    }

    /**
     * Konta w danym stanie (filtr w panelu), zgodnie z accountStatus().
     */
    public function scopeWithAccountStatus(Builder $query, AccountStatus $status): void
    {
        match ($status) {
            AccountStatus::Deleted => $query->whereNotNull('anonymized_at'),
            AccountStatus::Disabled => $query->whereNull('anonymized_at')->whereNotNull('disabled_at'),
            AccountStatus::Active => $query->whereNull('anonymized_at')->whereNull('disabled_at')->where('is_active', true),
            AccountStatus::Rejected => $query->whereNull('anonymized_at')->whereNull('disabled_at')->where('is_active', false)->whereNotNull('rejected_at'),
            AccountStatus::Suspended => $query->whereNull('anonymized_at')->whereNull('disabled_at')->where('is_active', false)->whereNull('rejected_at')->whereNotNull('activated_at'),
            AccountStatus::Pending => $query->whereNull('anonymized_at')->whereNull('disabled_at')->where('is_active', false)->whereNull('rejected_at')->whereNull('activated_at'),
        };
    }

    /**
     * Stan konta: czeka, odrzucone, aktywne, zawieszone przez klub, wyłączone przez użytkownika albo usunięte.
     */
    public function accountStatus(): AccountStatus
    {
        return match (true) {
            $this->anonymized_at !== null => AccountStatus::Deleted,
            $this->disabled_at !== null => AccountStatus::Disabled,
            $this->is_active => AccountStatus::Active,
            $this->rejected_at !== null => AccountStatus::Rejected,
            $this->activated_at !== null => AccountStatus::Suspended,
            default => AccountStatus::Pending,
        };
    }

    /**
     * Imię i nazwisko konta zatwierdzonego przez klub zmienia tylko klub.
     */
    public function nameIsLocked(): bool
    {
        return $this->activated_at !== null;
    }

    public function hasPrivacyConsent(): bool
    {
        return $this->privacy_consent_at !== null;
    }

    /**
     * Klub odrzuca prośbę o aktywację z podanym powodem. Konto zostaje, a użytkownik może poprawić dane i poprosić ponownie.
     */
    public function rejectActivation(string $reason): void
    {
        $this->forceFill(['rejected_at' => now(), 'rejection_reason' => $reason, 'rejected_by' => Auth::id()])->save();
        AuditLog::record('account.rejected', 'Odrzucono prośbę o aktywację konta '.$this->name.': '.$reason, $this);
    }

    /**
     * Użytkownik poprawił dane po odrzuceniu i wysyła prośbę jeszcze raz.
     *
     * @param  array{name?: string, requested_role: UserRole, requested_team_id: ?int, registration_note: ?string}  $data
     */
    public function resubmitActivation(array $data): void
    {
        $this->forceFill($data + ['rejected_at' => null, 'rejection_reason' => null, 'rejected_by' => null])->save();
        AuditLog::record('account.resubmitted', 'Ponowna prośba o aktywację konta '.$this->name, $this);
    }

    /**
     * Użytkownik sam wyłącza konto: dane zostają, ale konto nic nie może, dopóki go nie włączy.
     */
    public function disableByUser(): void
    {
        $this->forceFill(['disabled_at' => now(), 'is_active' => false])->save();
        $this->refreshPermissions();
        AuditLog::record('account.disabled', 'Użytkownik wyłączył konto '.$this->name, $this);
    }

    /**
     * Ponowne włączenie konta wyłączonego przez użytkownika. Konto zatwierdzone przez klub
     * (albo kibica z potwierdzonym e-mailem) wraca do działania bez ponownej aktywacji.
     */
    public function enableByUser(): void
    {
        $wasApproved = $this->activated_at !== null && $this->rejected_at === null;

        $this->forceFill(['disabled_at' => null, 'is_active' => $wasApproved])->save();
        $this->refreshPermissions();
        AuditLog::record('account.enabled', 'Użytkownik włączył ponownie konto '.$this->name, $this);

        if ($wasApproved) {
            $this->syncParentActivity();
        }
    }

    /**
     * Cofnięcie zgody albo prośba o usunięcie: konto przestaje działać, a dane osobowe są usuwane.
     * Treści (komentarze, propozycje) zostają podpisane „Konto usunięte”, a powiązania z zawodnikami i drużynami znikają.
     */
    public function anonymize(string $reason): void
    {
        $name = $this->name;

        Player::where('user_id', $this->id)->update(['user_id' => null]);
        $this->children()->detach();
        Team::where('trainer_id', $this->id)->update(['trainer_id' => null]);
        $this->assistantTeams()->detach();
        $this->roleAssignments()->delete();
        $this->receivedLikes()->delete();
        $this->permissionOverrides()->delete();
        $this->profileChangeRequests()->delete();
        PushSubscription::where('user_id', $this->id)->delete();

        if ($this->avatar_path) {
            Storage::disk('public')->delete($this->avatar_path);
        }

        $this->forceFill([
            'name' => 'Konto usunięte',
            'email' => 'usuniete-'.$this->id.'-'.Str::lower(Str::random(6)).'@konto.invalid',
            'password' => Str::random(40),
            'remember_token' => null,
            'avatar_path' => null,
            'instagram' => null,
            'additional_info' => null,
            'birth_date' => null,
            'registration_note' => null,
            'requested_team_id' => null,
            'rejection_reason' => null,
            'is_active' => false,
            'anonymized_at' => now(),
            'closed_reason' => $reason,
        ])->save();
        $this->refreshPermissions();

        AuditLog::record('account.anonymized', 'Usunięto dane konta #'.$this->id.' ('.($reason === 'consent_withdrawn' ? 'cofnięta zgoda' : 'prośba użytkownika').')', $this, ['dawna_nazwa' => mb_substr($name, 0, 1).'.']);
    }

    public function deactivate(string $reason): void
    {
        if ($this->is_active) {
            $this->forceFill(['is_active' => false])->save();
            $this->refreshPermissions();
            AuditLog::record('account.deactivated', 'Dezaktywowano konto '.$this->name.': '.$reason, $this);
        }
    }

    /**
     * Rodzic jest aktywny, dopóki aktywne jest choć jedno jego dziecko,
     * chyba że ma w klubie inną rolę (np. jest też trenerem).
     */
    public function syncParentActivity(): void
    {
        if (! $this->hasRole(UserRole::Parent) || $this->roles()->contains(fn (UserRole $role) => ! in_array($role, [UserRole::Parent, UserRole::Fan], true))) {
            return;
        }

        $hasActiveChild = $this->children()->where('players.is_active', true)->exists();

        if ($hasActiveChild && ! $this->is_active) {
            $this->forceFill(['is_active' => true, 'activated_at' => now()])->save();
            $this->refreshPermissions();
            AuditLog::record('account.activated', 'Konto rodzica '.$this->name.' wróciło, bo jego dziecko znów jest aktywne', $this);
        } elseif (! $hasActiveChild) {
            $this->deactivate('żadne dziecko nie jest już aktywnym zawodnikiem');
        }
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

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)->withPivot('last_read_message_id')->withTimestamps();
    }

    /**
     * Czy konto może pisać wiadomości: aktywne i z uprawnieniem (można je odebrać jednej osobie, np. za spam).
     */
    public function canMessage(): bool
    {
        return $this->hasPermission(Permission::WriteMessages);
    }

    public function unreadMessagesCount(): int
    {
        return once(fn () => Message::unreadBy($this)->count());
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Serduszka otrzymane przez profil w sztabie (likes() to polubienia dane przez to konto).
     */
    public function receivedLikes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function likesRelation(): MorphMany
    {
        return $this->receivedLikes();
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
     * Drużyny, które koordynuje (rola Koordynator drużyny). Koordynator nie jest w sztabie.
     */
    public function coordinatedTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_coordinators')->withTimestamps();
    }

    /**
     * Profil widoczny dla wszystkich (sztab, administracja klubu). Pozostałe osoby widzą tylko zalogowani.
     */
    public function hasPublicProfile(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Boss) || $this->isStaffMember();
    }

    /**
     * Adres profilu osoby: sztab ma swoją stronę w „Sztabie”, reszta w „Osobach”.
     */
    public function profileUrl(bool $absolute = true): string
    {
        return $this->isStaffMember() ? route('trainers.show', $this, $absolute) : route('people.show', $this, $absolute);
    }

    /**
     * Role do pokazania w profilu i w wyszukiwarce, np. „Rodzic · Fotograf”.
     */
    public function rolesLabel(): string
    {
        return $this->roles()->map(fn (UserRole $role) => __($role->label()))->unique()->implode(' · ');
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
