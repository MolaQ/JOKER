<?php

use App\Livewire\Admin\GameManager;
use App\Models\Competition;
use App\Models\Document;
use App\Models\Game;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\Services\ScheduleImporter;
use App\UserRole;
use Database\Seeders\StartSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function document(bool $public, string $category = 'rodo'): Document
{
    $path = UploadedFile::fake()->create('plik.pdf', 10)->store('documents', 'public');

    return Document::create(['title' => $public ? 'Regulamin klubu' : 'Klauzula RODO', 'file_path' => $path, 'file_type' => 'pdf', 'file_size' => 1024, 'category' => $category, 'is_public' => $public, 'uploaded_by' => User::factory()->create()->id]);
}

test('login-only documents are listed and downloaded only by active accounts', function () {
    Storage::fake('public');
    $public = document(true, 'regulations');
    $private = document(false);

    $this->get('/dokumenty')->assertOk()->assertSee('Regulamin klubu')->assertDontSee('Klauzula RODO')->assertSee('dostępne po zalogowaniu');
    $this->get($public->downloadUrl())->assertOk();
    $this->get($private->downloadUrl())->assertUnauthorized();

    $this->actingAs(User::factory()->inactive()->create())->get($private->downloadUrl())->assertForbidden();

    $this->actingAs(User::factory()->parent()->create());
    $this->get('/dokumenty')->assertSee('Klauzula RODO')->assertSee('RODO i ochrona danych');
    $this->get($private->downloadUrl())->assertOk();

    expect($private->fresh()->download_count)->toBe(1);
});

test('a manual league result can go back to the federation result', function () {
    $season = Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $team = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'cadet']);
    $competition = Competition::factory()->create(['season_id' => $season->id]);
    $game = Game::factory()->create(['team_id' => $team->id, 'season_id' => $season->id, 'competition_id' => $competition->id, 'home_score' => 3, 'away_score' => 0, 'result_source' => Game::RESULT_MANUAL]);

    $this->mock(ScheduleImporter::class)->shouldReceive('sync')->once()->andReturnUsing(function () use ($game) {
        $game->forceFill(['home_score' => 3, 'away_score' => 1, 'result_source' => Game::RESULT_WZPS])->save();

        return ['created' => 0, 'updated' => 1, 'rows' => 1, 'unmatched' => [], 'errors' => [], 'tables' => false];
    });

    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(GameManager::class)
        ->assertSee('ręcznie')
        ->call('openEditModal', $game->id)
        ->assertSee('Wróć do wyniku ze związku')
        ->call('restoreOfficialResult')
        ->assertSet('resultSource', Game::RESULT_WZPS);

    expect($game->fresh()->away_score)->toBe(1);
});

test('the start seeder creates teams, trainers and accounts from env without duplicates', function () {
    config([
        'seed.admin.email' => 'admin@example.com', 'seed.admin.password' => 'haslo-admina-123',
        'seed.boss.email' => 'szef@example.com', 'seed.boss.password' => 'haslo-szefa-123',
        'seed.player.email' => 'zawodnik@example.com', 'seed.player.password' => null,
    ]);

    $this->seed(StartSeeder::class);
    $this->seed(StartSeeder::class);

    expect(Team::pluck('name')->all())->toBe(['Seniorzy', 'Juniorzy', 'Juniorzy II', 'Kadeci', 'Kadeci II', 'Młodzicy', 'Młodzicy II'])
        ->and(Team::firstWhere('slug', 'seniorzy')->trainer->name)->toBe('Tomasz Aszemberg')
        ->and(Team::firstWhere('slug', 'kadeci')->assistantTrainers->pluck('name')->all())->toBe(['Szymon Pietraszak'])
        ->and(Team::firstWhere('slug', 'mlodzicy')->trainer->email)->toBe('szef@example.com')
        ->and(Team::firstWhere('slug', 'mlodzicy-ii')->trainer_id)->toBeNull()
        ->and(User::firstWhere('name', 'Roman Pilch')->is_active)->toBeFalse();

    $admin = User::firstWhere('email', 'admin@example.com');
    $boss = User::firstWhere('email', 'szef@example.com');
    $marcel = Player::firstWhere('last_name', 'Molak');

    expect($admin->roles()->all())->toBe([UserRole::Admin, UserRole::Parent])
        ->and(Hash::check('haslo-admina-123', $admin->password))->toBeTrue()
        ->and($boss->roles()->all())->toBe([UserRole::Boss, UserRole::Trainer])
        ->and($admin->children->pluck('id')->all())->toBe([$marcel->id])
        ->and($marcel->user->email)->toBe('zawodnik@example.com')
        ->and($marcel->position->value)->toBe('opposite')
        ->and($marcel->jersey_number)->toBe(10)
        ->and($marcel->teamsForSeason(Season::current()->value('id'))->pluck('name')->sort()->values()->all())->toBe(['Juniorzy II', 'Kadeci'])
        ->and(User::where('email', 'admin@example.com')->count())->toBe(1)
        ->and(Player::where('last_name', 'Molak')->count())->toBe(1);
});
