<?php

namespace App\Http\Controllers;

use App\Support\ClubLogos;
use App\Support\SiteSettings;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

/**
 * Strona /logotypy: logotypy klubów do pobrania (np. do transmisji). Nie trafia do wyszukiwarek
 * (noindex, robots.txt, brak w mapie strony); może wymagać logowania (Ustawienia → Moduły).
 */
class LogoController extends Controller
{
    private function guard(): void
    {
        $settings = app(SiteSettings::class);
        abort_unless($settings['logos_page'], 404);

        if ($settings['logos_login'] && ! auth()->check()) {
            abort(redirect()->guest(route('login')));
        }
    }

    public function index(): Response
    {
        $this->guard();

        return response()->view('logos.index', ['logos' => ClubLogos::ranked()])->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function download(string $key): BinaryFileResponse
    {
        $this->guard();
        $logo = ClubLogos::find($key) ?? abort(404);
        ClubLogos::countDownload($key);

        return response()->download($logo['file'], ClubLogos::fileName($logo), ['X-Robots-Tag' => 'noindex']);
    }

    public function zip(): BinaryFileResponse
    {
        $this->guard();
        $path = tempnam(sys_get_temp_dir(), 'logotypy');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $names = [];

        foreach (ClubLogos::all() as $logo) {
            $name = ClubLogos::fileName($logo);
            // Dwa kluby o tej samej nazwie dostają numer w nazwie pliku.
            $names[$name] = ($names[$name] ?? 0) + 1;
            $zip->addFile($logo['file'], $names[$name] > 1 ? Str::beforeLast($name, '.').'-'.$names[$name].'.'.$logo['extension'] : $name);
        }

        $zip->close();

        return response()->download($path, 'logotypy-'.now()->format('Y-m-d').'.zip', ['X-Robots-Tag' => 'noindex'])->deleteFileAfterSend();
    }
}
