<?php

namespace App\Support;

use App\Models\User;
use App\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Strona „Sztab”: wszyscy trenerzy (także minisiatkówki), fizjoterapeuci, statystycy i kierownicy w jednym miejscu.
 */
class StaffDirectory
{
    /** @var array<string, string> Grupy filtra na stronie. */
    public const GROUPS = ['coaches' => 'Trenerzy', 'mini' => 'Minisiatkówka', 'support' => 'Sztab'];

    /**
     * @return Collection<int, array{user: User, function: string, groups: list<string>, teams: Collection<int, array{key: string, name: string, role: string, url: string}>}>
     */
    public static function entries(bool $withHidden = false): Collection
    {
        $miniEnabled = (bool) app(SiteSettings::class)['module_mini'];

        return User::staffDirectory()
            ->when(! $withHidden, fn ($query) => $query->where('staff_hidden', false))
            ->with(['headCoachTeams', 'assistantTeams', 'miniGroups', 'roleAssignments'])
            ->get()
            ->map(fn (User $user) => self::entry($user, $miniEnabled))
            // Bez ręcznej kolejności: najpierw trenerzy główni, potem pozostali trenerzy, mini i reszta sztabu.
            ->sortBy([
                fn (array $a, array $b) => [$a['user']->staff_order === null, $a['user']->staff_order] <=> [$b['user']->staff_order === null, $b['user']->staff_order],
                fn (array $a, array $b) => self::rank($a) <=> self::rank($b),
                fn (array $a, array $b) => strcoll($a['user']->name, $b['user']->name),
            ])
            ->values();
    }

    /**
     * @param  array{user: User, groups: list<string>}  $entry
     */
    private static function rank(array $entry): int
    {
        return match (true) {
            $entry['user']->relationLoaded('headCoachTeams') && $entry['user']->headCoachTeams->isNotEmpty() => 0,
            in_array('coaches', $entry['groups'], true) => 1,
            in_array('mini', $entry['groups'], true) => 2,
            default => 3,
        };
    }

    /**
     * @return array{user: User, function: string, groups: list<string>, teams: Collection<int, array{key: string, name: string, role: string, url: string}>}
     */
    public static function entry(User $user, bool $miniEnabled = true): array
    {
        $teams = $user->headCoachTeams->toBase()
            ->map(fn ($team) => ['key' => 'team-'.$team->id, 'name' => $team->name, 'role' => __('Trener główny'), 'url' => route('teams.show', $team), 'order' => $team->display_order])
            ->merge($user->assistantTeams
                ->reject(fn ($team) => $team->trainer_id === $user->id)
                ->map(fn ($team) => ['key' => 'team-'.$team->id, 'name' => $team->name, 'role' => __($team->pivot->role ?: 'Asystent trenera'), 'url' => route('teams.show', $team), 'order' => $team->display_order]))
            ->sortBy('order')
            ->merge($miniEnabled ? $user->miniGroups->sortBy('display_order')
                ->map(fn ($group) => ['key' => 'mini-'.$group->id, 'name' => __('Minisiatkówka').' · '.$group->name, 'role' => __('Trener minisiatkówki'), 'url' => route('mini.show', $group), 'order' => 1000 + $group->display_order]) : [])
            ->map(fn (array $team) => collect($team)->except('order')->all())
            ->values();

        $isCoachFunction = fn (?string $role) => blank($role) || Str::contains(Str::lower($role), ['trener', 'coach']);
        $assistantRoles = $user->assistantTeams->reject(fn ($team) => $team->trainer_id === $user->id)->map(fn ($team) => $team->pivot->role);

        $groups = [];
        if ($user->hasRole(UserRole::Trainer) || $user->headCoachTeams->isNotEmpty() || $assistantRoles->contains(fn ($role) => $isCoachFunction($role))) {
            $groups[] = 'coaches';
        }
        if ($miniEnabled && ($user->hasRole(UserRole::MiniTrainer) || $user->miniGroups->isNotEmpty())) {
            $groups[] = 'mini';
        }
        if ($user->hasRole(UserRole::Staff) || $assistantRoles->contains(fn ($role) => ! $isCoachFunction($role))
            || (filled($user->staff_function) && ! $isCoachFunction($user->staff_function)) || $groups === []) {
            $groups[] = 'support';
        }

        $function = filled($user->staff_function) ? $user->staff_function : match (true) {
            $user->headCoachTeams->isNotEmpty() => __('Trener'),
            $assistantRoles->filter()->isNotEmpty() => __($assistantRoles->filter()->first()),
            $assistantRoles->isNotEmpty() => __('Asystent trenera'),
            in_array('mini', $groups, true) && ! in_array('coaches', $groups, true) => __('Trener minisiatkówki'),
            $user->hasRole(UserRole::Trainer) => __('Trener'),
            default => __('Sztab klubu'),
        };

        return ['user' => $user, 'function' => $function, 'groups' => array_values(array_unique($groups)), 'teams' => $teams];
    }
}
