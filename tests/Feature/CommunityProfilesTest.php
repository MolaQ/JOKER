<?php

use App\Livewire\Messages;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Permission;
use App\Support\Mentions;
use App\Support\SearchIndex;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    Mentions::flush();
    SearchIndex::forget();
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->parent = User::factory()->parent()->create(['name' => 'Ewa Rodzicka']);
    $this->child = Player::factory()->create(['first_name' => 'Kuba', 'last_name' => 'Rodzicki', 'team_id' => $this->team->id]);
    $this->child->guardians()->attach($this->parent);
    $this->admin = User::factory()->admin()->create(['name' => 'Adam Prezes']);
});

it('shows community profiles only to logged-in users and board profiles to everyone', function () {
    $this->get(route('people.show', $this->parent))->assertRedirect(route('login'));
    $this->get(route('people.show', $this->admin))->assertOk()->assertSee('Adam Prezes')->assertSee('Administrator');

    $this->actingAs(User::factory()->parent()->create())
        ->get(route('people.show', $this->parent))->assertOk()
        ->assertSee('Rodzic')
        ->assertSee('Kuba Rodzicki')
        ->assertSee('noindex', false);
});

it('shows the envelope to logged-in users who can write, and sends a player without account to the parents', function () {
    $this->get(route('players.show', $this->child))->assertOk()->assertDontSee('Napisz do rodziców');

    $writer = User::factory()->parent()->create();
    $this->actingAs($writer)->get(route('players.show', $this->child))->assertOk()
        ->assertSee('Napisz do rodziców')
        ->assertSee(route('messages.index', ['do' => $this->parent->id]), false);

    Livewire::withQueryParams(['do' => $this->parent->id.','.$this->admin->id])->test(Messages::class)
        ->assertSet('composing', true)
        ->assertSet('recipientIds', [$this->parent->id, $this->admin->id]);
});

it('does not let a fan write messages', function () {
    $fan = User::factory()->create(['role' => UserRole::Fan]);

    expect($fan->hasPermission(Permission::WriteMessages))->toBeFalse()
        ->and($this->parent->hasPermission(Permission::WriteMessages))->toBeTrue();

    $this->actingAs($fan)->get(route('people.show', $this->parent))->assertOk()->assertDontSee('Napisz wiadomość');
});

it('finds parents in search only for logged-in users, with their role', function () {
    expect(collect(SearchIndex::search('Rodzicka'))->pluck('title'))->not->toContain('Ewa Rodzicka');

    $found = collect(SearchIndex::search('Rodzicka', user: $this->parent));
    expect($found->pluck('title'))->toContain('Ewa Rodzicka')
        ->and($found->firstWhere('title', 'Ewa Rodzicka')['subtitle'])->toContain('Rodzic');

    expect(collect(SearchIndex::search('Prezes'))->pluck('title'))->toContain('Adam Prezes');
});

it('links @mentions of board members to their profile', function () {
    expect(Mentions::link('<p>Gratulacje od @Adam Prezes.</p>'))->toContain('href="'.route('people.show', $this->admin, false).'" class="mention"')
        ->and(Mentions::link('<p>@Ewa Rodzicka</p>'))->not->toContain('class="mention"');
});
