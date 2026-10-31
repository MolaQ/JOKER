<?php

use App\Livewire\Admin\GameManager;
use App\Livewire\Admin\PlayerManager;
use App\Livewire\Admin\RoleManager;
use App\Livewire\Admin\TeamList;
use App\Livewire\Admin\UserManager;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Permission;
use App\UserRole;
use Livewire\Livewire;

test('an account can hold several roles and gets permissions of all of them', function () {
    $user = User::factory()->create(['role' => [UserRole::Editor, UserRole::Parent]]);

    expect($user->roles()->all())->toBe([UserRole::Editor, UserRole::Parent])
        ->and($user->hasPermission(Permission::Articles))->toBeTrue()
        ->and($user->hasPermission(Permission::Teams))->toBeFalse()
        ->and($user->canAccessPanel())->toBeTrue();
});

test('parents, players and fans do not see the panel', function () {
    foreach ([UserRole::Parent, UserRole::Player, UserRole::Fan] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get('/panel')->assertForbidden();
    }
});

test('panel sections follow permissions', function () {
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->get('/panel/articles')->assertOk();
    $this->actingAs($editor)->get('/panel/teams')->assertForbidden();
    $this->actingAs($editor)->get('/panel/system')->assertForbidden();

    $this->actingAs(User::factory()->boss()->create())->get('/panel/system')->assertOk();
    $this->actingAs(User::factory()->admin()->create())->get('/panel/roles')->assertOk();
});

test('a single account can be granted or denied a permission', function () {
    $parent = User::factory()->parent()->create();
    $parent->permissionOverrides()->create(['permission' => Permission::Media, 'allowed' => true]);
    $trainer = User::factory()->trainer()->create();
    $trainer->permissionOverrides()->create(['permission' => Permission::Games, 'allowed' => false]);

    $this->actingAs($parent)->get('/panel/media')->assertOk();
    $this->actingAs($trainer)->get('/panel/games')->assertForbidden();
});

test('a trainer manages only own teams and their players', function () {
    $trainer = User::factory()->trainer()->create();
    $own = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet', 'trainer_id' => $trainer->id]);
    $other = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $ownPlayer = Player::factory()->create(['last_name' => 'Swojak', 'team_id' => $own->id]);
    $otherPlayer = Player::factory()->create(['last_name' => 'Obcy', 'team_id' => $other->id]);

    $this->actingAs($trainer);

    Livewire::test(TeamList::class)->assertSee('Kadeci')->assertDontSee('Seniorzy')->assertDontSee('+ Dodaj drużynę');
    Livewire::test(PlayerManager::class)->assertSee('Swojak')->assertDontSee('Obcy');
    expect(fn () => Livewire::test(PlayerManager::class)->call('openEditModal', $otherPlayer->id))->toThrow(Exception::class);
    Livewire::test(GameManager::class)->assertOk();

    $trainer->permissionOverrides()->create(['permission' => Permission::AllTeams, 'allowed' => true]);
    $trainer->refreshPermissions();

    Livewire::test(PlayerManager::class)->assertSee('Obcy');
});

test('role permissions are edited in the panel and logged', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(RoleManager::class)->call('toggle', 'trainer', 'documents.manage');

    expect(User::factory()->trainer()->create()->hasPermission(Permission::Documents))->toBeTrue()
        ->and(AuditLog::where('action', 'permissions.role')->count())->toBe(1);
});

test('only an administrator touches the administrator and boss roles', function () {
    $boss = User::factory()->boss()->create();
    $admin = User::factory()->admin()->create(['name' => 'Główny Admin']);
    $fan = User::factory()->fan()->create();
    $this->actingAs($boss);

    Livewire::test(RoleManager::class)->call('toggle', 'boss', 'roles.manage')->assertForbidden();

    Livewire::test(UserManager::class)
        ->assertViewHas('assignableRoles', fn (array $roles) => ! in_array(UserRole::Admin, $roles, true))
        ->call('openEditModal', $admin->id)
        ->assertSet('editingId', null)
        ->call('openEditModal', $fan->id)
        ->set('selectedRoles', ['admin'])
        ->call('save')
        ->assertHasErrors('selectedRoles.0');

    Livewire::test(UserManager::class)
        ->call('openEditModal', $fan->id)
        ->set('selectedRoles', ['fan', 'editor'])
        ->call('save')
        ->assertHasNoErrors();

    expect($fan->fresh()->roles()->all())->toBe([UserRole::Editor, UserRole::Fan])
        ->and(AuditLog::where('action', 'roles.changed')->exists())->toBeTrue();
});

test('an administrator cannot remove own administrator role', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(UserManager::class)
        ->call('openEditModal', $admin->id)
        ->set('selectedRoles', ['parent'])
        ->call('save')
        ->assertHasErrors('selectedRoles');

    expect($admin->fresh()->isAdmin())->toBeTrue();
});
