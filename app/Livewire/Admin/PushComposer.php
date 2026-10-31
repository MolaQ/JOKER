<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\PushSubscription;
use App\Models\Team;
use App\Services\PushNotifier;
use App\Support\SiteSettings;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Ręczne wysłanie powiadomienia push (np. „Zmiana godziny meczu”, „Zapraszamy na turniej”) do wszystkich albo wybranych kibiców.
 */
class PushComposer extends Component
{
    use ShowsToasts;

    public string $title = '';

    public string $body = '';

    public string $url = '';

    /** all, topic:{temat} albo team:{id drużyny} */
    public string $target = 'all';

    public function send(PushNotifier $push): void
    {
        $this->validate([
            'title' => 'required|string|max:80',
            'body' => 'required|string|max:200',
            'url' => ['nullable', 'string', 'max:500', 'regex:#^(https?://|/)#'],
            'target' => 'required|string',
        ], [
            'title.required' => 'Wpisz tytuł powiadomienia.',
            'body.required' => 'Wpisz treść powiadomienia.',
            'url.regex' => 'Link zaczyna się od https:// albo od / (strona klubu).',
        ]);

        $sent = $push->send(trim($this->title), trim($this->body), $this->url !== '' ? app(SiteSettings::class)->href($this->url) : url('/'), 'manual-'.now()->timestamp, audience: $this->audience());

        $this->reset('title', 'body', 'url');
        $this->toast($sent > 0 ? "Wysłano na urządzenia: {$sent}." : 'Nikt z wybranych odbiorców nie ma włączonych powiadomień.', $sent > 0 ? 'success' : 'error');
    }

    /**
     * @return array{topic?: ?string, teams?: list<int>}
     */
    private function audience(): array
    {
        [$kind, $value] = explode(':', $this->target, 2) + [null, null];

        return match ($kind) {
            'topic' => array_key_exists((string) $value, PushSubscription::TOPICS) ? ['topic' => $value] : [],
            'team' => ['topic' => 'results', 'teams' => [(int) $value]],
            default => [],
        };
    }

    public function render(PushNotifier $push): View
    {
        return view('livewire.admin.push-composer', [
            'enabled' => $push->enabled(),
            'total' => PushSubscription::count(),
            'recipients' => $push->countAudience($this->audience()),
            'teams' => Team::orderBy('display_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
