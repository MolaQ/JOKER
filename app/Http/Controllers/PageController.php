<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        $otherPages = Page::published()
            ->whereKeyNot($page->id)
            ->orderBy('display_order')
            ->get(['title', 'slug']);

        return view('pages.show', compact('page', 'otherPages'));
    }
}
