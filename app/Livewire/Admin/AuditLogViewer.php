<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\ChangeLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Panel „Dziennik zmian”: kto, co, kiedy i jak zmienił, z filtrami działającymi od razu.
 */
class AuditLogViewer extends Component
{
    use WithPagination;

    #[Url(as: 'szukaj', except: '')]
    public string $search = '';

    #[Url(as: 'kto', except: '')]
    public string $actor = '';

    #[Url(as: 'co', except: '')]
    public string $type = '';

    #[Url(as: 'zmiana', except: '')]
    public string $action = '';

    #[Url(as: 'od', except: '')]
    public string $from = '';

    #[Url(as: 'do', except: '')]
    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'actor', 'type', 'action', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'actor', 'type', 'action', 'from', 'to');
        $this->resetPage();
    }

    /**
     * Rodzaje wpisów do filtra „Czego dotyczy”: grupy kont i uprawnień, potem rodzaje treści.
     *
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return collect(AuditLog::GROUPS)->mapWithKeys(fn (array $group, string $key) => ['grupa:'.$key => $group[0]])->all()
            + collect(ChangeLog::MODELS)->sort()->all();
    }

    private function query(): Builder
    {
        $search = trim($this->search);

        return AuditLog::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('description', 'like', '%'.$search.'%')
                ->orWhere('details', 'like', '%'.$search.'%')))
            ->when($this->actor === 'system', fn (Builder $query) => $query->whereNull('actor_id'))
            ->when(ctype_digit($this->actor), fn (Builder $query) => $query->where('actor_id', (int) $this->actor))
            ->when(str_starts_with($this->type, 'grupa:') ? (AuditLog::GROUPS[substr($this->type, 6)] ?? null) : null, fn (Builder $query, array $group) => $query->where(function (Builder $query) use ($group) {
                foreach ($group[1] as $prefix) {
                    $query->orWhere('action', 'like', $prefix.'.%');
                }
            }))
            ->when(isset(ChangeLog::MODELS[$this->type]), fn (Builder $query) => $query->where('subject_type', (new $this->type)->getMorphClass()))
            ->when(isset(ChangeLog::ACTIONS[$this->action]), fn (Builder $query) => $query->where('action', $this->action))
            ->when($this->date($this->from), fn (Builder $query, Carbon $from) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($this->date($this->to), fn (Builder $query, Carbon $to) => $query->where('created_at', '<=', $to->endOfDay()));
    }

    private function date(string $value): ?Carbon
    {
        try {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }

    public function render()
    {
        $actors = User::whereIn('id', AuditLog::query()->whereNotNull('actor_id')->distinct()->select('actor_id'))->orderBy('name')->get(['id', 'name']);

        return view('livewire.admin.audit-log-viewer', [
            'entries' => $this->query()->with('actor:id,name')->latest('id')->paginate(30),
            'actorOptions' => collect([['value' => 'system', 'label' => 'System (zadania automatyczne)']])
                ->concat($actors->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name]))
                ->all(),
            'typeOptions' => self::typeOptions(),
            'actionOptions' => ChangeLog::ACTIONS,
            'filtered' => $this->search.$this->actor.$this->type.$this->action.$this->from.$this->to !== '',
        ]);
    }
}
