<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wysyłanie pliku przez Livewire (zdjęcia, logo) przy błędzie pokazuje tylko ogólne „Nie udało się wysłać pliku”.
 * Tu zapisujemy przyczynę do storage/logs/wysylanie-plikow.log (podgląd w menedżerze plików na hostingu).
 */
class LogFailedUpload
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            return $response;
        }

        $files = collect($request->file('files', []))->map(fn (UploadedFile $file) => [
            'nazwa' => $file->getClientOriginalName(),
            'rozmiar' => $file->getSize(),
            'blad_php' => $file->getError(),
            'opis' => $file->getErrorMessage(),
        ])->all();

        $directory = storage_path('app/private/livewire-tmp');

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/wysylanie-plikow.log'),
        ])->warning('Wysyłanie pliku nie powiodło się', [
            'kod' => $response->getStatusCode(),
            'odpowiedz' => mb_substr(strip_tags((string) $response->getContent()), 0, 300),
            'podpis_ok' => $request->hasValidRelativeSignature(),
            'adres' => $request->getSchemeAndHttpHost().$request->getPathInfo(),
            'uzytkownik' => $request->user()?->id,
            'pliki' => $files,
            'content_length' => $request->server('CONTENT_LENGTH'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'tmp_zapisywalny' => is_writable(ini_get('upload_tmp_dir') ?: sys_get_temp_dir()),
            'livewire_tmp' => $directory,
            'livewire_tmp_zapisywalny' => is_dir($directory) ? is_writable($directory) : is_writable(dirname($directory)),
        ]);

        return $response;
    }
}
