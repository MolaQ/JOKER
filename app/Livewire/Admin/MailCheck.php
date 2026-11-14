<?php

namespace App\Livewire\Admin;

use App\Support\MailDiagnosis;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Throwable;

/**
 * Sprawdzenie poczty z panelu: testowy e-mail do zalogowanej osoby i ostatnie błędy wysyłki z logu.
 * Bez zaglądania na serwer widać, czy e-maile o aktywacji kont i kopie z formularza w ogóle wychodzą.
 */
class MailCheck extends Component
{
    public ?string $result = null;

    public bool $ok = false;

    public function mount(): void
    {
        abort_unless(Gate::allows('system'), 403);
    }

    public function sendTest(): void
    {
        abort_unless(Gate::allows('system'), 403);
        $user = Auth::user();

        if (RateLimiter::tooManyAttempts('mail-test:'.$user->id, 3)) {
            $this->ok = false;
            $this->result = 'Za dużo prób, spróbuj za minutę.';

            return;
        }
        RateLimiter::hit('mail-test:'.$user->id, 60);

        try {
            Mail::raw('To jest testowy e-mail z panelu '.config('app.name').'. Skoro dotarł, poczta na serwerze działa.', function ($message) use ($user) {
                $message->to($user->email, $user->name)->subject('Test poczty: '.config('app.name'));
            });

            $this->ok = true;
            $this->result = config('mail.default') === 'log'
                ? 'Poczta jest ustawiona na zapis do logu (MAIL_MAILER=log), więc nic nie wyszło na zewnątrz.'
                : 'Wysłano na '.$user->email.'. Sprawdź skrzynkę, także folder spam.';
        } catch (Throwable $exception) {
            $this->ok = false;
            $this->result = 'Nie udało się wysłać: '.MailDiagnosis::summary($exception->getMessage());
        }
    }

    /**
     * Ostatnie błędy poczty z bieżącego pliku logu (najnowsze na górze).
     *
     * @return list<array{time: string, message: string}>
     */
    public static function recentErrors(int $limit = 5): array
    {
        $file = collect(File::glob(storage_path('logs/laravel*.log')))->sortByDesc(fn ($path) => filemtime($path))->first();

        if (! $file || ! is_readable($file)) {
            return [];
        }

        $handle = fopen($file, 'r');
        $size = filesize($file);
        fseek($handle, max(0, $size - 300_000));
        $tail = (string) stream_get_contents($handle);
        fclose($handle);

        preg_match_all('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(?:ERROR|WARNING|CRITICAL): (.*)$/m', $tail, $matches, PREG_SET_ORDER);

        return collect($matches)
            ->filter(fn (array $match) => Str::contains($match[2], ['e-mail', 'Mailer', 'mail', 'SMTP', 'Transport'], ignoreCase: true))
            // Właściwy błąd serwera poczty jest w kontekście wpisu („error” w JSON), nie w samym komunikacie.
            ->map(function (array $match) {
                $context = json_decode((string) Str::after($match[2], ' {'), true);
                $context = is_array($context) ? $context : json_decode('{'.Str::after($match[2], ' {'), true);
                $error = is_array($context) ? (string) ($context['error'] ?? '') : '';

                return ['time' => $match[1], 'message' => Str::limit(preg_replace('/\s*\{.*$/', '', $match[2]), 120).($error !== '' ? ': '.MailDiagnosis::summary($error) : '')];
            })
            ->reverse()
            ->take($limit)
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.mail-check', [
            'mailer' => (string) config('mail.default'),
            'host' => (string) config('mail.mailers.'.config('mail.default').'.host'),
            'from' => (string) config('mail.from.address'),
            'errors' => self::recentErrors(),
        ]);
    }
}
