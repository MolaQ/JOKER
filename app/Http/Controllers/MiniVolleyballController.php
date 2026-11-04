<?php

namespace App\Http\Controllers;

use App\AccountStatus;
use App\Models\MiniGroup;
use App\Models\MiniTrip;
use App\Models\MiniTripTeam;
use App\Models\TrainingSession;
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
            'results' => MiniTripTeam::with(['trip', 'group'])->whereHas('trip', fn ($query) => $query->published())->whereNotNull('place')->get()
                ->sortByDesc(fn (MiniTripTeam $team) => [$team->trip->starts_on, -$team->place])->take(8)->values(),
            'showParentInfo' => MiniTrip::canSeeParentInfo(Auth::user()),
        ]);
    }

    public function show(MiniGroup $miniGroup)
    {
        abort_unless(app(SiteSettings::class)['module_mini'] && $miniGroup->is_active, 404);

        $trips = MiniTrip::published()->whereHas('teams', fn ($query) => $query->where('mini_group_id', $miniGroup->id))
            ->with(['teams' => fn ($query) => $query->where('mini_group_id', $miniGroup->id)->with('players'), 'tournament', 'articles'])
            ->orderByDesc('starts_on')
            ->get();

        return view('mini.show', [
            'group' => $miniGroup,
            'players' => $miniGroup->players()->where('is_active', true)->get(),
            'upcoming' => $trips->reject(fn (MiniTrip $trip) => $trip->isPast())->sortBy('starts_on')->values(),
            'past' => $trips->filter(fn (MiniTrip $trip) => $trip->isPast())->values(),
            'medals' => $miniGroup->medals(),
            // Treningi z planu: tej grupy albo wspólne z innymi grupami.
            'trainings' => TrainingSession::whereNull('team_id')->whereNotNull('mini_group_ids')->ordered()->get()
                ->filter(fn (TrainingSession $session) => $session->miniGroups()->contains('id', $miniGroup->id))->values(),
            'groups' => MiniGroup::where('is_active', true)->ordered()->get(),
            'trainers' => $miniGroup->trainers()->withAccountStatus(AccountStatus::Active)->where('staff_hidden', false)->orderByRaw('staff_order is null')->orderBy('staff_order')->orderBy('name')->get(),
            'showParentInfo' => MiniTrip::canSeeParentInfo(Auth::user()),
        ]);
    }
}
