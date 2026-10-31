<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Approvals;
use Illuminate\Http\Request;

class ApprovalsController extends Controller
{
    /**
     * Jedna strona z zakładkami dla wszystkiego, co czeka na zatwierdzenie. Bez wskazanej
     * sekcji otwiera pierwszą, w której coś czeka.
     */
    public function __invoke(Request $request, ?string $section = null)
    {
        $sections = Approvals::sectionsFor($request->user());
        abort_if($sections->isEmpty(), 403);

        if ($section === null) {
            $first = $sections->firstWhere('count', '>', 0) ?? $sections->first();

            return redirect()->route($first['route']);
        }

        abort_unless($sections->has($section), 403);

        return view('admin.approvals.index', ['sections' => $sections, 'current' => $section]);
    }
}
