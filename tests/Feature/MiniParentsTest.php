<?php

use App\Livewire\Admin\ParentAssignment;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    app(SiteSettings::class)->set(['module_mini' => '1']);
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'trojki');
    $this->child = MiniPlayer::create(['mini_group_id' => $this->group->id, 'first_name' => 'Zosia', 'last_name' => 'Wiśniewska', 'birth_year' => 2016]);
    $this->admin = User::factory()->admin()->create();
});

function registerMiniParent(array $overrides = []): TestResponse
{
    return test()->post('/register', $overrides + [
        'name' => 'Ewa Wiśniewska',
        'email' => 'ewa@example.com',
        'password' => 'tajnehaslo1',
        'password_confirmation' => 'tajnehaslo1',
        'kind' => 'parent',
        'team_id' => 'mini-'.test()->group->id,
        'note' => 'Zosia Wiśniewska',
        'privacy' => '1',
        'policy' => '1',
    ]);
}

test('rodzic dziecka z minisiatkówki wybiera grupę mini przy zakładaniu konta', function () {
    $this->get('/register')->assertOk()->assertSee('Minisiatkówka: Trójki')->assertSee('value="mini-'.$this->group->id.'"', false);

    registerMiniParent()->assertRedirect(route('profile.edit'));

    $parent = User::firstWhere('email', 'ewa@example.com');
    expect($parent->requested_role)->toBe(UserRole::Parent)
        ->and($parent->requested_team_id)->toBeNull()
        ->and($parent->requested_mini_group_id)->toBe($this->group->id);
});

test('nieistniejąca albo ukryta grupa mini nie przechodzi', function () {
    registerMiniParent(['team_id' => 'mini-999'])->assertSessionHasErrors('team_id');

    $this->group->update(['is_active' => false]);
    registerMiniParent(['team_id' => 'mini-'.$this->group->id])->assertSessionHasErrors('team_id');

    app(SiteSettings::class)->set(['module_mini' => '0']);
    $this->get('/register')->assertDontSee('Minisiatkówka: Czwórki');
});

test('klub przypisuje rodzica do dziecka z minisiatkówki jednym kliknięciem', function () {
    registerMiniParent();
    $parent = User::firstWhere('email', 'ewa@example.com');

    Livewire::actingAs($this->admin)->test(ParentAssignment::class)
        ->assertSeeText('Minisiatkówka: Trójki')
        ->assertSee('Przypisz do: Zosia Wiśniewska (mini: Trójki)')
        ->call('assignMini', $parent->id, $this->child->id)
        ->assertSee('Przypisane dzieci');

    $parent->refresh();
    expect($parent->is_active)->toBeTrue()
        ->and($parent->hasRole(UserRole::Parent))->toBeTrue()
        ->and($parent->miniChildren->pluck('id')->all())->toBe([$this->child->id])
        ->and(ParentAssignment::childlessFor($this->admin)->whereKey($parent->id)->exists())->toBeFalse();
    Notification::assertSentTo($parent, AccountActivated::class);

    // Rodzic widzi dziecko w „Mój profil” i sam ustawia zgodę na zdjęcie.
    $this->actingAs($parent)->get('/my-profile')->assertSee('Dzieci w minisiatkówce')->assertSee('Zosia Wiśniewska');
    $this->put(route('profile.mini-consent', $this->child->id), ['image_consent' => '1'])->assertRedirect();
    expect($this->child->fresh()->image_consent)->toBeTrue();

    // Gdy dziecko kończy z minisiatkówką, konto rodzica przestaje działać.
    $this->child->update(['is_active' => false]);
    expect($parent->fresh()->is_active)->toBeFalse();
});

test('dziecko mini można wybrać też z listy i odpiąć', function () {
    $parent = User::factory()->inactive()->create(['requested_role' => UserRole::Parent, 'registration_note' => 'nie pamiętam']);

    Livewire::actingAs($this->admin)->test(ParentAssignment::class)
        ->set('otherChild.'.$parent->id, 'mini-'.$this->child->id)
        ->call('assign', $parent->id)
        ->call('unassignMini', $parent->id, $this->child->id);

    expect($parent->fresh()->is_active)->toBeTrue()->and($parent->miniChildren()->count())->toBe(0);
});

test('obcy rodzic nie zmieni zgody na zdjęcie cudzego dziecka', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('profile.mini-consent', $this->child->id), ['image_consent' => '1'])->assertForbidden();
});

test('trener minisiatkówki widzi rodziców ze swojej grupy', function () {
    $trainer = User::factory()->create();
    $trainer->assignRole(UserRole::Trainer);
    $trainer->miniGroups()->attach($this->group);
    registerMiniParent();
    $parent = User::firstWhere('email', 'ewa@example.com');

    expect(ParentAssignment::pendingFor($trainer->fresh())->whereKey($parent->id)->exists())->toBeTrue()
        ->and(ParentAssignment::miniChildrenFor($trainer->fresh())->pluck('id')->all())->toBe([$this->child->id]);

    $other = User::factory()->create();
    $other->assignRole(UserRole::Trainer);
    expect(ParentAssignment::pendingFor($other->fresh())->whereKey($parent->id)->exists())->toBeFalse();
});
