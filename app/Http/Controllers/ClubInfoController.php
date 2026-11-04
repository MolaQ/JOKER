<?php

namespace App\Http\Controllers;

use App\Models\Alumnus;
use App\Models\ShopProduct;
use App\Models\SupportPackage;
use App\Models\Team;
use App\Models\TrainingSession;
use App\Support\SiteSettings;
use App\Support\TrainingWeek;
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
        $groups = TrainingWeek::groups($sessions);
        // „?team=kadeci,kadeci-ii” zaznacza grupy na tablicy (jedna albo kilka); „minisiatkowka” to treningi minisiatkówki.
        $slugs = array_filter(explode(',', (string) $request->query('team')));
        $selected = $groups->filter(fn (array $group) => in_array($group['slug'], $slugs, true))->keys()->all();
        $single = count($selected) === 1 ? $groups[$selected[0]] : null;

        return view('club.trainings', [
            'sessions' => $sessions,
            'selected' => $selected,
            'single' => $single,
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
