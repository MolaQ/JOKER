<?php

namespace App\Livewire;

use App\Models\PageView;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Subtelny licznik w stopce: kto jest teraz na stronie, ilu gości dziś i wszystkie odsłony.
 * Odświeża się co kilkanaście sekund, gdy stopka jest widoczna.
 */
class VisitCounter extends Component
{
    public const ONLINE_MINUTES = 5;

    /**
     * @return array{online: int, today: int, total: int}
     */
    public static function counts(): array
    {
        return Cache::remember('visit-counter', 10, fn () => [
            'online' => PageView::where('created_at', '>=', now()->subMinutes(self::ONLINE_MINUTES))->distinct()->count('visitor_hash'),
            'today' => PageView::where('created_at', '>=', today())->distinct()->count('visitor_hash'),
            'total' => PageView::where('kind', PageView::KIND_PAGE)->count(),
        ]);
    }

    public function render(): View
    {
        $counts = self::counts();

        // Oglądający sam jest na stronie, choć jego wejście zapisuje się dopiero po wysłaniu odpowiedzi.
        return view('livewire.visit-counter', [
            'online' => max(1, $counts['online']),
            'today' => max(1, $counts['today']),
            'total' => $counts['total'],
        ]);
    }
}
