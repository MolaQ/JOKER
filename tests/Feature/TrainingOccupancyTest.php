<?php

use App\Models\Team;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AgeColors;
use App\Support\TrainingOccupancy;

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
