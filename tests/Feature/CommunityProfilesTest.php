<?php

use App\Livewire\Messages;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Permission;
use App\Support\Mentions;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
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
        ->assertSee(route('messages.index', ['to' => $this->parent->id]), false);

    Livewire::withQueryParams(['to' => $this->parent->id.','.$this->admin->id])->test(Messages::class)
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

it('links @mentions of anyone in the community, e.g. who took the photos', function () {
    expect(route('people.show', $this->parent, false))->toStartWith('/family/')
        ->and(Mentions::link('<p>Gratulacje od @Adam Prezes.</p>'))->toContain('href="'.route('people.show', $this->admin, false).'" class="mention"')
        ->and(Mentions::link('<p>Zdjęcia: @Ewa Rodzicka</p>'))->toContain('href="'.route('people.show', $this->parent, false).'" class="mention"');
});

it('follows the message rules from settings and can switch messages off', function () {
    $coach = User::factory()->trainer()->create(['name' => 'Tomasz Trener']);
    $rules = app(SiteSettings::class)->messageRules();
    $rules['parent'] = ['trainer'];
    app(SiteSettings::class)->set(['messages_rules' => $rules]);

    expect($this->parent->mayMessage($coach))->toBeTrue()
        ->and($this->parent->mayMessage($this->admin))->toBeFalse();

    $this->actingAs($this->parent);
    Livewire::test(Messages::class)->call('startComposing')->set('recipientSearch', 'Prezes')->assertDontSee('Adam Prezes')
        ->set('recipientSearch', 'Trener')->assertSee('Tomasz Trener');

    app(SiteSettings::class)->set(['messages_enabled' => false]);
    expect($this->parent->fresh()->canMessage())->toBeFalse();
    $this->get(route('messages.index'))->assertNotFound();
    $this->get(route('home'))->assertDontSee(route('messages.index'), false);
});

test('kolor serduszek ustawia się w panelu', function () {
    app(SiteSettings::class)->set(['color_like' => '#e11d48']);

    $this->get(route('home'))->assertSee('--color-like: #e11d48', false);
});
