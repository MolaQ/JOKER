<?php

namespace App\Http\Controllers;

use App\Support\SiteSettings;
use Illuminate\Http\JsonResponse;

/**
 * Manifest panelu klubu: po „Dodaj do ekranu głównego” panel otwiera się jak aplikacja
 * (pełny ekran, bez paska przeglądarki), z nazwą i logo klubu z ustawień.
 */
class PanelManifestController extends Controller
{
    public function __invoke(SiteSettings $site): JsonResponse
    {
        $logo = $site->image('logo', asset('img/logo-512.png'));

        return response()->json([
            'name' => 'Panel · '.$site['club_name'],
            'short_name' => 'Panel',
            'lang' => 'pl',
            'id' => route('admin.dashboard', absolute: false),
            'start_url' => route('admin.dashboard', absolute: false),
            'scope' => route('admin.dashboard', absolute: false),
            'display' => 'standalone',
            'background_color' => $site->color('color_dark'),
            'theme_color' => $site->color('color_dark'),
            'icons' => [
                ['src' => $logo, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => asset('img/logo-180.png'), 'sizes' => '180x180', 'type' => 'image/png'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
