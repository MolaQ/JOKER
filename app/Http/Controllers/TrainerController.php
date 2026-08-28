<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;

class TrainerController extends Controller
{
    public function show(User $trainer)
    {
        abort_unless($trainer->role === UserRole::Trainer, 404);

        $trainer->load(['headCoachTeams', 'assistantTeams']);

        $teams = $trainer->allCoachedTeams();

        return view('trainers.show', compact('trainer', 'teams'));
    }
}
