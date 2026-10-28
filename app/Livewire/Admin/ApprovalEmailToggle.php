<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Przełącznik na stronie „Do zatwierdzenia”: e-mail, gdy coś czeka na decyzję zalogowanej osoby.
 */
class ApprovalEmailToggle extends Component
{
    use ShowsToasts;

    public bool $enabled = false;

    public function mount(): void
    {
        $this->enabled = (bool) Auth::user()->notify_approvals;
    }

    public function updatedEnabled(bool $value): void
    {
        Auth::user()->forceFill(['notify_approvals' => $value, 'approvals_notified_count' => 0])->save();
        $this->toast($value ? 'Napiszemy do Ciebie, gdy coś będzie czekać na zatwierdzenie.' : 'Nie będziemy wysyłać e-maili o sprawach do zatwierdzenia.');
    }

    public function render()
    {
        return <<<'BLADE'
            <label class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1.5 text-sm text-gray-700 ring-1 ring-gray-200">
                <input type="checkbox" wire:model.live="enabled" class="rounded border-gray-300 text-sky-700">
                Wyślij mi e-mail, gdy coś czeka na moją decyzję
            </label>
            BLADE;
    }
}
