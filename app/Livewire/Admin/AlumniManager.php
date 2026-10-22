<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Alumnus;
use App\Models\Player;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Wychowankowie na stronie „Od Jokera do PlusLigi”.
 */
class AlumniManager extends Component
{
    use ShowsToasts, WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $position = '';

    public string $years_in_club = '';

    public string $current_club = '';

    public string $level = '';

    public string $achievements = '';

    public string $profile_url = '';

    public ?int $player_id = null;

    public bool $is_featured = false;

    public bool $is_published = true;

    public int $display_order = 0;

    public $photo = null;

    public ?string $existingPhoto = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'years_in_club' => 'nullable|string|max:40',
            'current_club' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:80',
            'achievements' => 'nullable|string|max:3000',
            'profile_url' => 'nullable|url|max:500',
            'player_id' => 'nullable|exists:players,id',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'display_order' => 'integer|min:0',
            'photo' => 'nullable|image|max:4096',
        ];
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $alumnus = Alumnus::findOrFail($id);
        $this->resetForm();
        $this->editingId = $alumnus->id;
        $this->fill($alumnus->only(['name', 'position', 'years_in_club', 'current_club', 'level', 'achievements', 'profile_url', 'player_id', 'is_featured', 'is_published', 'display_order']));
        foreach (['position', 'years_in_club', 'current_club', 'level', 'achievements', 'profile_url'] as $field) {
            $this->{$field} = (string) $this->{$field};
        }
        $this->existingPhoto = $alumnus->photo_path;
        $this->showForm = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        unset($data['photo']);
        $data = array_map(fn ($value) => $value === '' ? null : $value, $data);
        $data['name'] = trim((string) $data['name']);

        if ($this->photo) {
            $data['photo_path'] = $this->photo->store('alumni', 'public');
        }

        Alumnus::updateOrCreate(['id' => $this->editingId], $data);
        $this->toast($this->editingId ? 'Zapisano zmiany.' : 'Dodano wychowanka.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Alumnus::findOrFail($id)->delete();
        $this->toast('Usunięto wychowanka.');
    }

    public function resetForm(): void
    {
        $this->reset(['showForm', 'editingId', 'name', 'position', 'years_in_club', 'current_club', 'level', 'achievements', 'profile_url', 'player_id', 'is_featured', 'is_published', 'display_order', 'photo', 'existingPhoto']);
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.alumni-manager', [
            'alumni' => Alumnus::with('player')->orderByDesc('is_featured')->orderBy('display_order')->orderBy('name')->get(),
            'players' => Player::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }
}
