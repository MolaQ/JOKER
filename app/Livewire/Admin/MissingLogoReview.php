<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\RivalTeam;
use App\Permission;
use App\Support\MissingLogos;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * „Do zatwierdzenia → Mecze bez logo”: najbliższe mecze, których rywal nie ma logotypu.
 * Logo (i wariant na ciemne tło do miniatur) wgrywa się od razu tutaj.
 */
class MissingLogoReview extends Component
{
    use ShowsToasts, WithFileUploads;

    /** @var array<int, mixed> logo na jasne tło, klucz = id drużyny rywali */
    public array $logos = [];

    /** @var array<int, mixed> logo na ciemne tło, klucz = id drużyny rywali */
    public array $logosLight = [];

    public function boot(): void
    {
        abort_unless(Auth::user()?->hasPermission(Permission::Competitions), 403);
    }

    public function save(int $rivalId): void
    {
        $rival = RivalTeam::findOrFail($rivalId);

        $this->validate([
            "logos.$rivalId" => ['nullable', 'required_without:logosLight.'.$rivalId, 'image', 'max:2048'],
            "logosLight.$rivalId" => 'nullable|image|max:2048',
        ], [
            "logos.$rivalId.required_without" => 'Wybierz plik z logo.',
            "logos.$rivalId.image" => 'Logo musi być obrazkiem.',
            "logos.$rivalId.max" => 'Logo może mieć najwyżej 2 MB.',
            "logosLight.$rivalId.max" => 'Logo może mieć najwyżej 2 MB.',
        ]);

        $rival->update(array_filter([
            'logo_path' => isset($this->logos[$rivalId]) ? $this->logos[$rivalId]->store('rival-teams', 'public') : null,
            'logo_light_path' => isset($this->logosLight[$rivalId]) ? $this->logosLight[$rivalId]->store('rival-teams', 'public') : null,
        ]));

        unset($this->logos[$rivalId], $this->logosLight[$rivalId]);
        $this->toast('Logo drużyny '.$rival->full_name.' zostało zapisane.');
    }

    public function render(): View
    {
        return view('livewire.admin.missing-logo-review', ['items' => MissingLogos::upcoming()]);
    }
}
