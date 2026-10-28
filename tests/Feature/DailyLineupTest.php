<?php

use App\Livewire\LikeButton;
use App\Models\DailyLineup;
use App\Models\Player;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use App\PlayerPosition;
use App\Support\SiteSettings;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    Season::create(['name' => '2026/2027', 'start_date' => '2026-09-01', 'end_date' => '2027-06-30', 'is_current' => true]);
    $this->seniors = Team::create(['name' => 'Seniorzy', 'slug' => 'seniorzy', 'category' => 'senior', 'display_order' => 1]);
    $this->cadets = Team::create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy', 'display_order' => 4]);
});

function lineupPlayer(Team $team, PlayerPosition $position, array $attributes = []): Player
{
    return Player::factory()->create(['team_id' => $team->id, 'position' => $position, ...$attributes]);
}

test('skład dnia losuje każdą pozycję raz, bez nieaktywnych i bez zawodników spoza drużyn', function () {
    foreach (DailyLineup::SLOTS as $position) {
        lineupPlayer($this->cadets, $position);
    }
    lineupPlayer($this->cadets, PlayerPosition::OutsideHitter);
    $inactive = lineupPlayer($this->cadets, PlayerPosition::Libero, ['is_active' => false]);
    $teamless = Player::factory()->create(['team_id' => null, 'position' => PlayerPosition::Setter]);

    $lineup = DailyLineup::today();
    $ids = collect($lineup->players)->pluck('player_id');

    expect($ids)->toHaveCount(6)->not->toContain(null)
        ->and($ids->unique())->toHaveCount(6)
        ->and($ids)->not->toContain($inactive->id)->not->toContain($teamless->id)
        ->and(collect($lineup->lineup())->map(fn ($slot) => $slot['player']->position)->all())->toBe(DailyLineup::SLOTS);
});

test('skład jest jeden na dzień, a następnego dnia losowany od nowa', function () {
    foreach (DailyLineup::SLOTS as $position) {
        lineupPlayer($this->seniors, $position);
    }

    $first = DailyLineup::today();
    expect(DailyLineup::today()->id)->toBe($first->id);

    Carbon::setTestNow(now()->addDay());
    expect(DailyLineup::today()->id)->not->toBe($first->id)
        ->and(DailyLineup::count())->toBe(2);
    Carbon::setTestNow();
});

test('przy dwóch kapitanach opaskę dostaje zawodnik starszej drużyny', function () {
    $cadetCaptain = lineupPlayer($this->cadets, PlayerPosition::Setter);
    $seniorCaptain = lineupPlayer($this->seniors, PlayerPosition::Libero);
    $cadetCaptain->syncHomeRoster(true);
    $seniorCaptain->syncHomeRoster(true);
    $other = lineupPlayer($this->seniors, PlayerPosition::Opposite);

    expect(DailyLineup::captainAmong([$cadetCaptain->id, $seniorCaptain->id, $other->id]))->toBe($seniorCaptain->id)
        ->and(DailyLineup::captainAmong([$cadetCaptain->id, $other->id]))->toBe($cadetCaptain->id)
        ->and(DailyLineup::captainAmong([$other->id]))->toBeNull();
});

test('trener jest losowany ze sztabu, a serduszka zwiększają szansę', function () {
    $head = User::factory()->create();
    $assistant = User::factory()->create();
    User::factory()->create(); // konto spoza sztabu
    $this->seniors->update(['trainer_id' => $head->id]);
    $this->cadets->assistantTrainers()->attach($assistant->id, ['role' => 'Asystent trenera']);

    $coaches = collect(range(1, 40))->map(fn () => DailyLineup::draw()['coach_id'])->unique();
    expect($coaches->diff([$head->id, $assistant->id]))->toBeEmpty();

    // 99 serduszek dla asystenta: szansa trenera głównego to 1 na 101.
    User::factory()->count(99)->create()->each(fn (User $fan) => $assistant->toggleLikeFor($fan));
    $picks = collect(range(1, 30))->map(fn () => DailyLineup::draw()['coach_id']);
    expect($picks->filter(fn ($id) => $id === $assistant->id)->count())->toBeGreaterThan(20);
});

test('profil trenera można polubić, a polubienia dane przez konto zostają osobno', function () {
    $coach = User::factory()->create();
    $this->seniors->update(['trainer_id' => $coach->id]);
    $fan = User::factory()->create();

    $this->actingAs($fan);
    Livewire::test(LikeButton::class, ['type' => 'trainer', 'id' => $coach->id])->call('toggle')->assertSet('liked', true)->assertSet('likesCount', 1);

    expect($coach->receivedLikes()->count())->toBe(1)
        ->and($coach->likes()->count())->toBe(0)
        ->and($fan->likes()->count())->toBe(1);

    $this->get(route('trainers.show', $coach))->assertOk()->assertSeeLivewire(LikeButton::class);
});

test('strona główna pokazuje skład dnia z linkami do profili, a w ustawieniach można go wyłączyć', function () {
    $coach = User::factory()->create(['name' => 'Anna Trenerska']);
    $this->seniors->update(['trainer_id' => $coach->id]);
    $libero = lineupPlayer($this->seniors, PlayerPosition::Libero, ['first_name' => 'Karol', 'last_name' => 'Mróz', 'jersey_number' => 92]);

    $this->get('/')->assertOk()
        ->assertSee('Skład dnia')
        ->assertSee('Mróz')
        ->assertSee(route('players.show', $libero), false)
        ->assertSee(route('trainers.show', $coach), false)
        ->assertSee('dl-pic-libero', false)
        ->assertSee('Pobierz obrazek');

    app(SiteSettings::class)->set(['home_lineup_enabled' => false]);
    $this->get('/')->assertOk()->assertDontSee('Pobierz obrazek');
});
