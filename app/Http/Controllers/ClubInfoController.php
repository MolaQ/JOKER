<?php

namespace App\Http\Controllers;

use App\Models\Alumnus;
use App\Models\ShopProduct;
use App\Models\SupportPackage;
use App\Models\Team;
use App\Models\TrainingSession;
use Illuminate\Http\Request;

/**
 * Strony dla przyszłych zawodników i kibiców: wychowankowie, plan treningów i wspieranie klubu.
 */
class ClubInfoController extends Controller
{
    public function alumni()
    {
        return view('club.alumni', ['alumni' => Alumnus::published()->with('player')->get()]);
    }

    public function trainings(Request $request)
    {
        $sessions = TrainingSession::with('team')->ordered()->get();
        $team = $request->filled('druzyna') ? Team::firstWhere('slug', $request->query('druzyna')) : null;

        return view('club.trainings', [
            'days' => $sessions->when($team, fn ($all) => $all->where('team_id', $team->id))->groupBy('weekday')->sortKeys(),
            'teams' => Team::whereIn('id', $sessions->pluck('team_id')->unique())->orderBy('display_order')->get(),
            'team' => $team,
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
