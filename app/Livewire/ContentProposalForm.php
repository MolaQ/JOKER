<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ContentProposal;
use App\Models\Game;
use App\Models\TournamentMatch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formularz "Zaproponuj treść" dla rodziców i zawodników. Propozycja czeka
 * na zatwierdzenie w panelu (Propozycje treści).
 */
class ContentProposalForm extends Component
{
    use ShowsToasts, WithFileUploads;

    public string $type = '';

    public string $match = '';

    public string $title = '';

    public string $content = '';

    public string $url = '';

    public array $photos = [];

    public function mount(): void
    {
        $this->type = ContentProposal::typesFor(Auth::user())[0] ?? '';
    }

    /**
     * Mecze z ostatnich 6 tygodni i najbliższych 2 tygodni, do wyboru w formularzu.
     *
     * @return array<string, array<string, string>>
     */
    public function matchOptions(): array
    {
        $from = now()->subWeeks(6);
        $to = now()->addWeeks(2);

        return array_filter([
            __('Mecze ligowe') => Game::with(['team', 'competition', 'opponentTeam'])
                ->whereBetween('game_date', [$from, $to])
                ->orderByDesc('game_date')
                ->get()
                ->mapWithKeys(fn (Game $game) => ['game:'.$game->id => $game->mediaLabel()])
                ->all(),
            __('Turnieje') => TournamentMatch::with(['category.tournament', 'home.competitor', 'away.competitor'])
                ->whereBetween('starts_at', [$from, $to])
                ->orderByDesc('starts_at')
                ->get()
                ->mapWithKeys(fn (TournamentMatch $match) => ['tournament_match:'.$match->id => $match->mediaLabel()])
                ->all(),
        ]);
    }

    public function submit(): void
    {
        $types = ContentProposal::typesFor(Auth::user());
        abort_if($types === [], 403);

        $matchKeys = collect($this->matchOptions())->flatMap(fn (array $options) => array_keys($options))->all();

        $validated = $this->validate([
            'type' => ['required', Rule::in($types)],
            'match' => ['nullable', Rule::requiredIf($this->type !== ContentProposal::TYPE_ARTICLE), Rule::in($matchKeys)],
            'title' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_ARTICLE), 'string', 'max:255'],
            'content' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_ARTICLE), 'string', 'max:20000'],
            'url' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_STREAM), 'url:http,https', 'max:500'],
            'photos' => ['array', 'max:30', Rule::requiredIf($this->type === ContentProposal::TYPE_PHOTOS)],
            'photos.*' => 'image|max:10240',
        ], [
            'match.required' => __('Wybierz mecz.'),
            'photos.required' => __('Dodaj co najmniej jedno zdjęcie.'),
            'photos.*.max' => __('Zdjęcie może mieć najwyżej 10 MB.'),
        ], [
            'title' => __('tytuł'),
            'content' => __('treść'),
            'url' => __('adres transmisji'),
        ]);

        [$matchKey, $matchId] = $validated['match'] ? explode(':', $validated['match'], 2) : [null, null];
        $matchType = $matchKey ? (new (['game' => Game::class, 'tournament_match' => TournamentMatch::class][$matchKey]))->getMorphClass() : null;

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'match_type' => $matchType,
            'match_id' => $matchId,
            'title' => $validated['title'] ?: null,
            'content' => $validated['content'] ?: null,
            'url' => $validated['url'] ?: null,
            'files' => collect($validated['type'] === ContentProposal::TYPE_STREAM ? [] : $this->photos)->map(fn ($photo) => $photo->store('content-proposals', 'public'))->all() ?: null,
        ]);

        $this->reset(['match', 'title', 'content', 'url', 'photos']);
        $this->toast(__('Dziękujemy! Propozycja czeka na zatwierdzenie.'));
        $this->dispatch('proposal-sent');
    }

    public function render(): View
    {
        return view('livewire.content-proposal-form', [
            'types' => ContentProposal::typesFor(Auth::user()),
            'matchOptions' => $this->matchOptions(),
        ]);
    }
}
