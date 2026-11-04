<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\MiniGroup;
use App\Models\User;
use App\Support\SearchIndex;
use App\Support\Sitemap;
use App\Support\StaffDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Strona „Sztab” w panelu: kolejność osób, funkcja na kafelku, ukrycie ze strony i grupy minisiatkówki trenera.
 */
class StaffOrder extends Component
{
    use ShowsToasts;

    /** @var array<int, string> */
    public array $functions = [];

    /** @var array<int, list<int|string>> */
    public array $miniGroupIds = [];

    public function mount(): void
    {
        $this->authorizeAccess();

        foreach (StaffDirectory::entries(true) as $entry) {
            $this->functions[$entry['user']->id] = (string) $entry['user']->staff_function;
            $this->miniGroupIds[$entry['user']->id] = $entry['user']->miniGroups->pluck('id')->map(fn ($id) => (string) $id)->all();
        }
    }

    protected function authorizeAccess(): void
    {
        abort_unless(Auth::user()?->can('teams.manage'), 403);
    }

    /**
     * Zmiana sztabu odświeża mapę strony i wyszukiwarkę.
     */
    protected function changed(): void
    {
        Sitemap::forget();
        SearchIndex::forget();
    }

    /**
     * Przesuwa osobę o jedno miejsce; całą listę numerujemy od nowa, by kolejność była jednoznaczna.
     */
    public function move(int $userId, int $direction): void
    {
        $this->authorizeAccess();

        $ids = StaffDirectory::entries(true)->pluck('user.id')->values();
        $index = $ids->search($userId);
        $target = $index === false ? false : $index + ($direction < 0 ? -1 : 1);

        if ($index === false || $target < 0 || $target >= $ids->count()) {
            return;
        }

        $order = $ids->all();
        [$order[$index], $order[$target]] = [$order[$target], $order[$index]];

        foreach ($order as $position => $id) {
            User::whereKey($id)->update(['staff_order' => $position + 1]);
        }

        $this->changed();
    }

    public function updatedFunctions(string $value, string $userId): void
    {
        $this->authorizeAccess();
        $value = mb_substr(trim($value), 0, 120);

        User::whereKey((int) $userId)->update(['staff_function' => $value !== '' ? $value : null]);
        $this->changed();
        $this->toast(__('Zapisano funkcję.'));
    }

    public function updatedMiniGroupIds(): void
    {
        $this->authorizeAccess();
        $valid = MiniGroup::pluck('id');

        foreach ($this->miniGroupIds as $userId => $groupIds) {
            $user = User::find((int) $userId);
            $user?->miniGroups()->sync(collect($groupIds)->map(fn ($id) => (int) $id)->intersect($valid)->values()->all());
        }

        $this->changed();
        $this->toast(__('Zapisano grupy minisiatkówki.'));
    }

    public function toggleHidden(int $userId): void
    {
        $this->authorizeAccess();
        $user = User::findOrFail($userId);
        $user->update(['staff_hidden' => ! $user->staff_hidden]);
        $this->changed();
    }

    public function render(): View
    {
        $this->authorizeAccess();

        return view('livewire.admin.staff-order', [
            'entries' => StaffDirectory::entries(true),
            'miniGroups' => MiniGroup::ordered()->get(),
            'groupLabels' => StaffDirectory::GROUPS,
        ]);
    }
}
