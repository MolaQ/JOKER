<?php

namespace App\Http\Controllers;

use App\Support\Sitemap;
use Illuminate\Http\Response;

/**
 * Mapa strony dla wyszukiwarek (sitemap.xml) i robots.txt z adresem mapy.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        return response(Sitemap::xml(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /panel',
            'Disallow: /login',
            'Disallow: /moj-profil',
            'Disallow: /szukaj',
            'Disallow: /jezyk/',
            'Disallow: /logotypy',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
