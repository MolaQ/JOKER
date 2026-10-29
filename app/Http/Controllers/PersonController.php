<?php

namespace App\Http\Controllers;

use App\AccountStatus;
use App\Models\User;
use App\Support\Mentions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Profil osoby ze społeczności (rodzic, fotograf, koordynator...). Sztab ma własną stronę w „Sztabie”.
 * Profile administracji klubu widzą wszyscy, pozostałe tylko zalogowani.
 */
class PersonController extends Controller
{
    public function show(User $user): View|RedirectResponse
    {
        abort_unless($user->accountStatus() === AccountStatus::Active, 404);

        if ($user->isStaffMember()) {
            return redirect()->route('trainers.show', $user, 301);
        }

        if (! $user->hasPublicProfile() && ! auth()->check()) {
            return redirect()->guest(route('login'));
        }

        $user->load(['children.team', 'coordinatedTeams']);

        return view('people.show', [
            'person' => $user,
            'mentions' => Mentions::articlesAbout($user->name),
        ]);
    }
}
