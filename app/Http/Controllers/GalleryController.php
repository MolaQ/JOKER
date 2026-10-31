<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Game;
use App\Models\Team;
use App\Support\Seo;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->filled('team') ? Team::firstWhere('slug', $request->query('team')) : null;

        $galleries = Gallery::published()
            ->whereHas('photos')
            ->with(['photos' => fn ($photos) => $photos->limit(1), 'match', 'team'])
            ->withCount('photos')
            ->when($team, fn ($query) => $query->where(fn ($inTeam) => $inTeam
                ->where('team_id', $team->id)
                ->orWhereHasMorph('match', [Game::class], fn ($game) => $game->where('team_id', $team->id))))
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        return view('galleries.index', [
            'galleries' => $galleries,
            'teams' => Team::orderBy('display_order')->get(['id', 'name', 'slug']),
            'team' => $team,
        ]);
    }

    public function show(Gallery $gallery)
    {
        abort_unless($gallery->is_published, 404);

        $gallery->load(['photos.tags.taggable', 'match', 'team']);

        return view('galleries.show', [
            'gallery' => $gallery,
            'structuredData' => Seo::gallery($gallery),
        ]);
    }
}
