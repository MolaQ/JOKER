<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\ContentProposal;
use App\Support\MatchPicker;
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
     * @return array<string, array<string, string>>
     */
    public function matchOptions(): array
    {
        return MatchPicker::recent();
    }

    public function submit(): void
    {
        $types = ContentProposal::typesFor(Auth::user());
        abort_if($types === [], 403);

        $matchKeys = MatchPicker::keys($this->matchOptions());

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

        $match = MatchPicker::find($validated['match']);

        ContentProposal::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'match_type' => $match?->getMorphClass(),
            'match_id' => $match?->getKey(),
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
