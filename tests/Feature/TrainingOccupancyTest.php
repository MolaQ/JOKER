<?php

use App\Livewire\Admin\TrainingPlanner;
use App\Models\MiniGroup;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AgeColors;
use App\Support\SiteSettings;
use App\Support\TrainingOccupancy;
use App\Support\TrainingWeek;
use Livewire\Livewire;

test('obłożenie sali układa sektory jak w hali, główne boisko na całą szerokość i oznacza nakładanie', function () {
    $cadets = Team::factory()->create(['name' => 'Kadeci', 'category' => 'junior_mlodszy']);
    $seniors = Team::factory()->create(['name' => 'Seniorzy', 'category' => 'senior']);

    TrainingSession::create(['team_id' => $cadets->id, 'weekday' => 1, 'starts_at' => '16:00', 'ends_at' => '17:30', 'venue' => 'court1']);
    TrainingSession::create(['team_id' => $seniors->id, 'weekday' => 1, 'starts_at' => '17:00', 'ends_at' => '19:00', 'venue' => 'main']);
    TrainingSession::create(['team_id' => $seniors->id, 'weekday' => 1, 'starts_at' => '19:00', 'ends_at' => '20:00', 'venue' => 'gym']);
    TrainingSession::create(['team_id' => $cadets->id, 'weekday' => 3, 'starts_at' => '16:00', 'ends_at' => '17:00', 'venue' => 'court3']);

    $days = TrainingOccupancy::days(TrainingSession::with('team')->get());
    $monday = $days[1];

    expect($days->keys()->all())->toBe([1, 3])
        ->and($monday['start'])->toBe(16 * 60)->and($monday['end'])->toBe(20 * 60)
        ->and(collect($monday['courts'])->every(fn ($block) => $block['conflict']))->toBeTrue()
        ->and($monday['gym'][0]['conflict'])->toBeFalse()
        ->and($monday['gym'][0]['width'])->toEqual(100)
        ->and(round($days[3]['courts'][0]['width'], 2))->toEqual(33.33)
        ->and($days[3]['courts'][0]['left'])->toEqual(0)
        ->and($monday['courts'][0]['color'])->toBe(AgeColors::color('junior_mlodszy'));

    $this->get(route('trainings.index'))->assertOk()->assertSee('Godziny treningów')->assertSee('nakłada się');

    $this->actingAs(User::factory()->admin()->create())->get(route('admin.trainings.index'))->assertOk()->assertSee('Obłożenie sektorów i siłowni');
});

test('minisiatkówka trenuje w planie bez drużyny, także kilka grup razem', function () {
    app(SiteSettings::class)->set(['module_mini' => '1']);
    $fours = MiniGroup::firstWhere('slug', 'czworki');
    $threes = MiniGroup::firstWhere('slug', 'trojki');
    MiniGroup::query()->update(['is_active' => true]);

    Livewire::actingAs(User::factory()->admin()->create())->test(TrainingPlanner::class)
        ->set('team_id', 'mini')->set('mini_groups', [$fours->id, $threes->id])
        ->set('weekdays', [2])->set('starts_at', '15:00')->set('ends_at', '16:00')->set('venue', 'court2')
        ->call('save')->assertHasNoErrors();

    $session = TrainingSession::sole();
    expect($session->isMini())->toBeTrue()
        ->and($session->team_id)->toBeNull()
        ->and($session->label())->toBe('Minisiatkówka: Czwórki + Trójki')
        ->and($session->colors())->toBe([AgeColors::forMini(4), AgeColors::forMini(3)])
        ->and(TrainingOccupancy::days(collect([$session]))[2]['courts'][0]['colors'])->toHaveCount(2);

    $this->get(route('trainings.index'))->assertOk()->assertSee('Minisiatkówka: Czwórki + Trójki');
    $this->get(route('trainings.index', ['team' => 'minisiatkowka']))->assertOk()->assertSee('15:00–16:00');
    $this->get(route('mini.show', $threes))->assertOk()->assertSee('razem z')->assertSee('15:00–16:00');

    // Bez zaznaczonych grup: wszystkie razem.
    $session->update(['mini_group_ids' => []]);
    expect($session->fresh()->label())->toBe('Minisiatkówka')->and($session->fresh()->miniGroups())->toHaveCount(MiniGroup::count());
});

test('tablica tygodnia ściska puste godziny, liczy obłożenie sektorów i zaznacza kilka drużyn z adresu', function () {
    $cadets = Team::factory()->create(['name' => 'Kadeci', 'slug' => 'kadeci', 'category' => 'junior_mlodszy', 'display_order' => 1]);
    $cadets2 = Team::factory()->create(['name' => 'Kadeci II', 'slug' => 'kadeci-ii', 'category' => 'junior_mlodszy', 'display_order' => 2]);
    $youth = Team::factory()->create(['name' => 'Młodzicy', 'slug' => 'mlodzicy', 'category' => 'mlodzik', 'display_order' => 3]);

    TrainingSession::create(['team_id' => $cadets2->id, 'weekday' => 2, 'starts_at' => '10:00', 'ends_at' => '12:00', 'venue' => 'court2']);
    TrainingSession::create(['team_id' => $cadets->id, 'weekday' => 2, 'starts_at' => '16:15', 'ends_at' => '18:15', 'venue' => 'court1']);
    TrainingSession::create(['team_id' => $youth->id, 'weekday' => 2, 'starts_at' => '16:30', 'ends_at' => '18:30', 'venue' => 'court1']);
    TrainingSession::create(['team_id' => $youth->id, 'weekday' => 4, 'starts_at' => '18:00', 'ends_at' => '19:00', 'venue' => 'main']);
    TrainingSession::create(['team_id' => $cadets->id, 'weekday' => 4, 'starts_at' => '17:15', 'ends_at' => '18:15', 'venue' => 'gym']);

    $sessions = TrainingSession::with('team')->get();
    $week = TrainingWeek::build($sessions);

    // 10–12 i 16–19, między nimi jedna wąska przerwa zamiast czterech pustych godzin
    expect($week['axis']['segments'])->toBe([[600, 720], [960, 1140]])
        ->and($week['axis']['total'])->toBe(120 + 180 + TrainingWeek::GAP)
        ->and(TrainingWeek::position($week['axis'], 600))->toEqual(0)
        ->and(TrainingWeek::position($week['axis'], 1140))->toEqual(100)
        // Sektor I: wtorek 16:15–18:30 (nakładające się liczone raz) + czwartek godzina całej sali
        ->and($week['hours']['court1'])->toEqual(3.25)
        ->and($week['hours']['court3'])->toEqual(1)
        ->and($week['hours']['gym'])->toEqual(1)
        // druga drużyna tej samej kategorii jest kreskowana
        ->and($week['groups']['team-'.$cadets->id]['hatch'])->toBeFalse()
        ->and($week['groups']['team-'.$cadets2->id]['hatch'])->toBeTrue()
        ->and(collect($week['days'][2]['courts'])->where('conflict', true))->toHaveCount(2)
        ->and($week['days'][4]['courts'][0]['h'])->toEqual(100);

    $this->get(route('trainings.index', ['team' => 'kadeci,mlodzicy,nie-ma']))->assertOk()
        ->assertSee('Plan treningów')
        ->assertSee('[\\u0022team-'.$cadets->id.'\\u0022,\\u0022team-'.$youth->id.'\\u0022]', false)
        ->assertSee('Godziny treningów');

    $this->get(route('trainings.index', ['team' => 'kadeci-ii']))->assertOk()->assertSee('<title>Plan treningów - Kadeci II', false);
});
