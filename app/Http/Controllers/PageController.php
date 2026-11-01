<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(Request $request, Page $page): View
    {
        $user = $request->user();
        abort_unless($page->isVisibleTo($user), 404);

        $page->load(['parent', 'children' => fn ($children) => $children->visibleTo($user)]);

        return view('pages.show', [
            'page' => $page,
            'menu' => Page::menuFor($user),
            'isPreview' => ! $page->is_published || ($page->parent && ! $page->parent->is_published),
        ]);
    }
}
