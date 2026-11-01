<?php

use App\Livewire\Admin\TrainingPlanner;
use App\Models\MiniGroup;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AgeColors;
use App\Support\SiteSettings;
use App\Support\TrainingOccupancy;
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

    $this->get(route('trainings.index'))->assertOk()->assertSee('Obłożenie sektorów i siłowni')->assertSee('nakłada się');

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

    $this->get(route('trainings.index'))->assertOk()->assertSee('Minisiatkówka: Czwórki + Trójki')->assertSee('Minisiatkówka: czwórki');
    $this->get(route('trainings.index', ['team' => 'minisiatkowka']))->assertOk()->assertSee('15:00–16:00');
    $this->get(route('mini.show', $threes))->assertOk()->assertSee('razem z')->assertSee('15:00–16:00');

    // Bez zaznaczonych grup: wszystkie razem.
    $session->update(['mini_group_ids' => []]);
    expect($session->fresh()->label())->toBe('Minisiatkówka')->and($session->fresh()->miniGroups())->toHaveCount(MiniGroup::count());
});
