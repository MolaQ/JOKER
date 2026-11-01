<?php

namespace App\Livewire;

use App\Livewire\Admin\TournamentEditor;
use App\Livewire\Concerns\ShowsToasts;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TrainingSession;
use App\Support\TournamentBracket;
use App\Support\TournamentHalls;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Once;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Ekran „Wpisz wyniki” na telefon dla osób wpisujących wyniki turnieju: godzina rozpoczęcia meczu
 * (domyślnie z harmonogramu), sety i zakończenie meczu. Godziny w harmonogramie się nie zmieniają;
 * późniejszy start pokazuje przy kolejnych meczach na tym boisku „możliwe opóźnienie”.
 */
class TournamentScorer extends Component
{
    use ShowsToasts;

    public Tournament $tournament;

    #[Url]
    public string $day = '';

    #[Url]
    public string $court = '';

    /** @var array<int, array{start: string, sets: string}> */
    public array $rows = [];

    public function mount(Tournament $tournament): void
    {
        abort_unless($tournament->canBeScoredBy(Auth::user()), 403);

        $this->tournament = $tournament;
        $days = $this->days();
        $this->day = in_array($this->day, $days, true) ? $this->day : (in_array(today()->toDateString(), $days, true) ? today()->toDateString() : ($days[0] ?? ''));
    }

    /**
     * Mecz rozpoczął się o podanej godzinie (domyślnie z harmonogramu).
     */
    public function start(int $id): void
    {
        $match = $this->match($id);
        $starts = $this->startTime($match);

        if (! $starts) {
            return;
        }

        $match->update(['planned_starts_at' => $match->planned_starts_at ?? $match->starts_at ?? $starts, 'starts_at' => $starts, 'is_live' => true]);
        $this->toast(__('Mecz nr :number rozpoczęty o :time.', ['number' => $match->number, 'time' => $starts->format('H:i')]));
    }

    /**
     * Zapisuje sety; z $finish mecz się kończy (wynik idzie do tabel i drabinki).
     */
    public function save(int $id, bool $finish = false): void
    {
        $match = $this->match($id);
        $sets = TournamentEditor::parseSets((string) ($this->rows[$id]['sets'] ?? ''), "rows.{$id}.sets");
        $home = collect($sets)->filter(fn (array $set) => $set[0] > $set[1])->count();
        $away = collect($sets)->filter(fn (array $set) => $set[1] > $set[0])->count();

        if ($finish && ($sets === [] || $home === $away)) {
            $this->addError("rows.{$id}.sets", __('Mecz kończy się, gdy jedna drużyna wygrała więcej setów.'));

            return;
        }

        $starts = $match->starts_at && ($match->is_live || $match->home_sets !== null) ? $match->starts_at : $this->startTime($match);

        $match->update([
            'planned_starts_at' => $match->planned_starts_at ?? $match->starts_at ?? $starts,
            'starts_at' => $starts ?? $match->starts_at,
            'sets_score' => $sets ?: null,
            'home_sets' => $sets ? $home : null,
            'away_sets' => $sets ? $away : null,
            'is_live' => ! $finish,
            'mvp' => mb_substr(trim((string) ($this->rows[$id]['mvp'] ?? '')), 0, 120) ?: null,
        ]);

        TournamentBracket::sync($match->category);
        $this->resetErrorBag("rows.{$id}.sets");
        $this->toast($finish ? __('Zakończono: :home – :away :score.', ['home' => $match->homeName(), 'away' => $match->awayName(), 'score' => $home.':'.$away]) : __('Zapisano wynik meczu nr :number.', ['number' => $match->number]));
    }

    /**
     * Pomyłka przy zakończeniu: mecz wraca do trwających (wynik zostaje do poprawy).
     */
    public function reopen(int $id): void
    {
        $this->match($id)->update(['is_live' => true]);
    }

    /**
     * Mecz rozpoczęty przez pomyłkę: wraca do nierozpoczętych z godziną z harmonogramu, bez wpisanych setów.
     */
    public function cancelStart(int $id): void
    {
        $match = $this->match($id);

        if (! $match->is_live) {
            return;
        }

        $match->update([
            'starts_at' => $match->planned_starts_at ?? $match->starts_at,
            'is_live' => false,
            'sets_score' => null,
            'home_sets' => null,
            'away_sets' => null,
        ]);

        TournamentBracket::sync($match->category);
        unset($this->rows[$id]);
        $this->resetErrorBag("rows.{$id}.sets");
        $this->toast(__('Cofnięto rozpoczęcie meczu nr :number.', ['number' => $match->number]));
    }

    private function startTime(TournamentMatch $match): ?Carbon
    {
        $time = trim((string) ($this->rows[$match->id]['start'] ?? ''));
        $planned = $match->planned_starts_at ?? $match->starts_at;

        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $time)) {
            $this->addError("rows.{$match->id}.start", __('Wpisz godzinę jak 10:45.'));

            return null;
        }

        $this->resetErrorBag("rows.{$match->id}.start");

        return Carbon::parse(($planned ?? Carbon::parse($this->day ?: today()))->toDateString().' '.$time);
    }

    private function match(int $id): TournamentMatch
    {
        abort_unless($this->tournament->canBeScoredBy(Auth::user()), 403);
        Once::flush();

        return TournamentMatch::whereIn('tournament_category_id', $this->tournament->categories()->select('id'))->findOrFail($id);
    }

    /**
     * @return list<string>
     */
    private function days(): array
    {
        return $this->allMatches()->map(fn (TournamentMatch $match) => ($match->planned_starts_at ?? $match->starts_at)?->toDateString())->filter()->unique()->sort()->values()->all();
    }

    /**
     * @return Collection<int, TournamentMatch>
     */
    private function allMatches(): Collection
    {
        return TournamentMatch::whereIn('tournament_category_id', $this->tournament->categories()->select('id'))
            ->with(['category', 'home', 'away', 'group'])
            ->get();
    }

    public function render(): View
    {
        $matches = $this->allMatches()
            ->filter(fn (TournamentMatch $match) => ($match->planned_starts_at ?? $match->starts_at)?->toDateString() === $this->day)
            ->sortBy(fn (TournamentMatch $match) => [($match->planned_starts_at ?? $match->starts_at)->getTimestamp(), (string) $match->court, $match->number]);

        // Boiska dnia: najpierw hala klubowa (III, II, I, główne), potem inne obiekty po nazwie.
        $order = array_flip(TournamentHalls::MAIN_VENUES);
        $courts = $matches->pluck('court')->filter()->unique()
            ->sortBy(fn (string $court) => [($venue = TrainingSession::venueForCourt($court)) ? 0 : 1, $venue ? $order[$venue] : 0, mb_strtolower($court)])
            ->values();
        $categoryIds = $matches->filter(fn (TournamentMatch $match) => filled($match->court))->mapWithKeys(fn (TournamentMatch $match) => [$match->court => $match->tournament_category_id])->all();
        $shown = $matches->when($this->court !== '', fn (Collection $all) => $all->where('court', $this->court))->values();

        foreach ($shown as $match) {
            $this->rows[$match->id] ??= [
                'start' => (($match->is_live || $match->home_sets !== null) ? $match->starts_at : ($match->planned_starts_at ?? $match->starts_at))?->format('H:i') ?? '',
                'sets' => collect($match->sets_score ?? [])->map(fn (array $set) => $set[0].':'.$set[1])->implode(', '),
                'mvp' => (string) $match->mvp,
            ];
        }

        return view('livewire.tournament-scorer', [
            'matches' => $shown,
            'courts' => $courts,
            'categoryIds' => $categoryIds,
            'days' => $this->days(),
        ]);
    }
}
