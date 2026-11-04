<?php

use App\Livewire\Admin\ParentAssignment;
use App\Livewire\PlayerProposalForm;
use App\Models\MiniGroup;
use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AccountActivated;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    app(SiteSettings::class)->set(['module_mini' => '1']);
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'trojki');
    $this->child = Player::create(['mini_group_id' => $this->group->id, 'first_name' => 'Zosia', 'last_name' => 'Wiśniewska', 'birth_year' => 2016, 'image_consent' => false, 'is_active' => true]);
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

test('dziecko z minisiatkówki ma zwykły profil zawodnika z grupą zamiast drużyny', function () {
    expect($this->child->isMini())->toBeTrue()
        ->and($this->group->players()->pluck('id')->all())->toBe([$this->child->id]);

    $this->get(route('mini.show', $this->group))->assertOk()->assertSee(route('players.show', $this->child), false);
    $this->get(route('players.show', $this->child))->assertOk()
        ->assertSee('Minisiatkówka: Trójki')->assertSee('2016');

    // Podpis według ustawień mini także na profilu i w adresie.
    app(SiteSettings::class)->set(['mini_child_names' => 'initial']);
    expect($this->child->fresh()->displayName())->toBe('Zosia W.')
        ->and(route('players.show', $this->child->fresh()))->toEndWith('/zosia-w');

    // Zdjęcie tylko za zgodą rodzica.
    $this->child->update(['photo_path' => 'mini/zosia.jpg']);
    expect($this->child->fresh()->photoUrl())->toBeNull();
    $this->child->update(['image_consent' => true]);
    expect($this->child->fresh()->photoUrl())->not->toBeNull();

    // Po przejściu do drużyny dziecko znika z grupy mini.
    $team = Team::factory()->create();
    $this->child->update(['team_id' => $team->id]);
    expect($this->child->fresh()->isMini())->toBeFalse()->and($this->group->players()->count())->toBe(0);
});

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

test('klub przypisuje rodzica do dziecka z mini i rodzic prowadzi jego profil', function () {
    registerMiniParent();
    $parent = User::firstWhere('email', 'ewa@example.com');

    Livewire::actingAs($this->admin)->test(ParentAssignment::class)
        ->assertSeeText('Minisiatkówka: Trójki')
        ->assertSee('Przypisz do: Zosia Wiśniewska (Minisiatkówka: Trójki)')
        ->call('assign', $parent->id, $this->child->id)
        ->assertSee('Przypisane dzieci');

    $parent->refresh();
    expect($parent->is_active)->toBeTrue()
        ->and($parent->hasRole(UserRole::Parent))->toBeTrue()
        ->and($parent->children->pluck('id')->all())->toBe([$this->child->id]);
    Notification::assertSentTo($parent, AccountActivated::class);

    // Rodzic widzi profil dziecka w „Mój profil”, ustawia zgodę i może proponować zmiany jak przy innych zawodnikach.
    $this->actingAs($parent)->get('/my-profile')->assertSee('Zosia Wiśniewska')->assertSee('Minisiatkówka: Trójki');
    $this->put(route('profile.consent', $this->child), ['image_consent' => '1'])->assertRedirect();
    expect($this->child->fresh()->image_consent)->toBeTrue()
        ->and($this->child->fresh()->isEditableProposalBy($parent))->toBeTrue()
        ->and($this->child->fresh()->isReviewableBy($this->admin))->toBeTrue();
    Livewire::actingAs($parent)->test(PlayerProposalForm::class, ['player' => $this->child->fresh()])->assertOk();

    // Gdy dziecko kończy z minisiatkówką, konto rodzica przestaje działać.
    $this->child->update(['is_active' => false]);
    expect($parent->fresh()->is_active)->toBeFalse();
});

test('trener minisiatkówki widzi rodziców i dzieci ze swojej grupy', function () {
    $trainer = User::factory()->create();
    $trainer->assignRole(UserRole::Trainer);
    $trainer->miniGroups()->attach($this->group);
    registerMiniParent();
    $parent = User::firstWhere('email', 'ewa@example.com');

    expect(ParentAssignment::pendingFor($trainer->fresh())->whereKey($parent->id)->exists())->toBeTrue()
        ->and(Player::managedBy($trainer->fresh())->pluck('id')->all())->toBe([$this->child->id]);

    $other = User::factory()->create();
    $other->assignRole(UserRole::Trainer);
    expect(ParentAssignment::pendingFor($other->fresh())->whereKey($parent->id)->exists())->toBeFalse();
});

test('migracja przenosi dawne dzieci mini do profili zawodników razem ze składami, raz', function () {
    $tripId = DB::table('mini_trips')->insertGetId(['title' => 'Turniej', 'starts_on' => '2026-10-10', 'status' => 'published', 'created_at' => now(), 'updated_at' => now()]);
    $teamId = DB::table('mini_trip_teams')->insertGetId(['mini_trip_id' => $tripId, 'mini_group_id' => $this->group->id, 'name' => 'Joker 1', 'created_at' => now(), 'updated_at' => now()]);
    $oldId = DB::table('mini_players')->insertGetId(['mini_group_id' => $this->group->id, 'first_name' => 'Antek', 'last_name' => 'Kowalski', 'birth_year' => 2015, 'number' => 4, 'image_consent' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    DB::table('mini_player_mini_trip_team')->insert(['mini_trip_team_id' => $teamId, 'mini_player_id' => $oldId]);
    DB::table('mini_trips')->where('id', $tripId)->update(['pending_results' => json_encode(['teams' => [['name' => 'Joker 1', 'players' => [$oldId]]]])]);

    $migration = require database_path('migrations/2026_11_04_100005_move_mini_players_to_players.php');
    $migration->up();
    $migration->up();

    $antek = Player::firstWhere('mini_player_id', $oldId);
    expect(Player::where('first_name', 'Antek')->count())->toBe(1)
        ->and($antek->isMini())->toBeTrue()
        ->and($antek->jersey_number)->toBe(4)
        ->and($antek->birth_year)->toBe(2015)
        ->and($antek->image_consent)->toBeTrue()
        ->and($antek->miniTripTeams()->pluck('mini_trip_teams.id')->all())->toBe([$teamId])
        ->and(json_decode(DB::table('mini_trips')->find($tripId)->pending_results, true)['teams'][0]['players'])->toBe([$antek->id]);

    $this->get(route('players.show', $antek))->assertOk()->assertSee('Turnieje minisiatkówki')->assertSee('Turniej');
});
