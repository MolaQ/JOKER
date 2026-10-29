<?php

use App\Livewire\Admin\AccountActivation;
use App\Livewire\Admin\ParentAssignment;
use App\Livewire\Admin\UserManager;
use App\Models\AuditLog;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Permission;
use App\UserRole;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->cadets = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet', 'display_order' => 4]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'display_order' => 1]);
});

function coordinatorOf(Team ...$teams): User
{
    $user = User::factory()->create(['role' => [UserRole::Coordinator, UserRole::Parent]]);
    $user->coordinatedTeams()->sync(collect($teams)->pluck('id'));

    return $user;
}

test('podpowiedź rozpoznaje dziecko z opisu rodzica, także w odmianie i po nazwisku rodzica', function () {
    $jan = Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski', 'team_id' => $this->cadets->id]);
    $piotr = Player::factory()->create(['first_name' => 'Piotr', 'last_name' => 'Kowalski', 'team_id' => $this->seniors->id]);
    $ola = Player::factory()->create(['first_name' => 'Aleksandra', 'last_name' => 'Wiśniewska', 'team_id' => $this->cadets->id]);
    $players = Player::with('team')->get();

    $parent = User::factory()->inactive()->make(['name' => 'Anna Kowalska', 'registration_note' => 'Jestem mamą Jana Kowalskiego', 'requested_team_id' => $this->cadets->id]);
    expect(ParentAssignment::suggestions($parent, $players)->pluck('player.id')->all())->toBe([$jan->id]);

    // Samo nazwisko: obaj Kowalscy jako podpowiedź do sprawdzenia, najpierw z wybranej drużyny.
    $parent = User::factory()->inactive()->make(['name' => 'Anna Kowalska', 'registration_note' => 'syn Kowalski', 'requested_team_id' => $this->cadets->id]);
    $guess = ParentAssignment::suggestions($parent, $players);
    expect($guess->pluck('player.id')->all())->toBe([$jan->id, $piotr->id])
        ->and($guess->pluck('sure')->unique()->all())->toBe([false]);

    $parent = User::factory()->inactive()->make(['name' => 'Marek Zieliński', 'registration_note' => 'córka Ola Wisniewska, kadetki', 'requested_team_id' => $this->cadets->id]);
    expect(ParentAssignment::suggestions($parent, $players)->pluck('player.id')->all())->toBe([$ola->id]);

    $parent = User::factory()->inactive()->make(['name' => 'Ewa Nowak', 'registration_note' => 'syn gra w kadetach', 'requested_team_id' => $this->cadets->id]);
    expect(ParentAssignment::suggestions($parent, $players))->toBeEmpty();
});

test('jedno kliknięcie aktywuje rodzica i przypisuje dziecko', function () {
    $jan = Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski', 'team_id' => $this->cadets->id]);
    $parent = User::factory()->inactive()->create(['name' => 'Anna Kowalska', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->cadets->id, 'registration_note' => 'Jan Kowalski']);

    $this->actingAs(User::factory()->admin()->create());
    $this->get('/panel/parent-assignments')->assertOk()->assertSee('Przypisz do: Jan Kowalski (Kadeci)');
    $this->get('/panel/account-activations')->assertOk()->assertDontSee('Anna Kowalska');

    Livewire::test(ParentAssignment::class)
        ->call('assign', $parent->id, $jan->id)
        ->assertSee('Przypisani przed chwilą');

    $parent->refresh();
    expect($parent->is_active)->toBeTrue()
        ->and($parent->hasRole(UserRole::Parent))->toBeTrue()
        ->and($parent->children->pluck('id')->all())->toBe([$jan->id])
        ->and(AuditLog::where('action', 'account.linked')->value('description'))->toContain('Jan Kowalski (Kadeci)');
    Notification::assertSentTo($parent, AccountActivated::class);
});

test('drugie dziecko wybiera się z listy', function () {
    $jan = Player::factory()->create(['first_name' => 'Jan', 'last_name' => 'Kowalski', 'team_id' => $this->cadets->id]);
    $zosia = Player::factory()->create(['first_name' => 'Zofia', 'last_name' => 'Kowalska', 'team_id' => $this->seniors->id]);
    $parent = User::factory()->inactive()->create(['name' => 'Anna Kowalska', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->cadets->id]);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ParentAssignment::class)
        ->call('assign', $parent->id, $jan->id)
        ->set('otherChild.'.$parent->id, (string) $zosia->id)
        ->call('assign', $parent->id);

    expect($parent->fresh()->children->pluck('id')->sort()->values()->all())->toBe([$jan->id, $zosia->id]);
});

test('koordynator widzi i przypisuje tylko rodziców swojej drużyny, ale nie jest w sztabie', function () {
    $coordinator = coordinatorOf($this->cadets);
    $mine = User::factory()->inactive()->create(['name' => 'Rodzic Kadeta', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->cadets->id]);
    $other = User::factory()->inactive()->create(['name' => 'Rodzic Seniora', 'requested_role' => UserRole::Parent, 'requested_team_id' => $this->seniors->id]);
    $cadet = Player::factory()->create(['team_id' => $this->cadets->id]);
    $senior = Player::factory()->create(['team_id' => $this->seniors->id]);

    expect($coordinator->hasPermission(Permission::ActivateAccounts))->toBeTrue()
        ->and($coordinator->managedTeamIds()->all())->toBe([$this->cadets->id])
        ->and($coordinator->isStaffMember())->toBeFalse();

    $this->actingAs($coordinator)->get('/panel/parent-assignments')->assertOk()->assertSee('Rodzic Kadeta')->assertDontSee('Rodzic Seniora');

    Livewire::test(ParentAssignment::class)->call('assign', $mine->id, $senior->id)->assertDispatched('toast', message: 'Wybierz zawodnika.', type: 'error');
    expect($mine->fresh()->is_active)->toBeFalse();

    Livewire::test(ParentAssignment::class)->call('assign', $mine->id, $cadet->id);
    expect($mine->fresh()->children->pluck('id')->all())->toBe([$cadet->id]);

    expect(fn () => Livewire::test(ParentAssignment::class)->call('assign', $other->id, $cadet->id))->toThrow(Exception::class);
});

test('koordynator traci dostęp, gdy rola wygaśnie', function () {
    $coordinator = coordinatorOf($this->cadets);
    $coordinator->setRoleExpiry(UserRole::Coordinator, now()->subDay());

    $fresh = User::find($coordinator->id);
    expect($fresh->hasPermission(Permission::ActivateAccounts))->toBeFalse()
        ->and($fresh->managedTeamIds()->all())->toBe([]);
});

test('w edycji konta koordynatorowi wybiera się drużyny', function () {
    $this->actingAs(User::factory()->admin()->create());
    $user = User::factory()->create(['role' => UserRole::Parent]);

    Livewire::test(UserManager::class)
        ->call('openEditModal', $user->id)
        ->set('selectedRoles', ['parent', 'coordinator'])
        ->assertSee('Koordynuje drużyny')
        ->call('save')
        ->assertHasErrors('coordinatedTeamIds')
        ->set('coordinatedTeamIds', [$this->cadets->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->coordinatedTeams->pluck('id')->all())->toBe([$this->cadets->id])
        ->and(AuditLog::where('action', 'roles.coordinator')->first()->details['dodane'])->toBe(['Kadeci']);

    Livewire::test(UserManager::class)
        ->call('openEditModal', $user->id)
        ->assertSet('coordinatedTeamIds', [$this->cadets->id])
        ->set('selectedRoles', ['parent'])
        ->call('save');

    expect($user->fresh()->coordinatedTeams)->toBeEmpty();
});

test('zakładka kont nie pokazuje już rodziców', function () {
    User::factory()->inactive()->create(['requested_role' => UserRole::Parent, 'requested_team_id' => $this->cadets->id]);
    $player = User::factory()->inactive()->create(['requested_role' => UserRole::Player, 'requested_team_id' => $this->cadets->id]);
    $admin = User::factory()->admin()->create();

    expect(AccountActivation::pendingFor($admin)->pluck('id')->all())->toBe([$player->id])
        ->and(ParentAssignment::countFor($admin))->toBe(1);
});
