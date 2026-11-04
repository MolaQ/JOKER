<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista osób do odczytu dla moderatora, koordynatora i trenera (bez edycji ról i haseł).
 * Trener i koordynator widzą zawodników i rodziców swoich drużyn, moderator (wszystkie drużyny) każde konto.
 */
class UserDirectory extends Component
{
    use WithPagination;

    #[Url(as: 'szukaj', except: '')]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(Auth::user()?->can('people.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Konta widoczne dla danej osoby: null drużyn oznacza cały klub.
     */
    public static function visibleTo(User $viewer): Builder
    {
        $teamIds = $viewer->managedTeamIds();
        $query = User::query()->whereNull('anonymized_at');

        if ($teamIds === null) {
            return $query;
        }

        return $query->where(fn (Builder $inner) => $inner
            ->whereHas('playerProfile', fn (Builder $player) => $player->whereIn('team_id', $teamIds))
            ->orWhereHas('children', fn (Builder $child) => $child->whereIn('team_id', $teamIds)));
    }

    public function render(): View
    {
        abort_unless(Auth::user()?->can('people.view'), 403);

        $viewer = Auth::user();
        $teamIds = $viewer->managedTeamIds();
        $term = trim($this->search);

        $users = self::visibleTo($viewer)
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('name', 'like', '%'.$term.'%')
                ->orWhere('email', 'like', '%'.$term.'%')
                ->orWhereHas('children', fn (Builder $child) => $child->where('first_name', 'like', '%'.$term.'%')->orWhere('last_name', 'like', '%'.$term.'%'))))
            ->with(['playerProfile.team', 'children.team'])
            ->orderBy('name')
            ->paginate(25);

        return view('livewire.admin.user-directory', [
            'users' => $users,
            'allTeams' => $teamIds === null,
            'teamIds' => $teamIds,
        ]);
    }
}
