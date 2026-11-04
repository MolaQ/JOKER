<?php

namespace App\Http\Controllers;

use App\Models\MiniGroup;
use App\Models\Team;
use App\Models\User;
use App\Support\Mentions;
use App\Support\SiteSettings;
use App\Support\StaffDirectory;

class TrainerController extends Controller
{
    /**
     * Zakładka „Sztab”: wszyscy trenerzy, trenerzy minisiatkówki i reszta sztabu, z filtrami.
     */
    public function index()
    {
        $entries = StaffDirectory::entries();
        $teamKeys = $entries->flatMap(fn (array $entry) => $entry['teams']->pluck('key'))->unique();

        $teams = Team::orderBy('display_order')->get()
            ->map(fn (Team $team) => ['key' => 'team-'.$team->id, 'name' => $team->name])
            ->merge(app(SiteSettings::class)['module_mini']
                ? MiniGroup::where('is_active', true)->ordered()->get()->map(fn (MiniGroup $group) => ['key' => 'mini-'.$group->id, 'name' => __('Minisiatkówka').' · '.$group->name])
                : [])
            ->filter(fn (array $team) => $teamKeys->contains($team['key']))
            ->values();

        $groups = collect(StaffDirectory::GROUPS)
            ->filter(fn (string $label, string $key) => $entries->contains(fn (array $entry) => in_array($key, $entry['groups'], true)));

        return view('trainers.index', ['entries' => $entries, 'teams' => $teams, 'groups' => $groups]);
    }

    public function show(User $trainer)
    {
        abort_unless($trainer->isStaffMember(), 404);

        $trainer->load(['headCoachTeams', 'assistantTeams', 'miniGroups']);
        $trainer->registerView();

        $entry = StaffDirectory::entry($trainer, (bool) app(SiteSettings::class)['module_mini']);

        return view('trainers.show', ['trainer' => $trainer, 'entry' => $entry, 'roles' => $entry['teams'], 'mentions' => Mentions::articlesAbout($trainer->name)]);
    }
}
