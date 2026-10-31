<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Przełącznik języka (flagi w nagłówku): zapamiętuje wybór w sesji i wraca na tę samą stronę.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put(SetLocale::SESSION_KEY, $locale);

        return redirect()->back(fallback: route('home'));
    }
}
