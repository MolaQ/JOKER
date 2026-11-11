<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Competition;
use App\Models\Game;
use App\Models\RivalTeam;
use App\Models\Season;
use App\Models\Team;
use App\Models\ThumbnailTemplate;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Permission;
use App\Support\ClubLogos;
use App\Support\SearchIndex;
use App\Support\ThumbnailData;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
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

    /** Filtry listy meczów, jak na stronie meczów: sezon, drużyna, rozgrywki, okres, szukanie rywala. */
    public string $fSeason = '';

    public string $fTeam = '';

    public string $fCompetition = '';

    public string $fPeriod = 'all';

    public string $fSearch = '';

    /** Wynik wpisywany w polach: sety wygrane przez gospodarzy i gości. */
    public string $scoreHome = '';

    public string $scoreAway = '';

    /** @var list<array{h: string, a: string}> Punkty w setach (do pięciu). */
    public array $setRows = [];

    public $background = null;

    public $cornerImage = null;

    public $vsImage = null;

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
        $this->fSeason = (string) (Season::current()->value('id') ?? '');
        $this->loadTemplate(ThumbnailTemplate::query()->orderBy('name')->value('id'));
    }

    public function updatedTemplateId(mixed $id): void
    {
        $this->loadTemplate($id ? (int) $id : null);
    }

    public function chooseTemplate(?int $id): void
    {
        $this->loadTemplate($id);
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

    public function updatedVsImage(): void
    {
        $this->validate(['vsImage' => 'image|max:4096']);
        $this->s['vs_image'] = $this->vsImage->store('miniatury/vs', 'public');
        $this->s['vs_style'] = 'image';
        $this->vsImage = null;
    }

    /** Przesuwa napis VS w górę (ujemnie) albo w dół o podaną liczbę pikseli grafiki 1920×1080. */
    public function nudgeVs(int $step): void
    {
        $this->s['vs_offset'] = max(-400, min(400, (int) $this->s['vs_offset'] + $step));
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
            's.title_color' => $color,
            's.title_two_lines' => 'boolean',
            's.show_stage' => 'boolean',
            's.stage_text' => 'nullable|string|max:80',
            's.vs_team_colors' => 'boolean',
            's.vs_v1' => $color, 's.vs_v2' => $color, 's.vs_v3' => $color,
            's.vs_s1' => $color, 's.vs_s2' => $color, 's.vs_s3' => $color,
            's.bar_from' => $color,
            's.bar_to' => $color,
            's.panel' => $color,
            's.images.*.size' => 'integer|min:60|max:400',
            's.vs_font' => ['required', Rule::in(array_keys(ThumbnailTemplate::VS_FONTS))],
            's.vs_color' => $color,
            's.vs_style' => ['required', Rule::in(array_keys(ThumbnailTemplate::VS_STYLES))],
            's.vs_size' => 'required|integer|min:30|max:250',
            's.vs_offset' => 'required|integer|min:-400|max:400',
            's.vs_image' => 'nullable|string|max:255',
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
        $today = now()->startOfDay();
        $search = trim($this->fSearch);
        $tournamentsOnly = $this->fCompetition === 'turnieje';

        $games = $tournamentsOnly ? collect() : Game::query()->with(['team', 'opponentTeam', 'competition'])
            ->when($this->fSeason !== '', fn ($query) => $query->where('season_id', (int) $this->fSeason))
            ->when($this->fTeam !== '', fn ($query) => $query->where('team_id', (int) $this->fTeam))
            ->when($this->fCompetition === 'sparingi', fn ($query) => $query->whereNull('competition_id'))
            ->when(ctype_digit($this->fCompetition), fn ($query) => $query->where('competition_id', (int) $this->fCompetition))
            ->when($this->fPeriod === 'upcoming', fn ($query) => $query->where('game_date', '>=', $today))
            ->when($this->fPeriod === 'results', fn ($query) => $query->where('game_date', '<', now()))
            ->orderByDesc('game_date')
            // Szukanie działa w pamięci (bez polskich znaków, po dacie), więc limit dopiero po nim.
            ->when($search === '', fn ($query) => $query->limit(300))
            ->get()
            ->when($search !== '', fn (Collection $all) => $all->filter(fn (Game $game) => self::matchesSearch($search, [
                $game->opponent,
                $game->opponentTeam?->full_name,
                $game->opponentTeam?->short_name,
                implode(' ', $game->opponentTeam?->aliases ?? []),
                $game->team?->name,
                implode(' ', $game->sidesNames()),
                $game->competition?->name,
                $game->venue,
            ], $game->game_date))->take(300));

        // Najpierw najbliższe (od dziś w przyszłość), potem rozegrane od najnowszego.
        $games = $games->filter(fn (Game $game) => $game->game_date >= $today)->sortBy('game_date')
            ->concat($games->filter(fn (Game $game) => $game->game_date < $today)->sortByDesc('game_date'))
            ->values();

        $showTournaments = $this->fCompetition === '' || $tournamentsOnly;
        $matches = ! $showTournaments ? collect() : TournamentMatch::query()->with(['category.tournament', 'home.competitor', 'away.competitor'])
            ->whereHas('category.tournament', fn ($tournament) => $tournament->when($this->fSeason !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('season_id', (int) $this->fSeason)->orWhereNull('season_id'))))
            ->when($this->fPeriod === 'upcoming', fn ($query) => $query->where(fn ($inner) => $inner->whereNull('starts_at')->orWhere('starts_at', '>=', $today)))
            ->when($this->fPeriod === 'results', fn ($query) => $query->whereNotNull('home_sets'))
            ->get()
            ->filter(fn (TournamentMatch $match) => collect([$match->home, $match->away])->contains(fn ($side) => $side?->isOwnTeam()
                && ($this->fTeam === '' || ($side->competitor_type !== 'rival_team' && (int) $side->competitor_id === (int) $this->fTeam))))
            ->when($search !== '', fn ($all) => $all->filter(fn (TournamentMatch $match) => self::matchesSearch($search, [
                $match->homeName(), $match->awayName(), $match->category->name, $match->category->tournament->name,
            ], $match->starts_at)))
            ->sortBy(fn (TournamentMatch $match) => [$match->starts_at && $match->starts_at < $today ? 1 : 0, $match->starts_at && $match->starts_at < $today ? -$match->starts_at->getTimestamp() : (int) $match->starts_at?->getTimestamp()])
            ->groupBy(fn (TournamentMatch $match) => $match->category->tournament->name);

        return compact('games', 'matches');
    }

    /**
     * Szukanie bez polskich znaków i wielkości liter, także po dacie: „15.11”, „15.11.2026”, „2026-11-15”, „15 listopada”, „sobota”.
     *
     * @param  array<int, ?string>  $texts
     */
    private static function matchesSearch(string $search, array $texts, ?\DateTimeInterface $date): bool
    {
        $query = SearchIndex::normalize($search);

        if ($query === '') {
            return true;
        }

        $when = $date ? Carbon::instance($date) : null;
        $haystack = SearchIndex::normalize(implode(' ', [
            ...$texts,
            $when?->format('d.m.Y'),
            $when?->format('j.n.Y'),
            $when?->format('Y-m-d'),
            $when?->translatedFormat('j F Y l'),
            $when?->translatedFormat('F'),
        ]));

        // Sama data („15.11”, „5.11.2026”): całe liczby, żeby „5.11” nie znalazło 15 listopada ani 11.11.2015.
        if (preg_match('/^[0-9 ]+$/', $query)) {
            return str_contains(' '.$haystack.' ', ' '.$query.' ');
        }

        return collect(explode(' ', $query))->every(fn (string $term) => str_contains($haystack, $term));
    }

    public function resetFilters(): void
    {
        $this->fSeason = (string) (Season::current()->value('id') ?? '');
        $this->fTeam = $this->fCompetition = $this->fSearch = '';
        $this->fPeriod = 'all';
    }

    public function render(): View
    {
        $user = Auth::user();
        $template = $this->templateId ? ThumbnailTemplate::find($this->templateId) : null;

        return view('livewire.admin.thumbnail-studio', $this->choices() + [
            'templates' => ThumbnailTemplate::query()->with(['user:id,name', 'competition:id,name', 'tournament:id,name'])->orderBy('name')->get(),
            'canChange' => $template?->canBeChangedBy($user) ?? true,
            'logos' => ClubLogos::all()->map(fn (array $logo) => ['name' => $logo['name'], 'teams' => $logo['teams'], 'url' => ThumbnailData::src($logo['url'])])->values(),
            'seasons' => Season::query()->orderByDesc('start_date')->get(['id', 'name']),
            'teams' => Team::query()->orderBy('display_order')->get(['id', 'name']),
            'sponsors' => ThumbnailData::sponsorLogos($this->m['tournament_id'] ?? null),
            'competitions' => Competition::query()->orderBy('name')->get(['id', 'name']),
            'tournaments' => Tournament::query()->orderByDesc('start_date')->get(['id', 'name', 'edition']),
            'fileBase' => Str::slug(trim(($this->m['home']['name'] ?? '').' '.($this->m['away']['name'] ?? '')).' '.($this->m['date'] ?? '')) ?: 'miniatura',
        ]);
    }
}
