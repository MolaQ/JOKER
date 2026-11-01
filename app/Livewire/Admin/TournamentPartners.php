<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Sponsor;
use App\Models\Tournament;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Zakładka „Partnerzy turnieju” w panelu turnieju: logotypy na dole stron turnieju i na ekranie TV.
 * Partner może być sponsorem klubu albo tylko tego turnieju (wtedy nie trafia do bloku „Wspierają nas”).
 */
class TournamentPartners extends Component
{
    use ShowsToasts, WithFileUploads;

    public int $tournamentId;

    public string $existingId = '';

    public string $name = '';

    public string $website_url = '';

    public string $tile_color = '';

    public $logo = null;

    public function boot(): void
    {
        abort_unless(Auth::user()?->can('tournaments.manage'), 403);
    }

    public function mount(int $tournamentId): void
    {
        $this->tournamentId = $tournamentId;
    }

    private function tournament(): Tournament
    {
        return Tournament::findOrFail($this->tournamentId);
    }

    public function updatedExistingId(): void
    {
        $sponsor = Sponsor::find((int) $this->existingId);
        $this->existingId = '';

        if ($sponsor) {
            $this->attach($sponsor);
        }
    }

    public function addNew(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'website_url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#i'],
            'tile_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'logo' => 'nullable|image|max:4096',
        ], [
            'name.required' => 'Wpisz nazwę partnera.',
            'website_url.regex' => 'Link zaczyna się od https:// albo / (podstrona naszej strony).',
            'tile_color.regex' => 'Kolor podaj jako #RRGGBB.',
        ]);

        $sponsor = Sponsor::create([
            'name' => $this->name,
            'website_url' => $this->website_url ?: null,
            'tile_color' => $this->tile_color ?: null,
            'logo_path' => $this->logo?->store('sponsors', 'public'),
            'is_active' => true,
            'tier' => 'bronze',
        ]);

        $this->reset(['name', 'website_url', 'tile_color', 'logo']);
        $this->attach($sponsor);
    }

    private function attach(Sponsor $sponsor): void
    {
        $tournament = $this->tournament();

        if ($tournament->sponsors()->whereKey($sponsor->id)->exists()) {
            $this->toast($sponsor->name.' już jest partnerem turnieju.', 'warning');

            return;
        }

        $tournament->sponsors()->attach($sponsor->id, [
            'display_order' => (int) $tournament->sponsors()->max('tournament_sponsor.display_order') + 1,
            'is_main' => false,
        ]);
        $this->toast('Dodano partnera: '.$sponsor->name.'.');
    }

    public function toggleMain(int $sponsorId): void
    {
        $partner = $this->tournament()->sponsors()->whereKey($sponsorId)->firstOrFail();
        $this->tournament()->sponsors()->updateExistingPivot($sponsorId, ['is_main' => ! $partner->pivot->is_main]);
    }

    /**
     * Podpis nad logo (np. „Patronat honorowy”); pusty = „Partner główny” albo bez podpisu.
     */
    public function setRole(int $sponsorId, string $role): void
    {
        $this->tournament()->sponsors()->whereKey($sponsorId)->firstOrFail();
        $this->tournament()->sponsors()->updateExistingPivot($sponsorId, ['role' => mb_substr(trim(strip_tags($role)), 0, 60) ?: null]);
        $this->toast('Zapisano podpis.');
    }

    public function move(int $sponsorId, int $direction): void
    {
        $ids = $this->tournament()->sponsors()->pluck('sponsors.id')->all();
        $index = array_search($sponsorId, $ids, true);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || ! isset($ids[$target])) {
            return;
        }

        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];

        foreach ($ids as $position => $id) {
            $this->tournament()->sponsors()->updateExistingPivot($id, ['display_order' => $position + 1]);
        }
    }

    public function remove(int $sponsorId): void
    {
        $this->tournament()->sponsors()->detach($sponsorId);
        $this->toast('Usunięto z partnerów turnieju. Logo zostaje w: Klub → Sponsorzy.');
    }

    public function render(): View
    {
        $partners = $this->tournament()->sponsors()->get();

        return view('livewire.admin.tournament-partners', [
            'partners' => $partners,
            'available' => Sponsor::whereNotIn('id', $partners->pluck('id'))->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
