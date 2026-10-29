<?php

use App\Livewire\Admin\AlumniManager;
use App\Livewire\Admin\SupportManager;
use App\Livewire\Admin\TrainingPlanner;
use App\Models\Alumnus;
use App\Models\Article;
use App\Models\Player;
use App\Models\PushSubscription;
use App\Models\ShopProduct;
use App\Models\SupportPackage;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use App\UserRole;
use Database\Seeders\StartSeeder;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Livewire\Livewire;

test('alumni are managed in the panel and shown on their page and the homepage', function () {
    $this->actingAs(User::factory()->editor()->create());

    Livewire::test(AlumniManager::class)
        ->call('create')
        ->set('name', 'Mateusz Malinowski')
        ->set('level', 'PlusLiga')
        ->set('achievements', "Mistrz Polski juniorów\nPowołanie do kadry")
        ->set('is_featured', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Alumnus::sole()->achievementList())->toHaveCount(2);

    $this->get(route('alumni.index'))->assertOk()->assertSee('Mateusz Malinowski')->assertSee('Powołanie do kadry');
    $this->get('/')->assertOk()->assertSee('Nasi wychowankowie')->assertSee('Mateusz Malinowski');
    $this->get('/panel/alumni')->assertOk()->assertSee('Mateusz Malinowski');
});

test('a trainer plans trainings of own teams on several days and sees busy courts', function () {
    $trainer = User::factory()->trainer()->create();
    $own = Team::factory()->create(['name' => 'Kadeci', 'trainer_id' => $trainer->id]);
    $other = Team::factory()->create(['name' => 'Młodzicy']);
    TrainingSession::create(['team_id' => $other->id, 'weekday' => 1, 'starts_at' => '16:30:00', 'ends_at' => '18:00:00', 'venue' => 'court1']);

    $this->actingAs($trainer);
    Livewire::test(TrainingPlanner::class)
        ->set('team_id', $own->id)
        ->set('weekdays', [1, 3])
        ->set('starts_at', '16:00')
        ->set('ends_at', '17:30')
        ->set('venue', 'court1')
        ->assertSee('To miejsce jest już zajęte')
        ->call('save')
        ->assertHasNoErrors()
        ->set('team_id', $other->id)
        ->set('weekdays', [2])
        ->call('save')
        ->assertHasErrors('team_id');

    expect(TrainingSession::where('team_id', $own->id)->pluck('weekday')->sort()->values()->all())->toBe([1, 3]);

    $this->get(route('trainings.index'))->assertOk()->assertSee('Kadeci')->assertSee('16:00–17:30')->assertSee('Sektor I');
    $this->get(route('teams.show', $own))->assertOk()->assertSee('Plan treningów')->assertSee('Środa');
});

test('staff members can plan trainings and fans cannot', function () {
    $staff = User::factory()->create(['role' => UserRole::Staff]);

    $this->actingAs($staff)->get('/panel/training-schedule')->assertOk();
    $this->actingAs(User::factory()->create())->get('/panel/training-schedule')->assertForbidden();
});

test('the support page shows tax, packages and gadgets that order through the contact form', function () {
    app(SiteSettings::class)->set(['support_krs' => '0000123456']);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SupportManager::class)
        ->call('createPackage')->set('name', 'Partner złoty')->set('benefits', "Logo na koszulkach\nBaner w hali")->call('save')
        ->call('createProduct')->set('name', 'Szalik kibica')->set('price', '49,90')->call('save')
        ->assertHasNoErrors();

    expect(ShopProduct::sole()->priceLabel())->toBe('49,90 zł')
        ->and(SupportPackage::sole()->benefitList())->toBe(['Logo na koszulkach', 'Baner w hali']);

    $this->get(route('support.index'))->assertOk()
        ->assertSee('0000123456')
        ->assertSee('Partner złoty')
        ->assertSee('Szalik kibica')
        ->assertSee('subject=Zam', false);
    $this->get('/panel/support')->assertOk()->assertSee('Szalik kibica');

    $this->get(route('contact.index', ['subject' => 'Zamówienie: Szalik kibica']))->assertSee('value="Zamówienie: Szalik kibica"', false);
});

test('browsers subscribe to push notifications and new articles notify them once', function () {
    $this->getJson(route('push.key'))->assertOk()->assertJsonStructure(['publicKey']);

    $this->postJson(route('push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'klucz', 'auth' => 'sekret']])->assertCreated();
    $this->postJson(route('push.subscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'klucz2', 'auth' => 'sekret']])->assertCreated();
    expect(PushSubscription::count())->toBe(1);

    $push = Mockery::mock(PushNotifier::class);
    $push->shouldReceive('enabled')->andReturnTrue();
    $push->shouldReceive('articlePublished')->once();
    $this->app->instance(PushNotifier::class, $push);

    $article = Article::factory()->create(['status' => 'published', 'published_at' => now(), 'author_id' => User::factory()->create()->id]);
    $article->update(['title' => 'Poprawiony tytuł', 'published_at' => now()->subMinute()]);
    app(DeferredCallbackCollection::class)->invoke();

    $this->deleteJson(route('push.unsubscribe'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc'])->assertOk();
    expect(PushSubscription::count())->toBe(0);
});

test('push notifications can be turned off in the settings', function () {
    app(SiteSettings::class)->set(['push_enabled' => false]);

    $this->getJson(route('push.key'))->assertNotFound();
    $this->get('/')->assertOk()->assertDontSee('data-push-config', false);
    expect(app(PushNotifier::class)->send('Tytuł', 'Treść', url('/')))->toBe(0);
});

test('the start seeder adds the best known alumnus, the out-of-town page and sponsor packages', function () {
    $this->seed(StartSeeder::class);
    $this->seed(StartSeeder::class);

    expect(Alumnus::where('name', 'Mateusz Malinowski')->count())->toBe(1)
        ->and(SupportPackage::count())->toBe(3);
    $this->get(route('pages.show', 'spoza-pily'))->assertOk()->assertSee('Liceum Ogólnokształcącym Mistrzostwa Sportowego');
});

test('the start seeder does not fail when another cadet already wears number 10', function () {
    $this->seed(StartSeeder::class);
    Player::where('first_name', 'Marcel')->delete();
    Player::create(['first_name' => 'Jan', 'last_name' => 'Nowak', 'team_id' => Team::firstWhere('slug', 'kadeci')->id, 'jersey_number' => 10, 'position' => 'setter', 'is_active' => true]);

    $this->seed(StartSeeder::class);

    expect(Player::firstWhere('first_name', 'Marcel')->jersey_number)->toBe(11);
});

test('the main court can be chosen as a training venue', function () {
    expect(TrainingSession::VENUES)->toHaveKey('main', 'Główne boisko');

    $session = TrainingSession::create(['team_id' => Team::factory()->create()->id, 'weekday' => 2, 'starts_at' => '17:00:00', 'ends_at' => '18:30:00', 'venue' => 'main']);

    expect($session->venueLabel())->toBe('Główne boisko')
        ->and($session->forceFill(['venue' => 'court1'])->venueLabel())->toBe('Sektor I');

    app(SiteSettings::class)->set(['venue_court1' => 'Boisko przy oknach']);
    expect($session->venueLabel())->toBe('Boisko przy oknach')
        ->and(TrainingSession::courtLabel('Boisko 1'))->toBe('Boisko przy oknach');
});

test('wychowanka z pustymi polami da się otworzyć do edycji', function () {
    $this->actingAs(User::factory()->editor()->create());
    $alumnus = Alumnus::create(['name' => 'Jan Kowalski', 'position' => null, 'level' => null, 'is_published' => true]);

    Livewire::test(AlumniManager::class)
        ->call('edit', $alumnus->id)
        ->assertHasNoErrors()
        ->assertSet('name', 'Jan Kowalski')
        ->assertSet('position', '');
});
