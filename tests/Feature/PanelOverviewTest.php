<?php

use App\Livewire\Admin\CompetitionManager;
use App\Models\Competition;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\ScheduleImporter;
use App\Support\Approvals;
use App\Support\SiteSettings;
use Database\Seeders\StartSeeder;
use Livewire\Livewire;

test('the dashboard shows compact statistics and what waits for approval', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['is_active' => false]);

    $this->actingAs($admin)->get('/panel')->assertOk()
        ->assertSee('Bilans sezonu')
        ->assertSee('Odwiedziny 7 dni')
        ->assertSee('Czeka na zatwierdzenie')
        ->assertSee('Konta do aktywacji');
});

test('everything awaiting approval is on one page with a tab per section', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['is_active' => false]);

    $this->actingAs($admin)->get('/panel/approvals')->assertRedirect(route('admin.accounts.index'));

    $this->get('/panel/comments')->assertOk()
        ->assertSee('Do zatwierdzenia')
        ->assertSee('Propozycje treści')
        ->assertSee('Oznaczenia na zdjęciach')
        ->assertSee('Zmiany od zawodników');

    expect(Approvals::pendingCountFor($admin))->toBeGreaterThanOrEqual(1);
});

test('a trainer sees only the approval sections their role allows', function () {
    $trainer = User::factory()->trainer()->create();
    $sections = Approvals::sectionsFor($trainer)->keys()->all();

    expect($sections)->not->toContain('propozycje', 'komentarze');
    $this->actingAs($trainer)->get('/panel/proposals')->assertForbidden();

    $fan = User::factory()->create();
    $this->actingAs($fan)->get('/panel/approvals')->assertForbidden();
});

test('the club crest is the default logo of every club team and in the settings', function () {
    $team = Team::factory()->create(['logo_path' => null]);

    expect($team->logoPath())->toEndWith('img/logo-256.webp');

    $team->update(['logo_path' => 'teams/own.png']);
    expect($team->fresh()->logoPath())->toBe('teams/own.png');

    $this->actingAs(User::factory()->admin()->create())->get('/panel/system')->assertOk()->assertSee('img/logo-256.webp')->assertSee('domyślny');
});

test('the start seeder adds this season competitions with the union pages', function () {
    $this->seed(StartSeeder::class);
    $this->seed(StartSeeder::class);

    $competitions = Competition::with('teams')->get();
    expect($competitions)->toHaveCount(5)
        ->and($competitions->firstWhere('name', 'Liga Kadetów')->teams->pluck('slug')->all())->toContain('kadeci', 'kadeci-ii')
        ->and($competitions->every(fn (Competition $competition) => filled($competition->source_url)))->toBeTrue();
});

test('a new season copies last season competitions with the new year in the addresses', function () {
    $this->mock(ScheduleImporter::class)->shouldReceive('sync')->andReturn([]);
    $old = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => false]);
    $new = Season::create(['name' => '2027/2028', 'start_date' => '2027-09-01', 'end_date' => '2028-06-30', 'is_current' => true]);
    $team = Team::factory()->create();
    $league = Competition::create(['name' => 'Liga Kadetów', 'level' => 'wojewódzka', 'season_id' => $old->id, 'source_url' => 'https://wielkopolskasiatkowka.com.pl/liga-kadetow-26-27/', 'synced_at' => now(), 'sync_message' => 'ok']);
    $league->teams()->attach($team->id, ['external_name' => 'LOMS Joker Piła', 'display_name' => 'LOMS Joker Piła', 'display_suffix' => '(kadeci)']);

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(CompetitionManager::class)->assertSee('Skopiuj z 2026/2027')->call('copyFromPreviousSeason')->assertDontSee('Skopiuj z 2026/2027');

    $copy = Competition::with('teams')->where('season_id', $new->id)->sole();
    expect($copy->source_url)->toBe('https://wielkopolskasiatkowka.com.pl/liga-kadetow-27-28/')
        ->and($copy->synced_at)->toBeNull()
        ->and($copy->teams->first()->pivot->display_suffix)->toBe('(kadeci)')
        ->and(Competition::copyToSeason($old, $new))->toBe(0);
});

test('competition level is typed with suggestions instead of a separate list', function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(CompetitionManager::class)
        ->call('openCreateModal')
        ->set('name', 'Liga Młodzików')
        ->set('level', 'wojewódzka')
        ->call('save')
        ->assertHasNoErrors();

    expect(Competition::firstWhere('name', 'Liga Młodzików')->levelName())->toBe('wojewódzka');
    $this->get('/panel/competition-levels')->assertNotFound();
});

test('the start seeder fixes a missing current season and fills competitions without addresses', function () {
    $this->travelTo('2026-10-22');
    $this->mock(ScheduleImporter::class)->shouldReceive('sync')->times(5)->andReturn([]);
    Season::create(['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_current' => false]);
    $current = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => false]);
    Competition::create(['name' => 'Liga Kadetów', 'season_id' => $current->id]);

    $this->seed(StartSeeder::class);

    expect($current->fresh()->is_current)->toBeTrue()
        ->and(Competition::where('season_id', $current->id)->count())->toBe(5)
        ->and(Competition::firstWhere('name', 'Liga Kadetów')->source_url)->toContain('liga-kadetow')
        ->and(Competition::firstWhere('name', 'Liga Kadetów')->teams)->toHaveCount(2);

    $this->artisan('joker:sync-schedules')->doesntExpectOutputToContain('nie mają adresu');
});

test('the start seeder fills the club and school contact without overwriting panel changes', function () {
    app(SiteSettings::class)->set(['contact_address' => 'Piła', 'contact_phone' => '+48 600 000 000']);

    $this->seed(StartSeeder::class);

    $site = app(SiteSettings::class);
    expect($site['contact_address'])->toBe('ul. Wincentego Pola 11, 64-920 Piła')
        ->and($site['contact_phone'])->toBe('+48 600 000 000')
        ->and($site['contact_school_phone'])->toBe('+48 67 349 00 93');

    $this->get(route('contact.index'))->assertOk()
        ->assertSee('ul. Wincentego Pola 11, 64-920 Piła')
        ->assertSee('Liceum Ogólnokształcące Mistrzostwa Sportowego w Pile')
        ->assertSee('google.com/maps', false)
        ->assertSee('"postalCode":"64-920"', false);
});
