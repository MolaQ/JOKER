<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Competition;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\ThumbnailTemplate;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Permission;
use App\Support\ClubLogos;
use App\Support\ThumbnailData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Miniatury transmisji na YouTube (/miniatury): para grafik (zapowiedź i podsumowanie) z meczu w bazie
 * albo wpisana ręcznie, w wyglądzie zapisanym jako szablon (kolory, czcionka, obrazki w rogach, tło).
 * Kolor rywala zapisuje się przy jego drużynie i wraca przy kolejnym meczu.
 */
class ThumbnailStudio extends Component
{
    use ShowsToasts, WithFileUploads;

    public ?int $templateId = null;

    public string $templateName = '';

    public ?int $templateCompetition = null;

    public ?int $templateTournament = null;

    /** @var array<string, mixed> */
    public array $s = [];

    /** @var array<string, mixed> */
    public array $m = [];

    /** Wybrany mecz: „game:12”, „match:5” albo pusty (wpisywanie ręczne). */
    public string $pick = '';

    /** Wynik wpisywany w polach: sety wygrane przez gospodarzy i gości. */
    public string $scoreHome = '';

    public string $scoreAway = '';

    /** @var list<array{h: string, a: string}> Punkty w setach (do pięciu). */
    public array $setRows = [];

    public $background = null;

    public $cornerImage = null;

    public string $cornerPosition = 'tl';

    public $homeLogo = null;

    public $awayLogo = null;

    public function boot(): void
    {
        abort_unless(Auth::user()?->can(Permission::Graphics->value), 403);
    }

    public function mount(): void
    {
        $this->m = ThumbnailData::blank();
        $this->fillResultInputs();
        $this->loadTemplate(ThumbnailTemplate::query()->orderBy('name')->value('id'));
    }

    public function updatedTemplateId(mixed $id): void
    {
        $this->loadTemplate($id ? (int) $id : null);
    }

    private function loadTemplate(?int $id): void
    {
        $template = $id ? ThumbnailTemplate::find($id) : null;
        $this->templateId = $template?->id;
        $this->templateName = (string) $template?->name;
        $this->templateCompetition = $template?->competition_id;
        $this->templateTournament = $template?->tournament_id;
        $this->s = $template ? $template->resolvedSettings() : ThumbnailTemplate::DEFAULTS;
        $this->resetErrorBag();
    }

    public function updatedPick(string $value): void
    {
        [$type, $id] = array_pad(explode(':', $value, 2), 2, null);

        $this->m = match ($type) {
            'game' => ThumbnailData::fromGame(Game::findOrFail((int) $id)),
            'match' => ThumbnailData::fromTournamentMatch(TournamentMatch::findOrFail((int) $id)),
            default => ThumbnailData::blank(),
        };
        $this->fillResultInputs();

        // Szablon przypisany do rozgrywek albo turnieju meczu wybiera się sam.
        $template = match (true) {
            filled($this->m['competition_id']) => ThumbnailTemplate::where('competition_id', $this->m['competition_id'])->first(),
            filled($this->m['tournament_id']) => ThumbnailTemplate::where('tournament_id', $this->m['tournament_id'])->first()
                ?? ThumbnailTemplate::query()->orderBy('name')->get()->first(fn (ThumbnailTemplate $template) => $template->resolvedSettings()['tournament_logo']),
            default => null,
        };

        if ($template && $template->id !== $this->templateId) {
            $this->loadTemplate($template->id);
        }
    }

    /**
     * Kolor połowy rywala zapamiętuje się przy jego drużynie.
     */
    public function updatedM(mixed $value, string $key): void
    {
        if (! preg_match('/^(home|away)\.color$/', $key, $found) || ! preg_match('/^#[0-9a-fA-F]{6}$/', (string) $value)) {
            return;
        }

        $sideKey = (string) ($this->m[$found[1]]['key'] ?? '');

        if (str_starts_with($sideKey, 'rival:')) {
            RivalTeam::whereKey((int) Str::after($sideKey, 'rival:'))->update(['brand_color' => $value]);
        }
    }

    public function useLogo(string $side, string $url): void
    {
        abort_unless(in_array($side, ['home', 'away'], true), 404);
        $this->m[$side]['logo'] = $url;
    }

    public function swapSides(): void
    {
        [$this->m['home'], $this->m['away']] = [$this->m['away'], $this->m['home']];
        $sets = ThumbnailData::parseSets((string) $this->m['sets']);
        $this->m['sets'] = collect($sets)->map(fn (array $set) => $set[1].':'.$set[0])->implode(', ');
        if (preg_match('/^\s*(\d)\s*:\s*(\d)\s*$/', (string) $this->m['score'], $score)) {
            $this->m['score'] = $score[2].':'.$score[1];
        }
        $this->fillResultInputs();
    }

    /**
     * Pola wyniku i setów z danych grafiki (po wyborze meczu albo zamianie stron).
     */
    private function fillResultInputs(): void
    {
        $score = preg_match('/^\s*(\d)\s*:\s*(\d)\s*$/', (string) $this->m['score'], $found) ? [$found[1], $found[2]] : ['', ''];
        [$this->scoreHome, $this->scoreAway] = $score;
        $sets = ThumbnailData::parseSets((string) $this->m['sets']);
        $this->setRows = array_map(fn (int $index) => ['h' => (string) ($sets[$index][0] ?? ''), 'a' => (string) ($sets[$index][1] ?? '')], range(0, 4));
    }

    /**
     * Wpisany wynik i sety trafiają do grafiki; pusty wynik liczy się z setów.
     */
    public function updated(string $name): void
    {
        if (! preg_match('/^(scoreHome|scoreAway|setRows)/', $name)) {
            return;
        }

        $digits = fn ($value) => preg_replace('/\D/', '', (string) $value);
        $this->m['score'] = $digits($this->scoreHome) !== '' && $digits($this->scoreAway) !== '' ? $digits($this->scoreHome).':'.$digits($this->scoreAway) : '';
        $this->m['sets'] = collect($this->setRows)
            ->filter(fn (array $row) => $digits($row['h'] ?? '') !== '' && $digits($row['a'] ?? '') !== '')
            ->map(fn (array $row) => (int) $digits($row['h']).':'.(int) $digits($row['a']))
            ->implode(', ');
    }

    public function clearResult(): void
    {
        $this->m['score'] = '';
        $this->m['sets'] = '';
        $this->fillResultInputs();
    }

    public function updatedHomeLogo(): void
    {
        $this->storeLogo('home');
    }

    public function updatedAwayLogo(): void
    {
        $this->storeLogo('away');
    }

    private function storeLogo(string $side): void
    {
        $field = $side.'Logo';
        $this->validate([$field => 'image|max:4096']);
        $this->m[$side]['logo'] = ThumbnailData::src($this->{$field}->store('miniatury/logotypy', 'public'));
        $this->{$field} = null;
    }

    public function updatedBackground(): void
    {
        $this->validate(['background' => 'image|max:8192']);
        $this->s['background'] = $this->background->store('miniatury/tla', 'public');
        $this->background = null;
    }

    public function updatedCornerImage(): void
    {
        $this->validate(['cornerImage' => 'image|max:4096', 'cornerPosition' => Rule::in(array_keys(ThumbnailTemplate::POSITIONS))]);
        $images = collect($this->s['images'])->reject(fn (array $image) => $image['position'] === $this->cornerPosition);
        $images->push(['path' => $this->cornerImage->store('miniatury/obrazki', 'public'), 'position' => $this->cornerPosition, 'size' => 200]);
        $this->s['images'] = $images->values()->all();
        $this->cornerImage = null;
    }

    public function removeImage(int $index): void
    {
        $images = $this->s['images'];
        unset($images[$index]);
        $this->s['images'] = array_values($images);
    }

    public function saveTemplate(bool $asNew = false): void
    {
        $this->validateTemplate();
        $template = $this->templateId && ! $asNew ? ThumbnailTemplate::find($this->templateId) : null;

        if ($template && ! $template->canBeChangedBy(Auth::user())) {
            $asNew = true;
            $template = null;
        }

        $data = [
            'name' => trim($this->templateName),
            'competition_id' => $this->templateCompetition ?: null,
            'tournament_id' => $this->templateTournament ?: null,
            'settings' => array_intersect_key($this->s, ThumbnailTemplate::DEFAULTS),
        ];

        if ($template) {
            $template->update($data);
            $this->toast(__('Zapisano szablon „:name”.', ['name' => $template->name]));

            return;
        }

        if ($asNew && ThumbnailTemplate::where('name', $data['name'])->exists()) {
            $data['name'] .= ' ('.Auth::user()->name.')';
        }

        $template = ThumbnailTemplate::create($data + ['user_id' => Auth::id()]);
        $this->templateId = $template->id;
        $this->templateName = $template->name;
        $this->toast(__('Zapisano nowy szablon „:name”.', ['name' => $template->name]));
    }

    public function deleteTemplate(): void
    {
        $template = ThumbnailTemplate::findOrFail($this->templateId);
        abort_unless($template->canBeChangedBy(Auth::user()), 403);
        $template->delete();
        $this->toast(__('Usunięto szablon „:name”.', ['name' => $template->name]));
        $this->loadTemplate(ThumbnailTemplate::query()->orderBy('name')->value('id'));
    }

    private function validateTemplate(): void
    {
        $color = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $this->validate([
            'templateName' => 'required|string|max:120',
            'templateCompetition' => 'nullable|exists:competitions,id',
            'templateTournament' => 'nullable|exists:tournaments,id',
            's.layout' => ['required', Rule::in(array_keys(ThumbnailTemplate::LAYOUTS))],
            's.font' => ['required', Rule::in(array_keys(ThumbnailTemplate::FONTS))],
            's.label' => 'nullable|string|max:120',
            's.preview_title' => 'nullable|string|max:60',
            's.summary_title' => 'nullable|string|max:60',
            's.live_text' => 'nullable|string|max:60',
            's.accent' => $color,
            's.bar_from' => $color,
            's.bar_to' => $color,
            's.panel' => $color,
            's.images.*.size' => 'integer|min:60|max:400',
            's.vs_font' => ['required', Rule::in(array_keys(ThumbnailTemplate::VS_FONTS))],
            's.vs_color' => $color,
            's.live_position' => ['required', Rule::in(array_keys(ThumbnailTemplate::LIVE_POSITIONS))],
        ], [], [
            'templateName' => __('nazwa szablonu'),
        ]);
    }

    /**
     * Mecze do wyboru: klubu z ostatnich i najbliższych tygodni oraz mecze turniejów.
     *
     * @return array{games: Collection, matches: Collection}
     */
    private function choices(): array
    {
        // Najpierw najbliższe (od dziś w przyszłość), potem rozegrane od najnowszego.
        $games = Game::query()->with(['team', 'opponentTeam', 'competition'])
            ->whereBetween('game_date', [now()->subDays(120), now()->addDays(120)])
            ->limit(200)->get();
        $today = now()->startOfDay();
        $games = $games->filter(fn (Game $game) => $game->game_date >= $today)->sortBy('game_date')
            ->concat($games->filter(fn (Game $game) => $game->game_date < $today)->sortByDesc('game_date'))
            ->values();

        $matches = TournamentMatch::query()->with(['category.tournament', 'home.competitor', 'away.competitor'])
            ->whereHas('category.tournament', fn ($tournament) => $tournament->where('start_date', '>=', now()->subDays(120)->toDateString()))
            ->get()
            ->sortBy(fn (TournamentMatch $match) => [$match->starts_at && $match->starts_at < now()->startOfDay() ? 1 : 0, $match->starts_at && $match->starts_at < now()->startOfDay() ? -$match->starts_at->getTimestamp() : (int) $match->starts_at?->getTimestamp()])
            ->filter(fn (TournamentMatch $match) => $match->home?->isOwnTeam() || $match->away?->isOwnTeam())
            ->groupBy(fn (TournamentMatch $match) => $match->category->tournament->name);

        return compact('games', 'matches');
    }

    public function render(): View
    {
        $user = Auth::user();
        $template = $this->templateId ? ThumbnailTemplate::find($this->templateId) : null;

        return view('livewire.admin.thumbnail-studio', $this->choices() + [
            'templates' => ThumbnailTemplate::query()->with('user:id,name')->orderBy('name')->get(),
            'canChange' => $template?->canBeChangedBy($user) ?? true,
            'logos' => ClubLogos::all()->map(fn (array $logo) => ['name' => $logo['name'], 'url' => ThumbnailData::src($logo['url'])])->values(),
            'sponsors' => ThumbnailData::sponsorLogos($this->m['tournament_id'] ?? null),
            'competitions' => Competition::query()->orderBy('name')->get(['id', 'name']),
            'tournaments' => Tournament::query()->orderByDesc('start_date')->get(['id', 'name', 'edition']),
            'fileBase' => Str::slug(trim(($this->m['home']['name'] ?? '').' '.($this->m['away']['name'] ?? '')).' '.($this->m['date'] ?? '')) ?: 'miniatura',
        ]);
    }
}
