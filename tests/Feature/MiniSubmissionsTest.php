<?php

use App\Livewire\Admin\ContentProposalManager;
use App\Livewire\Admin\MiniApprovals;
use App\Livewire\MiniSubmissions;
use App\Models\Article;
use App\Models\ContentProposal;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\MiniTrip;
use App\Models\User;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'czworki');
    $this->child = MiniPlayer::create(['mini_group_id' => $this->group->id, 'first_name' => 'Antek', 'last_name' => 'Kowalski']);
});

test('a mini trainer submits a tournament that waits for approval and then shows in the calendar', function () {
    $trainer = User::factory()->create(['role' => UserRole::MiniTrainer]);
    $this->actingAs($trainer);

    Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('trip.title', 'Turniej Mikołajkowy')->set('trip.starts_on', now()->addWeek()->toDateString())->set('trip.city', 'Chodzież')
        ->call('saveTrip')->assertHasNoErrors();

    $trip = MiniTrip::sole();
    expect($trip->status)->toBe(MiniTrip::STATUS_PENDING)->and($trip->submitted_by)->toBe($trainer->id);
    $this->get(route('mini.show', $this->group))->assertDontSee('Turniej Mikołajkowy');

    $this->actingAs(User::factory()->trainer()->create());
    expect(MiniApprovals::pendingCount())->toBe(1);
    Livewire::test(MiniApprovals::class)->assertSee('Turniej Mikołajkowy')->call('approveTrip', $trip->id);

    expect($trip->fresh()->status)->toBe(MiniTrip::STATUS_PUBLISHED);
    $this->get(route('mini.show', $this->group))->assertSee('Turniej Mikołajkowy');
});

test('results from a mini coordinator wait for approval and a manager saves them at once', function () {
    $trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'city' => 'Leszno', 'starts_on' => now()->subDay()]);
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniCoordinator]));

    Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('resultsTrip', (string) $trip->id)
        ->assertCount('rows', 1)
        ->set('rows.0.place', '2')->set('rows.0.teams_count', '12')->set('rows.0.players', [$this->child->id])
        ->call('saveResults')->assertHasNoErrors();

    expect($trip->teams()->count())->toBe(0)->and($trip->fresh()->pending_results['teams'][0]['place'])->toBe('2');

    $this->actingAs(User::factory()->trainer()->create());
    Livewire::test(MiniApprovals::class)->assertSee('2. miejsce')->assertSee('Antek Kowalski')->call('approveResults', $trip->id);

    $team = $trip->teams()->with('players')->sole();
    expect($team->place)->toBe(2)->and($team->resultLabel())->toBe('2. miejsce | 12 drużyn')
        ->and($team->players->pluck('id')->all())->toBe([$this->child->id])->and($trip->fresh()->pending_results)->toBeNull();

    Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('resultsTrip', (string) $trip->id)->set('rows.0.place', '1')->call('saveResults')->assertHasNoErrors();
    expect($team->fresh()->place)->toBe(1);
});

test('a report about a mini tournament becomes an article linked to that tournament', function () {
    $trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'city' => 'Leszno', 'starts_on' => now()->subDay()]);
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniTrainer]));

    Livewire::test(MiniSubmissions::class)
        ->set('reportTrip', (string) $trip->id)->set('reportTitle', 'Srebro w Lesznie')->set('reportContent', 'Nasze czwórki zagrały świetny turniej i wróciły z medalem.')
        ->call('sendReport')->assertHasNoErrors();

    $proposal = ContentProposal::sole();
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ContentProposalManager::class)->assertSee('Minisiatkówka: Turniej w Lesznie')->call('approve', $proposal->id);

    expect(Article::sole()->mini_trip_id)->toBe($trip->id);
    $this->get(route('mini.show', $this->group))->assertOk();
});

test('the submission tab is only for mini roles and staff, and groups are tabs and miniatures', function () {
    $this->get(route('mini.show', $this->group))->assertOk()->assertSee('Trójki')->assertDontSee('Turnieje, wyniki, dzieci');
    $this->actingAs(User::factory()->parent()->create())->get(route('mini.show', $this->group))->assertDontSee('Turnieje, wyniki, dzieci');
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniTrainer]))->get(route('mini.show', $this->group))->assertSee('Turnieje, wyniki, dzieci');
    $this->actingAs(User::factory()->parent()->create());
    Livewire::test(MiniSubmissions::class)->assertForbidden();

    $this->get(route('teams.index'))->assertOk()->assertSee(route('mini.show', $this->group));
});

test('a mini coordinator adds, moves and retires children without profiles', function () {
    $threes = MiniGroup::firstWhere('slug', 'trojki');
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniCoordinator]));

    $component = Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('section', 'dzieci')
        ->set('child.first_name', 'Hania')->set('child.last_name', 'Kot')->set('child.birth_year', '2015')
        ->call('addChild')->assertHasNoErrors();

    $hania = MiniPlayer::firstWhere('first_name', 'Hania');
    expect($hania->mini_group_id)->toBe($this->group->id)->and($hania->image_consent)->toBeFalse();

    $component->call('moveChild', $hania->id, (string) $threes->id)->call('retireChild', $this->child->id);
    expect($hania->fresh()->mini_group_id)->toBe($threes->id)->and($this->child->fresh()->is_active)->toBeFalse();
});

test('group icons use the colour set in the panel', function () {
    $this->group->update(['color' => '#123abc']);
    $this->get(route('mini.show', $this->group))->assertOk()->assertSee('color: #123abc', false);
    expect(MiniGroup::firstWhere('slug', 'trojki')->iconColor())->toBe(MiniGroup::DEFAULT_COLORS[3]);
});
