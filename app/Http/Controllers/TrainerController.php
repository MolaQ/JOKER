<?php

namespace App\Http\Controllers;

use App\Models\User;

class TrainerController extends Controller
{
    public function show(User $trainer)
    {
        abort_unless($trainer->isStaffMember(), 404);

        $trainer->load(['headCoachTeams', 'assistantTeams']);
        $trainer->registerView();

        $roles = $trainer->headCoachTeams->toBase()
            ->map(fn ($team) => ['team' => $team, 'role' => 'Trener główny'])
            ->merge($trainer->assistantTeams
                ->reject(fn ($team) => $team->trainer_id === $trainer->id)
                ->map(fn ($team) => ['team' => $team, 'role' => $team->pivot->role ?: 'Asystent trenera']))
            ->sortBy(fn ($entry) => $entry['team']->display_order)
            ->values();

        return view('trainers.show', compact('trainer', 'roles'));
    }
}
