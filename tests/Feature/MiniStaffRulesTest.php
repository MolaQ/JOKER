<?php

use App\Livewire\Admin\MiniVolleyballManager;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\User;
use App\Support\MiniRules;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'trojki');
});

test('the group page lists coordinators and trainers separately with links to their profiles', function () {
    $trainer = User::factory()->create(['role' => UserRole::MiniTrainer, 'name' => 'Anna Trenerka']);
    $coordinator = User::factory()->create(['role' => UserRole::MiniCoordinator, 'name' => 'Ewa Koordynatorka']);
    $other = User::factory()->create(['role' => UserRole::MiniTrainer, 'name' => 'Piotr Drugi']);
    foreach ([$trainer, $coordinator, $other] as $person) {
        $person->miniGroups()->attach($this->group);
    }

    $this->get(route('mini.show', $this->group))->assertOk()
        ->assertSeeInOrder(['Koordynator', 'Ewa Koordynatorka', 'Trenerzy', 'Anna Trenerka', 'Piotr Drugi'])
        ->assertSee(route('trainers.show', $coordinator), false);

    $this->get(route('trainers.show', $coordinator))->assertOk()->assertSee('Koordynator minisiatkówki');
});

test('a trip links to its group page and the right tab', function () {
    $trip = MiniTrip::create(['title' => 'Kinder Joy of Moving', 'starts_on' => now()->addWeek(), 'status' => MiniTrip::STATUS_PUBLISHED]);
    $trip->teams()->create(['mini_group_id' => $this->group->id, 'name' => 'Joker 1']);

    expect($trip->load('teams.group')->url())->toBe(route('mini.show', ['miniGroup' => $this->group, 'tab' => 'wyjazdy']).'#wyjazd-'.$trip->id);

    $this->get(route('mini.index'))->assertSee('id="wyjazd-'.$trip->id.'"', false)->assertSee('#wyjazd-'.$trip->id, false);
});

test('admins insert proposed rules for singles, twos, threes and fours', function () {
    foreach ([1, 2, 3, 4] as $players) {
        expect(MiniRules::template($players))->toContain('Boisko')->toContain('Kinder Joy of Moving');
    }
    expect(MiniRules::template(1))->toContain('8 × 3 m')->and(MiniRules::template(4))->toContain('2,24 m')->and(MiniRules::template(6))->toBe('');

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(MiniVolleyballManager::class)
        ->call('editGroup', $this->group->id)
        ->call('insertRulesTemplate')
        ->assertSet('group.rules', MiniRules::template((int) $this->group->players_on_court))
        ->call('saveGroup')->assertHasNoErrors();

    expect($this->group->fresh()->rules)->toContain('14 × 4,5 m');
    $this->get(route('mini.show', $this->group->fresh()))->assertOk()->assertSee('14 × 4,5 m');
});
