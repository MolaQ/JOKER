<?php

use App\Livewire\Admin\SponsorManager;
use App\Livewire\Admin\TournamentPartners;
use App\Models\Season;
use App\Models\Sponsor;
use App\Models\SponsorGroup;
use App\Models\Tournament;
use App\Models\User;
use App\Support\SiteSettings;
use Livewire\Livewire;

beforeEach(function () {
    $this->institutions = SponsorGroup::create(['name' => 'Instytucje i samorządy', 'size' => 'xl', 'display_order' => 1]);
    $this->friends = SponsorGroup::create(['name' => 'Przyjaciele Klubu', 'size' => 'md', 'display_order' => 2]);
    Sponsor::create(['name' => 'Miasto Piła', 'logo_path' => '/img/sponsorzy/pila.png', 'sponsor_group_id' => $this->institutions->id, 'website_url' => 'https://www.pila.pl/', 'is_active' => true, 'tier' => 'bronze']);
    Sponsor::create(['name' => 'KLUB PRO', 'logo_path' => '/img/sponsorzy/klub-pro.png', 'sponsor_group_id' => $this->friends->id, 'website_url' => '/page/klub-pro-2026', 'tile_color' => '#1e2a8a', 'is_active' => true, 'tier' => 'bronze']);
    Sponsor::create(['name' => 'Polska Siatkówka', 'logo_path' => '/img/sponsorzy/polska-siatkowka.png', 'sponsor_group_id' => $this->friends->id, 'is_active' => false, 'tier' => 'bronze']);
});

test('the sponsor block shows categories in order on every page, without hidden logos', function () {
    $this->get('/contact')->assertOk()
        ->assertSeeInOrder(['Wspierają nas', 'Instytucje i samorządy', 'Miasto Piła', 'Przyjaciele Klubu', 'KLUB PRO'])
        ->assertSee('background: #1e2a8a', false)
        ->assertSee('href="/page/klub-pro-2026"', false)
        ->assertDontSee('Polska Siatkówka');
    $this->get('/')->assertSee('Instytucje i samorządy');
});

test('the block can be limited to the home page or switched off', function () {
    app(SiteSettings::class)->set(['sponsors_block' => 'home']);
    $this->get('/contact')->assertDontSee('Instytucje i samorządy');
    $this->get('/')->assertSee('Instytucje i samorządy');

    app(SiteSettings::class)->set(['sponsors_block' => 'off']);
    $this->get('/')->assertDontSee('Instytucje i samorządy');
});

test('the panel orders categories and logos and keeps tournament-only partners out of the block', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(SponsorManager::class)
        ->call('moveGroup', $this->friends->id, -1)
        ->call('openCreateModal')
        ->set('name', 'Hurtownia Memoriałowa')
        ->set('website_url', 'https://hurtownia.example')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->friends->fresh()->display_order)->toBeLessThan($this->institutions->fresh()->display_order)
        ->and(Sponsor::where('name', 'Hurtownia Memoriałowa')->value('sponsor_group_id'))->toBeNull()
        ->and(SponsorGroup::forBlock()->flatMap->sponsors->pluck('name'))->not->toContain('Hurtownia Memoriałowa');

    Livewire::test(SponsorManager::class)->call('openCreateModal')->set('name', 'Zły link')->set('website_url', 'javascript:alert(1)')->call('save')->assertHasErrors('website_url');
});

test('a tournament has its own partners on its pages and on the tv bar', function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $tournament = Tournament::create(['name' => 'Memoriał', 'start_date' => '2026-11-14', 'end_date' => '2026-11-15', 'city' => 'Piła', 'is_organizer' => true, 'has_page' => true, 'about' => '<p>Turniej pamięci trenera.</p>']);
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(TournamentPartners::class, ['tournamentId' => $tournament->id])
        ->set('name', 'Piekarnia Turniejowa')
        ->call('addNew')
        ->assertHasNoErrors();
    $partner = Sponsor::where('name', 'Piekarnia Turniejowa')->first();
    $partner->update(['logo_path' => '/img/sponsorzy/piko.png']);
    Livewire::test(TournamentPartners::class, ['tournamentId' => $tournament->id])->call('toggleMain', $partner->id);

    $this->get(route('tournaments.show', $tournament))->assertOk()
        ->assertSeeInOrder(['Partnerzy turnieju', 'Partner główny', 'Piekarnia Turniejowa', 'Wspierają nas'])
        ->assertSee('O turnieju')->assertSee('Turniej pamięci trenera.');

    // Podpis nad logo zamiast „Partner główny”, np. patronat honorowy.
    Livewire::test(TournamentPartners::class, ['tournamentId' => $tournament->id])->call('setRole', $partner->id, ' Patronat honorowy ');
    $this->get(route('tournaments.show', $tournament))->assertSeeInOrder(['Partnerzy turnieju', 'Patronat honorowy', 'Piekarnia Turniejowa']);

    $this->get(route('tournaments.tv', $tournament))->assertOk()
        ->assertSeeInOrder(['tv-sponsors', 'Partnerzy turnieju', 'Piekarnia Turniejowa', 'Instytucje i samorządy'], false);
});
