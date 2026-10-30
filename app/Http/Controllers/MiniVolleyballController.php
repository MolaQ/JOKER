<?php

namespace App\Http\Controllers;

use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\MiniTripTeam;
use App\Support\SiteSettings;
use Illuminate\Support\Facades\Auth;

/**
 * Minisiatkówka na stronie: grupy z dziećmi, wyjazdy na turnieje, wyniki, historia i zasady.
 */
class MiniVolleyballController extends Controller
{
    public function index()
    {
        abort_unless(app(SiteSettings::class)['module_mini'], 404);

        $groups = MiniGroup::where('is_active', true)->ordered()->withCount(['players' => fn ($query) => $query->where('is_active', true)])->get();

        return view('mini.index', [
            'groups' => $groups,
            'upcoming' => MiniTrip::upcoming()->with('teams.group')->limit(6)->get(),
            'results' => MiniTripTeam::with(['trip', 'group'])->whereNotNull('place')->get()
                ->sortByDesc(fn (MiniTripTeam $team) => [$team->trip->starts_on, -$team->place])->take(8)->values(),
            'showParentInfo' => MiniTrip::canSeeParentInfo(Auth::user()),
        ]);
    }

    public function show(MiniGroup $miniGroup)
    {
        abort_unless(app(SiteSettings::class)['module_mini'] && $miniGroup->is_active, 404);

        $trips = MiniTrip::whereHas('teams', fn ($query) => $query->where('mini_group_id', $miniGroup->id))
            ->with(['teams' => fn ($query) => $query->where('mini_group_id', $miniGroup->id)->with('players'), 'tournament'])
            ->orderByDesc('starts_on')
            ->get();

        return view('mini.show', [
            'group' => $miniGroup,
            'players' => $miniGroup->players()->where('is_active', true)->get(),
            'upcoming' => $trips->reject(fn (MiniTrip $trip) => $trip->isPast())->sortBy('starts_on')->values(),
            'past' => $trips->filter(fn (MiniTrip $trip) => $trip->isPast())->values(),
            'medals' => $miniGroup->medals(),
            'others' => MiniGroup::where('is_active', true)->whereKeyNot($miniGroup->id)->ordered()->get(),
            'showParentInfo' => MiniTrip::canSeeParentInfo(Auth::user()),
        ]);
    }
}
