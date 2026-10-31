<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\AuditLog;
use App\Models\MiniGroup;
use App\Models\MiniPlayer;
use App\Models\MiniTrip;
use App\Models\User;
use App\Permission;
use App\Support\MiniResults;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Turnieje i wyniki minisiatkówki zgłoszone przez trenerów i koordynatorów mini.
 * Zatwierdzony turniej pojawia się w kalendarzu i na stronie minisiatkówki, zatwierdzone wyniki zastępują dotychczasowe.
 * Relacje z turniejów trafiają do zwykłych „Propozycji treści”.
 */
class MiniApprovals extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(Auth::user()?->hasPermission(Permission::MiniVolleyball), 403);
    }

    public static function pendingCount(): int
    {
        return MiniTrip::where('status', MiniTrip::STATUS_PENDING)->orWhereNotNull('pending_results')->count();
    }

    public function approveTrip(int $id): void
    {
        $trip = MiniTrip::where('status', MiniTrip::STATUS_PENDING)->findOrFail($id);
        $trip->update(['status' => MiniTrip::STATUS_PUBLISHED]);
        AuditLog::record('mini.trip_approved', 'Zatwierdzono turniej minisiatkówki: '.$trip->mediaLabel(), $trip);
        $this->toast('Turniej opublikowany.');
    }

    public function rejectTrip(int $id): void
    {
        $trip = MiniTrip::where('status', MiniTrip::STATUS_PENDING)->findOrFail($id);
        AuditLog::record('mini.trip_rejected', 'Odrzucono zgłoszony turniej minisiatkówki: '.$trip->mediaLabel());
        $trip->delete();
        $this->toast('Zgłoszenie turnieju usunięte.');
    }

    public function approveResults(int $id): void
    {
        $trip = MiniTrip::whereNotNull('pending_results')->findOrFail($id);
        MiniResults::apply($trip, $trip->pending_results['teams'] ?? []);
        AuditLog::record('mini.results_approved', 'Zatwierdzono wyniki: '.$trip->mediaLabel(), $trip);
        $this->toast('Wyniki są na stronie.');
    }

    public function rejectResults(int $id): void
    {
        $trip = MiniTrip::whereNotNull('pending_results')->findOrFail($id);
        $trip->update(['pending_results' => null]);
        $this->toast('Zgłoszone wyniki odrzucone, zostają dotychczasowe.');
    }

    public function render(): View
    {
        $trips = MiniTrip::with(['submitter', 'teams.group'])
            ->where(fn ($query) => $query->where('status', MiniTrip::STATUS_PENDING)->orWhereNotNull('pending_results'))
            ->orderBy('starts_on')
            ->get();

        $playerIds = $trips->flatMap(fn (MiniTrip $trip) => collect($trip->pending_results['teams'] ?? [])->pluck('players')->flatten())->unique();

        return view('livewire.admin.mini-approvals', [
            'trips' => $trips,
            'groups' => MiniGroup::pluck('name', 'id'),
            'players' => MiniPlayer::whereIn('id', $playerIds)->get()->mapWithKeys(fn (MiniPlayer $player) => [$player->id => trim($player->first_name.' '.$player->last_name)]),
            'submitters' => User::whereIn('id', $trips->pluck('pending_results.submitted_by')->filter())->pluck('name', 'id'),
        ]);
    }
}
