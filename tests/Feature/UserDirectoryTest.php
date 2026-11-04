<?php

use App\Livewire\Admin\UserDirectory;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->cadets = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet', 'display_order' => 4]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'display_order' => 1]);

    $this->cadetPlayerUser = User::factory()->create(['name' => 'Kuba Kadet']);
    Player::factory()->create(['user_id' => $this->cadetPlayerUser->id, 'team_id' => $this->cadets->id, 'first_name' => 'Kuba', 'last_name' => 'Kadet']);

    $this->cadetParent = User::factory()->create(['name' => 'Anna Rodzic']);
    $child = Player::factory()->create(['team_id' => $this->cadets->id, 'first_name' => 'Ola', 'last_name' => 'Rodzic']);
    $this->cadetParent->children()->attach($child);

    $this->seniorUser = User::factory()->create(['name' => 'Piotr Senior']);
    Player::factory()->create(['user_id' => $this->seniorUser->id, 'team_id' => $this->seniors->id]);
});

test('koordynator widzi tylko zawodników i rodziców swojej drużyny', function () {
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator->coordinatedTeams()->sync([$this->cadets->id]);

    $this->actingAs($coordinator)->get(route('admin.people.index'))->assertOk()->assertSee('Osoby w drużynach');

    Livewire::actingAs($coordinator)->test(UserDirectory::class)
        ->assertSee('Kuba Kadet')
        ->assertSee('Anna Rodzic')
        ->assertSee('Ola Rodzic')
        ->assertDontSee('Piotr Senior')
        ->assertDontSee('Zapisz')
        ->set('search', 'Ola')
        ->assertSee('Anna Rodzic')
        ->assertDontSee('Kuba Kadet');
});

test('moderator widzi wszystkie konta, ale bez edycji', function () {
    $moderator = User::factory()->create(['role' => UserRole::Moderator]);

    Livewire::actingAs($moderator)->test(UserDirectory::class)
        ->assertSee('Kuba Kadet')
        ->assertSee('Piotr Senior');

    $this->actingAs($moderator)->get(route('admin.users.index'))->assertForbidden();
});

test('kibic i rodzic nie widzą listy osób', function () {
    $this->actingAs(User::factory()->fan()->create())->get(route('admin.people.index'))->assertForbidden();
    $this->actingAs($this->cadetParent)->get(route('admin.people.index'))->assertForbidden();
});

test('menu pokazuje listę osób koordynatorowi, a administratorowi pełnych użytkowników', function () {
    $coordinator = User::factory()->create(['role' => UserRole::Coordinator]);
    $coordinator->coordinatedTeams()->sync([$this->cadets->id]);

    $this->actingAs($coordinator)->get(route('admin.people.index'))->assertSee(route('admin.people.index'))->assertDontSee(route('admin.users.index'));
    $this->actingAs(User::factory()->admin()->create())->get(route('admin.users.index'))->assertDontSee(route('admin.people.index'));
});
