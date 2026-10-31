<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\TrashItem;
use App\Support\Trash;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

/**
 * Panel „Kosz”: usunięte rekordy z datą ostatecznego usunięcia, przywracanie i usuwanie na stałe.
 * Każdy widzi tylko rodzaje, którymi zarządza (np. konta tylko z uprawnieniem do użytkowników).
 */
class TrashBin extends Component
{
    use ShowsToasts, WithPagination;

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $search = '';

    public function updated(string $property): void
    {
        $this->resetPage();
    }

    /**
     * @return list<string>
     */
    private function allowedTypes(): array
    {
        return collect(Trash::TYPES)->filter(fn (array $type) => Auth::user()->can($type[2]))->keys()->all();
    }

    private function find(int $id): TrashItem
    {
        $item = TrashItem::findOrFail($id);
        abort_unless(in_array($item->type, $this->allowedTypes(), true), 403);

        return $item;
    }

    public function restore(int $id): void
    {
        $item = $this->find($id);

        try {
            Trash::restore($item);
        } catch (RuntimeException $exception) {
            $this->toast($exception->getMessage(), 'error');

            return;
        }

        $this->toast(Trash::label($item->type).' „'.$item->label.'” wróciło na swoje miejsce.');
    }

    public function purge(int $id): void
    {
        $item = $this->find($id);
        $item->purgeFiles();
        $item->delete();
        AuditLog::record('trash.purged', 'Usunięto z kosza na stałe: '.mb_strtolower(Trash::label($item->type)).' „'.$item->label.'”');

        $this->toast('Usunięto na stałe.');
    }

    public function render(): View
    {
        $allowed = $this->allowedTypes();
        $search = trim($this->search);

        return view('livewire.admin.trash-bin', [
            'items' => TrashItem::query()
                ->whereIn('type', $allowed)
                ->when(in_array($this->type, $allowed, true), fn (Builder $query) => $query->where('type', $this->type))
                ->when($search !== '', fn (Builder $query) => $query->where('label', 'like', '%'.$search.'%'))
                ->with('deleter:id,name')
                ->latest('id')
                ->paginate(25),
            'typeOptions' => collect($allowed)->mapWithKeys(fn (string $type) => [$type => Trash::label($type)])->all(),
        ]);
    }
}
