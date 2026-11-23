<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ContentProposal;
use App\Models\RivalTeam;
use App\Support\MatchPicker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Formularz "Zaproponuj treść" dla rodziców, zawodników i współpracowników. Propozycja czeka
 * na zatwierdzenie w panelu (Propozycje treści).
 */
class ContentProposalForm extends Component
{
    use ShowsToasts, WithFileUploads;

    public string $type = '';

    public string $match = '';

    public string $title = '';

    public string $content = '';

    /** Wprowadzenie (zajawka) pod tytułem artykułu; puste = pierwsze zdania relacji. */
    public string $excerpt = '';

    public string $url = '';

    public array $photos = [];

    /** Drużyna rywali, której logo proponujemy. */
    public string $rival = '';

    /** Logo na jasne tło. */
    public $logo = null;

    /** Wariant logo na ciemne tło (do miniatur transmisji). */
    public $logoLight = null;

    public function mount(): void
    {
        $this->type = ContentProposal::typesFor(Auth::user())[0] ?? '';
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function matchOptions(): array
    {
        return MatchPicker::recent();
    }

    /**
     * Drużyny rywali do wyboru przy logo: nazwa i informacja, czy logo już jest.
     *
     * @return array<int, string>
     */
    public function rivalOptions(): array
    {
        return RivalTeam::with('club:id,name,logo_path')->orderBy('full_name')->get(['id', 'rival_club_id', 'full_name', 'logo_path', 'logo_light_path'])
            ->mapWithKeys(fn (RivalTeam $team) => [$team->id => $team->full_name.($team->logoPath() ? '' : ' – '.__('brak logo'))])
            ->all();
    }

    public function submit(): void
    {
        $types = ContentProposal::typesFor(Auth::user());
        abort_if($types === [], 403);

        if ($this->type === ContentProposal::TYPE_LOGO) {
            abort_unless(in_array(ContentProposal::TYPE_LOGO, $types, true), 403);
            $this->submitLogo();

            return;
        }

        $matchKeys = MatchPicker::keys($this->matchOptions());

        // Pusty edytor zostawia „<p></p>”: traktujemy to jak brak treści.
        foreach (['content', 'excerpt'] as $field) {
            if (trim(html_entity_decode(strip_tags($this->{$field}), ENT_QUOTES)) === '') {
                $this->{$field} = '';
            }
        }

        $validated = $this->validate([
            'type' => ['required', Rule::in($types)],
            'match' => ['nullable', Rule::requiredIf($this->type !== ContentProposal::TYPE_ARTICLE), Rule::in($matchKeys)],
            'title' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_ARTICLE), 'string', 'max:255'],
            'content' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_ARTICLE), 'string', 'max:40000'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', Rule::requiredIf($this->type === ContentProposal::TYPE_STREAM), 'url:http,https', 'max:500'],
            'photos' => ['array', 'max:30', Rule::requiredIf($this->type === ContentProposal::TYPE_PHOTOS)],
            'photos.*' => 'image|max:10240',
        ], [
            'match.required' => __('Wybierz mecz.'),
            'photos.required' => __('Dodaj co najmniej jedno zdjęcie.'),
            'photos.*.max' => __('Zdjęcie może mieć najwyżej 10 MB.'),
        ], [
            'title' => __('tytuł'),
            'content' => __('relacja'),
            'excerpt' => __('wprowadzenie'),
            'url' => __('adres transmisji'),
        ]);

        $match = MatchPicker::find($validated['match']);

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'match_type' => $match?->getMorphClass(),
            'match_id' => $match?->getKey(),
            'title' => $validated['title'] ?: null,
            'content' => $validated['content'] ?: null,
            'excerpt' => $validated['type'] === ContentProposal::TYPE_ARTICLE && filled($validated['excerpt'] ?? null) ? $validated['excerpt'] : null,
            'url' => $validated['url'] ?: null,
            'files' => collect($validated['type'] === ContentProposal::TYPE_STREAM ? [] : $this->photos)->map(fn ($photo) => $photo->store('content-proposals', 'public'))->all() ?: null,
        ]);

        $this->reset(['match', 'title', 'content', 'excerpt', 'url', 'photos']);
        $this->toast(__('Dziękujemy! Propozycja czeka na zatwierdzenie.'));
        $this->dispatch('proposal-sent');
    }

    private function submitLogo(): void
    {
        $this->validate([
            'rival' => ['required', Rule::exists('rival_teams', 'id')],
            'logo' => ['nullable', Rule::requiredIf($this->logoLight === null), 'image', 'max:2048'],
            'logoLight' => 'nullable|image|max:2048',
        ], [
            'rival.required' => __('Wybierz drużynę rywali.'),
            'logo.required' => __('Dodaj logo (zwykłe albo na ciemne tło).'),
            'logo.max' => __('Logo może mieć najwyżej 2 MB.'),
            'logoLight.max' => __('Logo może mieć najwyżej 2 MB.'),
        ]);

        $rival = RivalTeam::findOrFail((int) $this->rival);

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => ContentProposal::TYPE_LOGO,
            'match_type' => $rival->getMorphClass(),
            'match_id' => $rival->id,
            'title' => $rival->full_name,
            'files' => array_filter([
                'logo' => $this->logo?->store('content-proposals', 'public'),
                'logo_light' => $this->logoLight?->store('content-proposals', 'public'),
            ]),
        ]);

        $this->reset(['rival', 'logo', 'logoLight']);
        $this->toast(__('Dziękujemy! Propozycja czeka na zatwierdzenie.'));
        $this->dispatch('proposal-sent');
    }

    public function render(): View
    {
        $types = ContentProposal::typesFor(Auth::user());

        return view('livewire.content-proposal-form', [
            'types' => $types,
            'matchOptions' => $this->matchOptions(),
            'rivalOptions' => $this->type === ContentProposal::TYPE_LOGO ? $this->rivalOptions() : [],
        ]);
    }
}
