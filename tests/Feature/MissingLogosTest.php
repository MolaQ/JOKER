<?php

use App\Livewire\Admin\MissingLogoReview;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Support\Approvals;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    $this->team = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior']);
    $this->season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
});

test('najbliższy mecz z rywalem bez logo czeka w „Do zatwierdzenia”, a po wgraniu logo znika', function () {
    $noLogo = RivalTeam::factory()->create(['full_name' => 'MKS Bez Logo', 'logo_path' => null]);
    $withLogo = RivalTeam::factory()->create(['full_name' => 'KS Z Logo', 'logo_path' => 'rival-teams/x.png']);
    $later = RivalTeam::factory()->create(['full_name' => 'Daleki Rywal', 'logo_path' => null]);

    Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent_team_id' => $noLogo->id, 'game_date' => now()->addDays(5), 'status' => 'scheduled']);
    Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent_team_id' => $withLogo->id, 'game_date' => now()->addDays(3), 'status' => 'scheduled']);
    Game::factory()->create(['team_id' => $this->team->id, 'season_id' => $this->season->id, 'opponent_team_id' => $later->id, 'game_date' => now()->addDays(20), 'status' => 'scheduled']);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    expect(Approvals::sectionsFor($admin)['logotypy']['count'])->toBe(1);
    $this->get(route('admin.missing-logos.index'))->assertOk()->assertSee('Mecze bez logo rywala');

    Livewire::test(MissingLogoReview::class)
        ->assertSee('MKS Bez Logo')
        ->assertDontSee('KS Z Logo')
        ->assertDontSee('Daleki Rywal')
        ->call('save', $noLogo->id)
        ->assertHasErrors("logos.$noLogo->id")
        ->set("logos.$noLogo->id", UploadedFile::fake()->image('logo.png'))
        ->call('save', $noLogo->id)
        ->assertHasNoErrors()
        ->assertDontSee('MKS Bez Logo');

    expect($noLogo->fresh()->logo_path)->toStartWith('rival-teams/');
});

test('mecze bez logo widzi tylko osoba od rywali', function () {
    $this->actingAs(User::factory()->fan()->create());
    Livewire::test(MissingLogoReview::class)->assertForbidden();
});
