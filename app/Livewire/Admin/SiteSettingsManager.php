<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
use App\View\Components\Toasts;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel „Wygląd i ustawienia”. Formularz budowany automatycznie z config/site.php.
 */
class SiteSettingsManager extends Component
{
    use ShowsToasts, WithFileUploads;

    public string $tab = 'ogolne';

    /** @var array<string, mixed> */
    public array $values = [];

    /** @var array<string, mixed> */
    public array $uploads = [];

    /**
     * Ustawienia zmienia tylko administrator (sprawdzane przy każdym żądaniu).
     */
    public function boot(): void
    {
        abort_unless(auth()->user()?->can('site.manage'), 403);
    }

    public function mount(SiteSettings $settings): void
    {
        foreach (SiteSettings::fields() as $key => $field) {
            $value = $settings->get($key);

            $this->values[$key] = match ($field['type']) {
                'toggle' => (bool) $value,
                'links' => array_values((array) $value),
                default => (string) $value,
            };
        }

        if (! array_key_exists($this->tab, $this->groups())) {
            $this->tab = (string) array_key_first($this->groups());
        }
    }

    /**
     * @return array<string, array{label: string, icon: string, fields: array<string, array<string, mixed>>}>
     */
    public function groups(): array
    {
        return config('site.groups');
    }

    public function selectTab(string $tab): void
    {
        if (array_key_exists($tab, $this->groups())) {
            $this->tab = $tab;
        }
    }

    public function addLink(string $key): void
    {
        $this->values[$key][] = ['label' => '', 'url' => ''];
    }

    public function removeLink(string $key, int $index): void
    {
        unset($this->values[$key][$index]);
        $this->values[$key] = array_values($this->values[$key]);
    }

    public function moveLink(string $key, int $index, int $direction): void
    {
        $target = $index + $direction;
        $links = $this->values[$key];

        if (! isset($links[$index], $links[$target])) {
            return;
        }

        [$links[$index], $links[$target]] = [$links[$target], $links[$index]];
        $this->values[$key] = $links;
    }

    public function removeImage(string $key): void
    {
        $this->values[$key] = '';
        unset($this->uploads[$key]);
    }

    /**
     * Przywraca wartości domyślne dla bieżącej zakładki (po zapisaniu).
     */
    public function restoreDefaults(): void
    {
        foreach ($this->groups()[$this->tab]['fields'] as $key => $field) {
            $this->values[$key] = $field['type'] === 'toggle' ? (bool) $field['default'] : $field['default'] ?? '';
            unset($this->uploads[$key]);
        }

        $this->persist();
        $this->toast('Przywrócono ustawienia domyślne w zakładce „'.$this->groups()[$this->tab]['label'].'”.');
    }

    public function save(): void
    {
        $this->persist();
        $this->toast('Zapisano. Zmiany są już widoczne na stronie.');
    }

    /**
     * Sprawdza i zapisuje wszystkie pola; nowy wygląd toastów od razu trafia do przeglądarki.
     */
    protected function persist(): void
    {
        $this->validate($this->rules(), $this->messages(), $this->attributeNames());

        $fields = SiteSettings::fields();
        $toStore = [];

        foreach ($fields as $key => $field) {
            $value = $this->values[$key] ?? null;

            if ($field['type'] === 'image' && isset($this->uploads[$key]) && $this->uploads[$key]) {
                $value = $this->uploads[$key]->store('settings', 'public');
                $this->values[$key] = $value;
            }

            $toStore[$key] = match ($field['type']) {
                'toggle' => (bool) $value,
                'links' => collect((array) $value)
                    ->map(fn ($link) => ['label' => trim((string) ($link['label'] ?? '')), 'url' => trim((string) ($link['url'] ?? ''))])
                    ->filter(fn ($link) => $link['label'] !== '' || $link['url'] !== '')
                    ->values()
                    ->all(),
                'color' => strtolower((string) $value),
                default => trim((string) $value),
            };
        }

        app(SiteSettings::class)->set($toStore);
        SearchIndex::forget();

        $this->uploads = [];
        $this->values = array_merge($this->values, Arr::only($toStore, array_keys($fields)));
        $this->dispatch('toast-config', ...Toasts::appearance(app(SiteSettings::class)));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [];

        foreach (SiteSettings::fields() as $key => $field) {
            $path = 'values.'.$key;

            match ($field['type']) {
                'color' => $rules[$path] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'toggle' => $rules[$path] = ['boolean'],
                'textarea' => $rules[$path] = ['nullable', 'string', 'max:2000'],
                'select' => $rules[$path] = ['required', Rule::in(array_keys($field['options']))],
                'url' => $rules[$path] = ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|#|mailto:|tel:)/i'],
                'image' => $rules['uploads.'.$key] = ['nullable', 'image', 'max:4096'],
                'links' => [
                    $rules[$path] = ['array', 'max:20'],
                    $rules[$path.'.*.label'] = ['nullable', 'string', 'max:60'],
                    $rules[$path.'.*.url'] = ['nullable', 'string', 'max:500', 'regex:/^(https?:\/\/|\/|#|mailto:|tel:)/i'],
                ],
                default => $rules[$path] = ['nullable', 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'required' => 'Pole „:attribute” jest wymagane.',
            'regex' => 'Pole „:attribute” ma zły format. Adres zacznij od https:// albo / (np. /kontakt), kolor wpisz jako #rrggbb.',
            'max' => 'Pole „:attribute” jest za długie.',
            'image' => 'Plik w polu „:attribute” musi być obrazkiem.',
            'uploads.*.max' => 'Obrazek w polu „:attribute” może mieć najwyżej 4 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attributeNames(): array
    {
        return collect(SiteSettings::fields())
            ->flatMap(fn (array $field, string $key) => [
                'values.'.$key => $field['label'],
                'uploads.'.$key => $field['label'],
                'values.'.$key.'.*.label' => 'tekst linku',
                'values.'.$key.'.*.url' => 'adres linku',
            ])
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.site-settings-manager', [
            'groups' => $this->groups(),
        ]);
    }
}
