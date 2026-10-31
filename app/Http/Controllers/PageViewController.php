<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Odbiera z przeglądarki czas spędzony na stronie, przewinięcie i otwierane zakładki.
 */
class PageViewController extends Controller
{
    public function update(Request $request): Response
    {
        $view = PageView::fromToken($request->input('pv'));

        if ($view) {
            $view->update([
                'duration_seconds' => max((int) $view->duration_seconds, min(3600, max(0, (int) $request->input('seconds')))),
                'max_scroll' => max((int) $view->max_scroll, min(100, max(0, (int) $request->input('scroll')))),
            ]);
        }

        return response()->noContent();
    }

    public function tab(Request $request): Response
    {
        $view = PageView::fromToken($request->input('pv'));
        $label = Str::limit(trim(strip_tags((string) $request->input('label'))), 120, '');

        if ($view && $label !== '' && $view->created_at->gt(now()->subHours(6))) {
            PageView::create([
                'page_view_id' => $view->id,
                'kind' => PageView::KIND_TAB,
                'path' => $view->path,
                'route_name' => $view->route_name,
                'title' => $view->title,
                'label' => $label,
                'visitor_hash' => $view->visitor_hash,
                'device' => $view->device,
                'locale' => $view->locale,
            ]);
        }

        return response()->noContent();
    }
}
