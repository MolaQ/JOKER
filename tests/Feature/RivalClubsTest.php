<?php

use App\Livewire\Admin\RivalTeamManager;
use App\Models\Game;
use App\Models\LeagueStanding;
use App\Models\RivalClub;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->oldSeason = Season::create(['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30', 'is_current' => false]);
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'youth']);
    $this->actingAs(User::factory()->admin()->create());
});

function rivalGame(Team $team, Season $season, RivalTeam $rival): Game
{
    return Game::create(['team_id' => $team->id, 'season_id' => $season->id, 'opponent' => $rival->full_name, 'opponent_team_id' => $rival->id, 'game_date' => $season->start_date->addMonth(), 'is_home' => true, 'status' => 'scheduled']);
}

test('teams use the club logo unless they have their own', function () {
    $club = RivalClub::create(['name' => 'Energetyk Poznań', 'logo_path' => 'rival-clubs/energetyk.png']);
    $first = RivalTeam::create(['full_name' => 'Enea Energetyk Poznań 1', 'rival_club_id' => $club->id]);
    $youth = RivalTeam::create(['full_name' => 'Enea Energetyk Poznań 2', 'rival_club_id' => $club->id, 'logo_path' => 'rival-teams/mlodzicy.png']);

    expect($first->logoPath())->toBe('rival-clubs/energetyk.png')
        ->and($youth->logoPath())->toBe('rival-teams/mlodzicy.png');

    $standing = LeagueStanding::create(['season_id' => $this->season->id, 'competitor_type' => 'rival_team', 'competitor_id' => $first->id, 'team_name' => $first->full_name, 'position' => 1]);
    expect($standing->competitorLogoPath())->toBe('rival-clubs/energetyk.png');
});

test('suggested club groups teams that differ only by team number', function () {
    $ids = collect(['ENEA Energetyk Poznań', 'Enea Energetyk Poznań 1', 'Enea Energetyk Poznań 2', 'ENEA Energetyk Poznań II'])
        ->map(fn (string $name) => RivalTeam::create(['full_name' => $name])->id);
    RivalTeam::create(['full_name' => 'MKS Kalisz']);

    $component = Livewire::test(RivalTeamManager::class)->assertSee('Proponowane kluby');
    $component->call('createSuggestedClub', $ids->all());

    $club = RivalClub::sole();
    expect($club->name)->toBe('ENEA Energetyk Poznań')
        ->and($club->teams()->count())->toBe(4)
        ->and(RivalTeam::firstWhere('full_name', 'MKS Kalisz')->rival_club_id)->toBeNull();
});

test('selected teams are grouped into a new club with a shared logo', function () {
    Storage::fake('public');
    $a = RivalTeam::create(['full_name' => 'UMKS MOS Wola Warszawa']);
    $b = RivalTeam::create(['full_name' => 'MOS Wola Warszawa kadeci']);

    Livewire::test(RivalTeamManager::class)
        ->set("selected.{$a->id}", true)->set("selected.{$b->id}", true)
        ->set('groupClubName', 'MOS Wola Warszawa')
        ->call('groupSelected')
        ->assertHasNoErrors()
        ->call('openClubModal', RivalClub::sole()->id)
        ->set('clubLogo', UploadedFile::fake()->image('wola.png'))
        ->call('saveClub');

    expect($a->fresh()->logoPath())->not->toBeNull()
        ->and($a->fresh()->logoPath())->toBe($b->fresh()->logoPath());
});

test('a renamed team from the import is merged and keeps its history', function () {
    $old = RivalTeam::create(['full_name' => 'Energetyk Poznań', 'logo_path' => 'rival-teams/e.png']);
    rivalGame($this->team, $this->oldSeason, $old);
    $renamed = RivalTeam::create(['full_name' => 'ENEA Energetyk Poznań', 'needs_review' => true]);
    rivalGame($this->team, $this->season, $renamed);

    Livewire::test(RivalTeamManager::class)
        ->assertSee('Do przypisania (1)')
        ->set("review.{$renamed->id}.merge", (string) $old->id)
        ->call('resolveReview', $renamed->id)
        ->assertDontSee('Do przypisania');

    expect(RivalTeam::count())->toBe(1)
        ->and($old->fresh()->aliases)->toBe(['ENEA Energetyk Poznań'])
        ->and($old->games()->count())->toBe(2)
        ->and(RivalTeam::findByName('enea energetyk poznan')?->id)->toBe($old->id);
});

test('teams from past seasons only are hidden until archived ones are shown', function () {
    $current = RivalTeam::create(['full_name' => 'Sparta Złotów']);
    rivalGame($this->team, $this->season, $current);
    $past = RivalTeam::create(['full_name' => 'Chemik Police']);
    rivalGame($this->team, $this->oldSeason, $past);

    Livewire::test(RivalTeamManager::class)
        ->assertSee('Sparta Złotów')
        ->assertDontSee('Chemik Police')
        ->assertSee('Pokaż archiwalne (1)')
        ->set('showArchived', true)
        ->assertSee('Chemik Police');
});

test('rival teams no longer have a category', function () {
    Livewire::test(RivalTeamManager::class)
        ->call('openCreateModal')
        ->assertDontSee('Kategoria')
        ->set('full_name', 'UMKS MOS Wola Warszawa')
        ->set('short_name', 'Wola Warszawa')
        ->call('save')
        ->assertHasNoErrors();

    expect(RivalTeam::sole()->short_name)->toBe('Wola Warszawa');
});

test('a team with a sponsor in its name is suggested for the existing club', function () {
    $club = RivalClub::create(['name' => 'Energetyk Poznań']);
    RivalTeam::create(['full_name' => 'Energetyk Poznań', 'rival_club_id' => $club->id]);
    $sponsored = RivalTeam::create(['full_name' => 'ENEA Energetyk Poznań 2']);
    RivalTeam::create(['full_name' => 'MKS Kalisz']);

    Livewire::test(RivalTeamManager::class)
        ->assertSee('Dodaj do klubu Energetyk Poznań')
        ->call('createSuggestedClub', RivalTeam::where('full_name', 'like', '%Energetyk%')->pluck('id')->all());

    expect($sponsored->fresh()->rival_club_id)->toBe($club->id)
        ->and(RivalClub::count())->toBe(1);
});
