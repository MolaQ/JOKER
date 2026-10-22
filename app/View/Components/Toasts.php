<?php

namespace App\View\Components;

use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Stos „toastów” (dymków z komunikatem) w rogu ekranu. Pokazuje też komunikaty z sesji
 * (success / error / status) po przeładowaniu strony. Wygląd: Wygląd i ustawienia → Powiadomienia.
 */
class Toasts extends Component
{
    public const POSITIONS = ['top-right', 'bottom-right', 'top-center', 'bottom-center'];

    /** @var array{position: string, seconds: int, success: array{bg: string, text: string, title: string}, error: array{bg: string, text: string, title: string}} */
    public array $config;

    /** @var array<int, array{message: string, type: string}> */
    public array $initial;

    public function __construct(SiteSettings $settings)
    {
        $this->config = self::appearance($settings);
        $this->initial = self::fromSession();
    }

    /**
     * Ustawienia wyglądu toastów przekazywane do skryptu.
     *
     * @return array{position: string, seconds: int, success: array{bg: string, text: string, title: string}, error: array{bg: string, text: string, title: string}}
     */
    public static function appearance(SiteSettings $settings): array
    {
        $position = (string) $settings->get('toast_position');
        $seconds = (int) $settings->get('toast_seconds');

        return [
            'position' => in_array($position, self::POSITIONS, true) ? $position : 'top-right',
            'seconds' => $seconds >= 1 ? min($seconds, 60) : 4,
            'success' => [
                'bg' => $settings->color('toast_success_bg'),
                'text' => $settings->color('toast_success_text'),
                'title' => (string) $settings->get('toast_success_title'),
            ],
            'error' => [
                'bg' => $settings->color('toast_error_bg'),
                'text' => $settings->color('toast_error_text'),
                'title' => (string) $settings->get('toast_error_title'),
            ],
        ];
    }

    /**
     * Komunikaty zapisane w sesji przed przekierowaniem.
     *
     * @return array<int, array{message: string, type: string}>
     */
    public static function fromSession(): array
    {
        return collect(['success' => 'success', 'status' => 'success', 'error' => 'error'])
            ->map(fn (string $type, string $key) => ['message' => session($key), 'type' => $type])
            ->filter(fn (array $toast) => is_string($toast['message']) && $toast['message'] !== '')
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('components.toasts');
    }
}
