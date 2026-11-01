<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Sponsor;
use App\Models\SponsorGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Sponsorzy i partnerzy: kategorie bloku „Wspierają nas” nad stopką (kolejność, wielkość logotypów)
 * i logotypy w nich. Partnerzy tylko turniejów są w „Bez kategorii”.
 */
class SponsorManager extends Component
{
    use ShowsToasts, WithFileUploads;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $website_url = '';

    public ?int $sponsor_group_id = null;

    public string $tile_color = '';

    public bool $is_active = true;

    public $logo = null;

    public ?string $existingLogo = null;

    public string $newGroup = '';

    /** @var array<int, string> */
    public array $groupNames = [];

    public function boot(): void
    {
        abort_unless(Auth::user()?->can('sponsors.manage'), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'website_url' => ['nullable', 'string', 'max:255', 'regex:#^(https?://|/)#i'],
            'sponsor_group_id' => ['nullable', 'integer', Rule::exists('sponsor_groups', 'id')],
            'tile_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'is_active' => 'boolean',
            'logo' => 'nullable|image|max:4096',
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Wpisz nazwę.',
            'website_url.regex' => 'Link zaczyna się od https:// albo / (podstrona naszej strony, np. /page/klub-pro-2026).',
            'tile_color.regex' => 'Kolor podaj jako #RRGGBB.',
        ];
    }

    public function openCreateModal(?int $groupId = null): void
    {
        $this->resetForm();
        $this->sponsor_group_id = $groupId;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $sponsor = Sponsor::findOrFail($id);

        $this->resetForm();
        $this->editingId = $sponsor->id;
        $this->name = $sponsor->name;
        $this->description = (string) $sponsor->description;
        $this->website_url = (string) $sponsor->website_url;
        $this->sponsor_group_id = $sponsor->sponsor_group_id;
        $this->tile_color = (string) $sponsor->tile_color;
        $this->is_active = $sponsor->is_active;
        $this->existingLogo = $sponsor->logo_path;
        $this->showModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        unset($validated['logo']);
        $validated['website_url'] = $validated['website_url'] ?: null;
        $validated['tile_color'] = $validated['tile_color'] ?: null;
        $validated['sponsor_group_id'] = $this->sponsor_group_id ?: null;

        if ($this->logo) {
            $validated['logo_path'] = $this->logo->store('sponsors', 'public');
        }

        if ($this->editingId) {
            $sponsor = Sponsor::findOrFail($this->editingId);
            if ($sponsor->sponsor_group_id !== $validated['sponsor_group_id']) {
                $validated['display_order'] = (int) Sponsor::where('sponsor_group_id', $validated['sponsor_group_id'])->max('display_order') + 1;
            }
            $sponsor->update($validated);
            $this->toast('Zapisano: '.$sponsor->name.'.');
        } else {
            Sponsor::create($validated + [
                'tier' => 'bronze',
                'display_order' => (int) Sponsor::where('sponsor_group_id', $validated['sponsor_group_id'])->max('display_order') + 1,
            ]);
            $this->toast('Dodano: '.$validated['name'].'.');
        }

        $this->closeModal();
    }

    public function toggleActive(int $id): void
    {
        $sponsor = Sponsor::findOrFail($id);
        $sponsor->update(['is_active' => ! $sponsor->is_active]);
        $this->toast($sponsor->is_active ? $sponsor->name.' jest widoczny na stronie.' : $sponsor->name.' jest ukryty.');
    }

    public function move(int $id, int $direction): void
    {
        $sponsor = Sponsor::findOrFail($id);
        $this->reorder(Sponsor::where('sponsor_group_id', $sponsor->sponsor_group_id)->orderBy('display_order')->orderBy('name')->get(), $id, $direction);
    }

    public function delete(int $id): void
    {
        $sponsor = Sponsor::findOrFail($id);
        $sponsor->delete();
        $this->toast('Usunięto: '.$sponsor->name.'.');
    }

    public function addGroup(): void
    {
        $this->validate(['newGroup' => 'required|string|max:120'], ['newGroup.required' => 'Wpisz nazwę kategorii.']);

        SponsorGroup::create(['name' => trim($this->newGroup), 'size' => 'md', 'display_order' => (int) SponsorGroup::max('display_order') + 1]);
        $this->newGroup = '';
        $this->toast('Dodano kategorię.');
    }

    public function renameGroup(int $id): void
    {
        $name = trim((string) ($this->groupNames[$id] ?? ''));

        if ($name !== '') {
            SponsorGroup::whereKey($id)->update(['name' => mb_substr($name, 0, 120)]);
            $this->toast('Zapisano nazwę kategorii.');
        }
    }

    public function setGroupSize(int $id, string $size): void
    {
        if (array_key_exists($size, SponsorGroup::SIZES)) {
            SponsorGroup::whereKey($id)->update(['size' => $size]);
        }
    }

    public function moveGroup(int $id, int $direction): void
    {
        $this->reorder(SponsorGroup::orderBy('display_order')->orderBy('id')->get(), $id, $direction);
    }

    public function deleteGroup(int $id): void
    {
        $group = SponsorGroup::findOrFail($id);
        Sponsor::where('sponsor_group_id', $id)->update(['sponsor_group_id' => null]);
        $group->delete();
        $this->toast('Usunięto kategorię. Jej logotypy są teraz w „Bez kategorii”.');
    }

    /**
     * Zamienia miejscami z sąsiednim elementem listy i numeruje kolejność od 1.
     */
    private function reorder($items, int $id, int $direction): void
    {
        $items = $items->values()->all();
        $index = collect($items)->search(fn ($item) => $item->id === $id);
        $target = $index + ($direction < 0 ? -1 : 1);

        if ($index === false || ! isset($items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];

        foreach ($items as $position => $item) {
            if ($item->display_order !== $position + 1) {
                $item->update(['display_order' => $position + 1]);
            }
        }
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->website_url = '';
        $this->sponsor_group_id = null;
        $this->tile_color = '';
        $this->is_active = true;
        $this->logo = null;
        $this->existingLogo = null;
        $this->resetErrorBag();
    }

    public function render()
    {
        $groups = SponsorGroup::with('sponsors')->orderBy('display_order')->orderBy('id')->get();
        $this->groupNames = $groups->mapWithKeys(fn (SponsorGroup $group) => [$group->id => $this->groupNames[$group->id] ?? $group->name])->all();

        return view('livewire.admin.sponsor-manager', [
            'groups' => $groups,
            'loose' => Sponsor::whereNull('sponsor_group_id')->orderBy('display_order')->orderBy('name')->get(),
        ]);
    }
}
