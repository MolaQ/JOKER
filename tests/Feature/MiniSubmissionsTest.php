<?php

use App\Livewire\Admin\ContentProposalManager;
use App\Livewire\Admin\MiniApprovals;
use App\Livewire\MiniSubmissions;
use App\Models\Article;
use App\Models\ContentProposal;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\Player;
use App\Models\User;
use App\Support\AgeColors;
use App\Support\SiteSettings;
use App\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    MiniGroup::query()->update(['is_active' => true]);
    $this->group = MiniGroup::firstWhere('slug', 'czworki');
    $this->child = Player::create(['mini_group_id' => $this->group->id, 'first_name' => 'Antek', 'last_name' => 'Kowalski']);
});

test('a mini trainer submits a tournament that waits for approval and then shows in the calendar', function () {
    $trainer = User::factory()->create(['role' => UserRole::MiniTrainer]);
    $this->actingAs($trainer);

    Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('trip.title', 'Turniej Mikołajkowy')->set('trip.starts_on', now()->addWeek()->toDateString())->set('trip.city', 'Chodzież')
        ->call('saveTrip')->assertHasNoErrors();

    $trip = MiniTrip::sole();
    expect($trip->status)->toBe(MiniTrip::STATUS_PENDING)->and($trip->submitted_by)->toBe($trainer->id);
    // Zgłaszający widzi swój turniej w przeglądzie z oznaczeniem, publicznie go nie ma.
    $this->get(route('mini.show', $this->group))->assertSee('Turniej Mikołajkowy')->assertSee('czeka na zatwierdzenie');
    auth()->logout();
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
        ->call('saveChild')->assertHasNoErrors();

    $hania = Player::firstWhere('first_name', 'Hania');
    expect($hania->mini_group_id)->toBe($this->group->id)->and($hania->image_consent)->toBeFalse();

    $component->call('moveChild', $hania->id, (string) $threes->id)->call('retireChild', $this->child->id);
    expect($hania->fresh()->mini_group_id)->toBe($threes->id)->and($this->child->fresh()->is_active)->toBeFalse();
});

test('a mini coordinator fills in birth date, number, photo and consent and edits a child later', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniCoordinator]));

    $component = Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->set('section', 'dzieci')
        ->set('child.first_name', 'Ola')->set('child.last_name', 'Lis')->set('child.birth_date', '2016-05-14')->set('child.number', '9')
        ->set('childPhoto', UploadedFile::fake()->image('ola.jpg', 600, 600))
        ->set('child.image_consent', true)
        ->call('saveChild')->assertHasNoErrors();

    $ola = Player::firstWhere('first_name', 'Ola');
    expect($ola->birth_date->format('Y-m-d'))->toBe('2016-05-14')
        ->and($ola->birth_year)->toBe(2016)
        ->and($ola->jersey_number)->toBe(9)
        ->and($ola->image_consent)->toBeTrue()
        ->and($ola->image_consent_changed_at)->not->toBeNull()
        ->and($ola->photo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($ola->photo_path);

    $consentBefore = $this->child->fresh()->image_consent;

    $component->call('editChild', $this->child->id)
        ->assertSet('editingChildId', $this->child->id)
        ->set('child.birth_date', '2015-02-03')->set('child.number', '4')
        ->set('childPhoto', UploadedFile::fake()->image('dziecko.jpg', 600, 600))
        ->call('saveChild')->assertHasNoErrors()
        ->assertSet('editingChildId', null);

    $child = $this->child->fresh();
    expect($child->birth_year)->toBe(2015)->and($child->jersey_number)->toBe(4)->and($child->photo_path)->not->toBeNull()
        ->and($child->image_consent)->toBe($consentBefore);
});

test('group icons use the colour set in the panel', function () {
    app(SiteSettings::class)->set([AgeColors::key('mini4') => '#123abc']);
    $this->get(route('mini.show', $this->group))->assertOk()->assertSee('fill="#123abc"', false);
    expect(MiniGroup::firstWhere('slug', 'trojki')->iconColor())->toBe(AgeColors::forMini(3));
});

test('the squad can be set before the tournament and the overview filters trips', function () {
    $this->actingAs(User::factory()->admin()->create());
    $kid = Player::create(['mini_group_id' => $this->group->id, 'first_name' => 'Nataniel', 'last_name' => 'Lickindorf', 'is_active' => true]);
    $future = MiniTrip::create(['title' => 'Kinder Sport Wałcz', 'starts_on' => today()->addWeek(), 'city' => 'Wałcz', 'status' => MiniTrip::STATUS_PUBLISHED]);
    $future->teams()->create(['mini_group_id' => $this->group->id, 'name' => 'Joker 1']);
    MiniTrip::create(['title' => 'Turniej w Trzciance', 'starts_on' => today()->subWeek(), 'city' => 'Trzcianka', 'status' => MiniTrip::STATUS_PUBLISHED]);

    $component = Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->assertSee('Kinder Sport Wałcz')->assertSee('Turniej w Trzciance')->assertSee('brak składu')
        ->set('search', 'wałcz')->assertSee('Kinder Sport Wałcz')->assertDontSee('Turniej w Trzciance')
        ->set('search', '')->set('filterWhen', 'past')->assertDontSee('Kinder Sport Wałcz')
        ->set('filterWhen', '')
        ->call('openTrip', $future->id, 'wyniki')
        ->assertSet('section', 'wyniki')
        ->assertSee('Turniej jeszcze się nie odbył')
        ->set('rows.0.players', [$kid->id])
        ->call('saveResults')->assertHasNoErrors();

    expect($future->teams()->sole()->players->pluck('id')->all())->toBe([$kid->id]);
    $component->assertSee('1 dziecko');
});

test('a mini report has a lead, formatted text and tags children by their public name', function () {
    app(SiteSettings::class)->set(['mini_child_names' => 'initial']);
    Player::create(['mini_group_id' => $this->group->id, 'first_name' => 'Ania', 'last_name' => 'Kowalska', 'is_active' => true]);
    $trip = MiniTrip::create(['title' => 'Turniej w Lesznie', 'city' => 'Leszno', 'starts_on' => now()->subDay()]);
    $trainer = User::factory()->create(['role' => UserRole::MiniTrainer]);
    $this->actingAs($trainer);

    $this->getJson(route('mini.mentions'))->assertOk()->assertJsonFragment(['name' => 'Ania Kowalska', 'team' => $this->group->name]);

    Livewire::test(MiniSubmissions::class)
        ->set('reportTrip', (string) $trip->id)->set('reportTitle', 'Srebro w Lesznie')
        ->set('reportLead', '<p>Trójki wróciły z medalem.</p>')
        ->set('reportContent', '<p>Świetnie grała <strong>@Ania Kowalska</strong>, brawo dla całej drużyny i rodziców!</p><script>alert(1)</script>')
        ->call('sendReport')->assertHasNoErrors();

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ContentProposalManager::class)->call('approve', ContentProposal::sole()->id);

    $article = Article::sole();
    expect($article->excerpt)->toContain('Trójki wróciły z medalem.')
        ->and($article->content)->toContain('<strong>')->not->toContain('script');

    auth()->logout();
    $this->get(route('articles.show', $article))->assertOk()->assertSee('Ania K.')->assertDontSee('Ania Kowalska')
        ->assertSee(route('players.show', Player::firstWhere('first_name', 'Ania'), false));
});

test('the overview on a group page lists only that group tournaments and teams', function () {
    $other = MiniGroup::where('id', '!=', $this->group->id)->first();
    $own = MiniTrip::create(['title' => 'Turniej Czwórek', 'city' => 'Piła', 'starts_on' => now()->addWeek()]);
    $own->teams()->create(['mini_group_id' => $this->group->id, 'name' => 'Joker Czwórki']);
    $own->teams()->create(['mini_group_id' => $other->id, 'name' => 'Joker Inni']);
    $foreign = MiniTrip::create(['title' => 'Turniej Obcy', 'city' => 'Ujście', 'starts_on' => now()->addWeek()]);
    $foreign->teams()->create(['mini_group_id' => $other->id, 'name' => 'Joker Obcy']);
    $this->actingAs(User::factory()->create(['role' => UserRole::MiniTrainer]));

    Livewire::test(MiniSubmissions::class, ['groupId' => $this->group->id])
        ->assertSee('Turniej Czwórek')->assertSee('Joker Czwórki')
        ->assertDontSee('Turniej Obcy')->assertDontSee('Joker Inni')->assertDontSee('Wszystkie kategorie');

    Livewire::test(MiniSubmissions::class)->assertSee('Turniej Obcy')->assertSee('Joker Inni')->assertSee('Wszystkie kategorie');
});
