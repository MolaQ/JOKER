<?php

namespace App\Http\Controllers;

use App\Models\Alumnus;
use App\Models\ShopProduct;
use App\Models\SupportPackage;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Support\SiteSettings;
use Illuminate\Http\Request;

/**
 * Strony dla przyszłych zawodników i kibiców: wychowankowie, plan treningów i wspieranie klubu.
 */
class ClubInfoController extends Controller
{
    public function alumni()
    {
        abort_unless(app(SiteSettings::class)['module_alumni'], 404);

        return view('club.alumni', ['alumni' => Alumnus::published()->with('player')->get()]);
    }

    public function trainings(Request $request)
    {
        $sessions = TrainingSession::with('team')->ordered()->get();
        // „?team=minisiatkowka” pokazuje treningi minisiatkówki (nie są przypisane do drużyny).
        $mini = $request->query('team') === 'minisiatkowka' && $sessions->contains(fn (TrainingSession $session) => $session->isMini());
        $team = $request->filled('team') && ! $mini ? Team::firstWhere('slug', $request->query('team')) : null;

        return view('club.trainings', [
            'days' => $sessions
                ->when($team, fn ($all) => $all->where('team_id', $team->id))
                ->when($mini, fn ($all) => $all->filter(fn (TrainingSession $session) => $session->isMini()))
                ->groupBy('weekday')->sortKeys(),
            'teams' => Team::whereIn('id', $sessions->pluck('team_id')->filter()->unique())->orderBy('display_order')->get(),
            'hasMini' => $sessions->contains(fn (TrainingSession $session) => $session->isMini()),
            'mini' => $mini,
            'team' => $team,
            'allSessions' => $sessions,
        ]);
    }

    public function support()
    {
        return view('club.support', [
            'packages' => SupportPackage::orderBy('display_order')->orderBy('id')->get(),
            'products' => ShopProduct::orderByDesc('is_available')->orderBy('display_order')->orderBy('id')->get(),
        ]);
    }
}
