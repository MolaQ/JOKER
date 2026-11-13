<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\LinkReport;
use App\Support\LinkReports;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Zgłoszone niedziałające linki do filmów i transmisji: odpięcie od meczu albo „Działa”.
 */
class LinkReportReview extends Component
{
    use ShowsToasts;

    public function boot(): void
    {
        abort_unless(LinkReports::canResolve(Auth::user()), 403);
    }

    public function remove(int $id): void
    {
        LinkReports::remove(LinkReport::pending()->with('reportable')->findOrFail($id), Auth::user());
        $this->toast('Link odpięty. Na stronie już się nie pokazuje.');
    }

    public function dismiss(int $id): void
    {
        LinkReports::dismiss(LinkReport::pending()->findOrFail($id), Auth::user());
        $this->toast('Zgłoszenie zamknięte, link zostaje.');
    }

    public function render(): View
    {
        // Kilka zgłoszeń tego samego linku pokazujemy jako jedno, z liczbą zgłaszających.
        $reports = LinkReport::pending()->with(['reportable', 'user:id,name'])->oldest('id')->get()
            ->groupBy(fn (LinkReport $report) => $report->reportable_type.':'.$report->reportable_id.':'.$report->url)
            ->map(fn ($group) => ['report' => $group->first(), 'users' => $group->pluck('user.name')->filter()->unique()->values(), 'last' => $group->max('created_at')])
            ->values();

        return view('livewire.admin.link-report-review', ['reports' => $reports]);
    }
}
