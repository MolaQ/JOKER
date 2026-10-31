<?php

namespace App\Livewire;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Player;
use App\Models\PlayerChangeRequest;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Zawodnik lub jego rodzic proponuje zmiany profilu i nowe zdjęcie.
 * Zmiany trafiają do akceptacji administratora lub trenera drużyny.
 */
class PlayerProposalForm extends Component
{
    use ShowsToasts, WithFileUploads;

    public Player $player;

    public bool $open = false;

    /** @var array<string, mixed> */
    public array $data = [];

    public $photo = null;

    public function mount(Player $player): void
    {
        $this->player = $player;
        $this->fillFromPlayer();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'data.height' => 'nullable|integer|min:100|max:250',
            'data.weight' => 'nullable|integer|min:30|max:150',
            'data.reach' => 'nullable|integer|min:150|max:400',
            'data.spike_reach' => 'nullable|integer|min:150|max:400',
            'data.block_reach' => 'nullable|integer|min:150|max:400',
            'data.bio' => 'nullable|string|max:3000',
            'data.interests' => 'nullable|string|max:500',
            'data.email' => 'nullable|email|max:255',
            'data.social_links' => 'array',
            'data.social_links.*' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
            'photo' => 'nullable|image|max:4096|dimensions:ratio=4/5',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return collect(PlayerChangeRequest::EDITABLE_FIELDS)
            ->mapWithKeys(fn (string $label, string $field) => ['data.'.$field => mb_strtolower(__($label))])
            ->put('photo', __('zdjęcie'))
            ->put('data.social_links.*', __('adres profilu'))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return ['data.social_links.*.regex' => __('Adres profilu zacznij od https://')];
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function submit(): void
    {
        abort_unless($this->player->isEditableProposalBy(Auth::user()), 403);

        $this->validate();

        $changes = collect(PlayerChangeRequest::EDITABLE_FIELDS)
            ->keys()
            ->mapWithKeys(fn (string $field) => [$field => $this->normalize($field, $this->data[$field] ?? null)])
            ->filter(fn ($value, string $field) => $value !== $this->normalize($field, $this->currentValue($field)))
            ->all();

        if ($changes === [] && ! $this->photo) {
            $this->addError('data', __('Nic się nie zmieniło - zmień dane lub wybierz zdjęcie.'));

            return;
        }

        if ($changes !== []) {
            $this->player->changeRequests()->create([
                'submitted_by' => Auth::id(),
                'type' => PlayerChangeRequest::TYPE_DATA,
                'proposed_data' => $changes,
            ]);
        }

        if ($this->photo) {
            $this->player->changeRequests()->create([
                'submitted_by' => Auth::id(),
                'type' => PlayerChangeRequest::TYPE_PHOTO,
                'photo_path' => $this->photo->store('player-proposals', 'public'),
            ]);
        }

        $this->photo = null;
        $this->open = false;
        $this->toast(__('Dziękujemy! Zmiany pojawią się po zatwierdzeniu przez trenera lub administratora.'));
    }

    private function fillFromPlayer(): void
    {
        $this->data = collect(PlayerChangeRequest::EDITABLE_FIELDS)
            ->keys()
            ->mapWithKeys(fn (string $field) => [$field => $this->currentValue($field)])
            ->all();
    }

    private function currentValue(string $field): mixed
    {
        return match ($field) {
            'bio' => PlayerChangeRequest::bioAsText($this->player->bio),
            // E-mail proponuje się tylko dla zawodnika bez konta; konto zakłada klub po zatwierdzeniu.
            'email' => '',
            'social_links' => (array) ($this->player->social_links ?? []),
            default => $this->player->{$field},
        };
    }

    /**
     * @return int|string|array<string, string>|null
     */
    private function normalize(string $field, mixed $value): int|string|array|null
    {
        if ($field === 'social_links') {
            $links = collect((array) $value)->map(fn ($url) => trim((string) $url))->filter()->sortKeys()->all();

            return $links ?: null;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return in_array($field, PlayerChangeRequest::NUMERIC_FIELDS, true) ? (int) $value : trim((string) $value);
    }

    public function render(): View
    {
        return view('livewire.player-proposal-form', [
            'socialPlatforms' => app(SiteSettings::class)->playerSocialPlatforms(),
            'canPropose' => $this->player->isEditableProposalBy(Auth::user()),
            'pending' => Auth::check()
                ? $this->player->changeRequests()->pending()->where('submitted_by', Auth::id())->latest('id')->get()
                : collect(),
        ]);
    }
}
