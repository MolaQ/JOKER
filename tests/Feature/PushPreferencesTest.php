<?php

use App\Livewire\Admin\PushComposer;
use App\Models\PushSubscription;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Livewire\Livewire;

function pushSubscription(array $attributes = []): PushSubscription
{
    return PushSubscription::create([
        'endpoint_hash' => PushSubscription::hashEndpoint($endpoint = 'https://fcm.googleapis.com/fcm/send/'.uniqid()),
        'endpoint' => $endpoint,
        'public_key' => 'klucz',
        'auth_token' => 'sekret',
    ] + $attributes);
}

test('a subscription receives only the topics and teams the fan picked', function () {
    $all = pushSubscription();
    $picky = pushSubscription(['topics' => ['results', 'upcoming'], 'teams' => [5]]);
    $guest = pushSubscription(['topics' => ['tournaments', 'upcoming'], 'teams' => [], 'participants' => [42]]);

    expect($all->wants(['topic' => 'articles']))->toBeTrue()
        ->and($picky->wants(['topic' => 'articles']))->toBeFalse()
        ->and($picky->wants(['topic' => 'results', 'teams' => [5]]))->toBeTrue()
        ->and($picky->wants(['topic' => 'results', 'teams' => [7]]))->toBeFalse()
        ->and($all->wants(['topic' => 'tournaments', 'teams' => [], 'participants' => [42]]))->toBeFalse()
        ->and($guest->wants(['topic' => 'tournaments', 'teams' => [], 'participants' => [42]]))->toBeTrue()
        ->and($guest->wants(['topic' => 'results', 'teams' => [5], 'participants' => [42]]))->toBeFalse()
        ->and($picky->wants([]))->toBeTrue();
});

test('the fan saves topics, teams and followed tournament teams for their browser', function () {
    $subscription = pushSubscription();

    $this->postJson(route('push.preferences'), ['endpoint' => $subscription->endpoint])
        ->assertOk()->assertExactJson(['topics' => null, 'teams' => null, 'participants' => null]);

    $this->postJson(route('push.preferences'), ['endpoint' => $subscription->endpoint, 'topics' => ['results', 'upcoming'], 'teams' => [3, '3'], 'participants' => [9]])
        ->assertOk()->assertExactJson(['topics' => ['results', 'upcoming'], 'teams' => [3], 'participants' => [9]]);

    $this->postJson(route('push.preferences'), ['endpoint' => $subscription->endpoint, 'topics' => null])
        ->assertOk()->assertJson(['topics' => null, 'teams' => [3]]);

    $this->postJson(route('push.preferences'), ['endpoint' => $subscription->endpoint, 'topics' => ['spam']])->assertUnprocessable();
    $this->postJson(route('push.preferences'), ['endpoint' => 'https://example.com/nieznany'])->assertNotFound();
});

test('the bell panel lists only the topics the admin left on', function () {
    app(SiteSettings::class)->set(['push_galleries' => false]);

    $this->get('/')->assertOk()
        ->assertSee('data-push-panel', false)
        ->assertSee('value="articles"', false)
        ->assertDontSee('value="galleries"', false);
});

test('notification texts come from the editable templates', function () {
    app(SiteSettings::class)->set(['push_tpl_win_title' => 'Brawo {druzyna}! {wynik}']);

    expect(app(PushNotifier::class)->text('push_tpl_win_title', ['druzyna' => 'Kadetki', 'wynik' => '3:1']))->toBe('Brawo Kadetki! 3:1')
        ->and(app(PushNotifier::class)->text('push_tpl_upcoming_title', ['minuty' => 15, 'nazwa' => 'Memoriał · Kadetki']))->toBe('Za 15 min: Memoriał · Kadetki');
});

test('a finished tournament match and a match about to start send notifications once', function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'city' => 'Piła', 'is_organizer' => true]);
    $category = $tournament->categories()->create(['name' => 'Kadetki']);
    [$a, $b] = collect(['MKS Kalisz', 'Sparta Złotów'])->map(fn ($name) => $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => $name])->id]))->all();
    $match = $category->matches()->create(['stage' => 'group', 'home_participant_id' => $a->id, 'away_participant_id' => $b->id, 'starts_at' => now()->addMinutes(10)]);

    $push = Mockery::mock(PushNotifier::class);
    $push->shouldReceive('enabled')->andReturnTrue();
    $push->shouldReceive('upcoming')->once()->withArgs(fn ($upcoming, int $minutes) => $upcoming->is($match) && $minutes === 10);
    $push->shouldReceive('tournamentMatchFinished')->once();
    $this->app->instance(PushNotifier::class, $push);

    $this->artisan('joker:push-upcoming')->assertSuccessful();
    $this->artisan('joker:push-upcoming')->assertSuccessful();

    $match->update(['is_live' => true, 'home_sets' => 1, 'away_sets' => 0]);
    $match->update(['is_live' => false, 'home_sets' => 2, 'away_sets' => 0]);
    $match->update(['sets_score' => [[25, 10], [25, 12]]]);
    app(DeferredCallbackCollection::class)->invoke();
});

test('tournament notifications go to fans of our teams and followers of the playing teams', function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'city' => 'Piła', 'is_organizer' => true]);
    $category = $tournament->categories()->create(['name' => 'Kadetki']);
    $ours = $category->participants()->create(['competitor_type' => 'team', 'competitor_id' => Team::factory()->create()->id]);
    $guest = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'MKS Kalisz'])->id]);
    $other = $category->participants()->create(['competitor_type' => 'rival_team', 'competitor_id' => RivalTeam::create(['full_name' => 'Sparta'])->id]);

    $fan = pushSubscription();
    $follower = pushSubscription(['topics' => ['tournaments'], 'teams' => [], 'participants' => [$other->id]]);

    $notifier = new class(app(SiteSettings::class)) extends PushNotifier
    {
        public array $audiences = [];

        public function send(string $title, string $body, string $url, ?string $tag = null, ?array $userIds = null, array $audience = []): int
        {
            $this->audiences[] = [$title, $body, $audience];

            return 0;
        }
    };

    $withOurs = $category->matches()->create(['stage' => 'group', 'home_participant_id' => $ours->id, 'away_participant_id' => $guest->id, 'starts_at' => now(), 'home_sets' => 2, 'away_sets' => 1]);
    $guestsOnly = $category->matches()->create(['stage' => 'group', 'home_participant_id' => $guest->id, 'away_participant_id' => $other->id, 'starts_at' => now(), 'home_sets' => 0, 'away_sets' => 2]);
    $notifier->tournamentMatchFinished($withOurs->fresh());
    $notifier->tournamentMatchFinished($guestsOnly->fresh());

    [[$title, $body, $first], [, , $second]] = $notifier->audiences;
    expect($title)->toBe('Memoriał · Kadetki')
        ->and($body)->toContain('2:1')
        ->and($fan->wants($first))->toBeTrue()
        ->and($follower->wants($first))->toBeFalse()
        ->and($fan->wants($second))->toBeFalse()
        ->and($follower->wants($second))->toBeTrue();
});

test('an editor sends a notification by hand to a chosen group of fans', function () {
    $this->actingAs(User::factory()->admin()->create());
    pushSubscription();
    pushSubscription(['topics' => ['articles']]);

    $push = Mockery::mock(PushNotifier::class);
    $push->shouldReceive('enabled')->andReturnTrue();
    $push->shouldReceive('countAudience')->andReturnUsing(fn (array $audience) => PushSubscription::all()->filter->wants($audience)->count());
    $push->shouldReceive('send')->once()->withArgs(fn ($title, $body, $url, $tag, $userIds, $audience) => $title === 'Zmiana godziny' && $audience === ['topic' => 'results'] && str_ends_with($url, '/matches'))->andReturn(1);
    $this->app->instance(PushNotifier::class, $push);

    $this->get(route('admin.push.index'))->assertOk()->assertSee('Wyślij powiadomienie');

    Livewire::test(PushComposer::class)
        ->assertSee('Dostanie:')
        ->set('target', 'topic:results')
        ->assertSeeHtml('<strong>1</strong> z 2')
        ->call('send')
        ->assertHasErrors(['title', 'body'])
        ->set('title', 'Zmiana godziny')
        ->set('body', 'Mecz o 12:00')
        ->set('url', '/matches')
        ->call('send')
        ->assertHasNoErrors();
});
