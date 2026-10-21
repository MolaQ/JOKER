<?php

namespace App\Livewire\Concerns;

use Livewire\Component;

/**
 * Komunikat w „toaście” (dymek w rogu ekranu). Kolory, tytuły, czas i miejsce ustawia się
 * w panelu: Wygląd i ustawienia → Powiadomienia.
 *
 * @mixin Component
 */
trait ShowsToasts
{
    /**
     * @param  'success'|'error'  $type
     */
    public function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}
