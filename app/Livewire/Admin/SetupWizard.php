<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ShowsToasts;
use App\Models\Team;
use App\Support\ClubNameForms;
use App\Support\LogoColors;
use App\Support\SearchIndex;
use App\Support\SiteSettings;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Kreator startowy: najważniejsze ustawienia nowego klubu w czterech krokach (nazwa, herb i kolory, kontakt, drużyny).
 * Zmienia tylko te pola, które pokazuje; resztę klub ustawia potem w System.
 */
class SetupWizard extends Component
{
    use ShowsToasts, WithFileUploads;

    /** @var list<string> kroki po kolei */
    public const STEPS = ['klub', 'wyglad', 'kontakt', 'druzyny'];

    /** @var list<string> pola ustawień, które kreator pokazuje */
    public const NAME_KEYS = ['club_name', 'club_short_name', 'club_name_genitive', 'club_name_locative', 'club_legal_name', 'club_tagline'];

    public const COLOR_KEYS = ['color_accent', 'color_topbar_bg', 'color_topbar_text', 'color_dark', 'color_club_navy', 'color_panel'];

    /** Kolory, które idą za kolorami pokazanymi w kreatorze (żeby strona była spójna bez zaglądania do wszystkich pól). */
    private const COLOR_FOLLOWERS = [
        'color_like' => 'color_accent', 'color_venue' => 'color_accent', 'color_gym' => 'color_accent',
        'color_nav_button_bg' => 'color_topbar_bg', 'color_nav_button_text' => 'color_topbar_text', 'color_countdown' => 'color_topbar_bg',
    ];

    public const CONTACT_KEYS = ['contact_email', 'contact_phone', 'contact_address', 'footer_text', 'footer_button_label', 'contact_school_name', 'site_title', 'site_description'];

    /** @var array<string, string> */
    public const CATEGORIES = ['senior' => 'Seniorzy', 'junior' => 'Juniorzy', 'junior_mlodszy' => 'Kadeci (juniorzy młodsi)', 'mlodzik' => 'Młodzicy'];

    public int $step = 0;

    /** Ekran „gotowe” po zapisaniu. */
    public bool $done = false;

    /** @var array<string, string> */
    public array $values = [];

    /** @var TemporaryUploadedFile|null */
    public $logo = null;

    /** Kolory pochodzą z herbu (komunikat w kroku 2 i drugi ciemny kolor przy zapisie). */
    public bool $colorsFromLogo = false;

    /** @var list<array{name: string, category: string}> */
    public array $teams = [];

    /** @var list<string> pola, które ktoś zmienił ręcznie: podpowiedzi ich już nie nadpisują */
    public array $touched = [];

    public function boot(): void
    {
        abort_unless(auth()->user()?->can('system'), 403);
    }

    public function mount(SiteSettings $settings): void
    {
        foreach ([...self::NAME_KEYS, ...self::COLOR_KEYS, ...self::CONTACT_KEYS] as $key) {
            $this->values[$key] = (string) $settings->get($key);
        }

        $this->teams = [['name' => '', 'category' => 'senior']];
        $this->done = request()->boolean('gotowe');
    }

    public function updated(string $property): void
    {
        if (str_starts_with($property, 'values.')) {
            $key = substr($property, 7);
            $this->touched = array_values(array_unique([...$this->touched, $key]));

            match ($key) {
                'club_name' => $this->suggestFromName(),
                'club_short_name' => $this->suggestForms(),
                default => null,
            };
        }
    }

    /**
     * Po wgraniu herbu kolory dobierają się same (można je potem zmienić).
     */
    public function updatedLogo(): void
    {
        $this->validateOnly('logo', ['logo' => ['nullable', 'image', 'max:4096']], [], ['logo' => 'herb']);
        $this->colorsFromLogo = $this->applyLogoColors();
    }

    public function suggestColors(): void
    {
        $this->colorsFromLogo = $this->applyLogoColors();

        if (! $this->colorsFromLogo) {
            $this->toast('Z tego herbu nie da się odczytać kolorów (jest czarno-biały albo przezroczysty). Ustaw je ręcznie.', 'error');
        }
    }

    public function next(): void
    {
        $this->validate($this->rulesFor($this->step), $this->messages(), $this->attributeNames());
        $this->step = min($this->step + 1, count(self::STEPS));
    }

    public function again(): void
    {
        $this->done = false;
        $this->step = 0;
    }

    public function back(): void
    {
        $this->step = max(0, $this->step - 1);
    }

    public function goTo(int $step): void
    {
        // Do przodu tylko przez sprawdzone kroki, wstecz zawsze.
        foreach (range(0, max(0, min($step, count(self::STEPS)) - 1)) as $passed) {
            if ($passed >= $this->step) {
                $this->validate($this->rulesFor($passed), $this->messages(), $this->attributeNames());
            }
        }

        $this->step = max(0, min($step, count(self::STEPS)));
    }

    public function addTeam(?string $category = null): void
    {
        $category = array_key_exists((string) $category, self::CATEGORIES) ? $category : 'senior';
        $this->teams[] = ['name' => $category === 'senior' && $this->teams === [] ? '' : $this->teamNameFor($category), 'category' => $category];
    }

    public function removeTeam(int $index): void
    {
        unset($this->teams[$index]);
        $this->teams = array_values($this->teams);
    }

    public function finish(SiteSettings $settings): void
    {
        foreach (array_keys(self::STEPS) as $step) {
            try {
                $this->validate($this->rulesFor($step), $this->messages(), $this->attributeNames());
            } catch (ValidationException $exception) {
                $this->step = $step;

                throw $exception;
            }
        }

        $store = Arr::only($this->values, [...self::NAME_KEYS, ...self::COLOR_KEYS, ...self::CONTACT_KEYS]);
        $store = array_map(fn (string $value) => trim($value), $store);

        foreach (self::COLOR_KEYS as $key) {
            $store[$key] = strtolower($store[$key]);
        }

        foreach (self::COLOR_FOLLOWERS as $key => $source) {
            $store[$key] = $store[$source];
        }

        if ($this->colorsFromLogo && $this->logo) {
            $store['color_dark_alt'] = LogoColors::suggest($this->logo->getRealPath())['color_dark_alt'] ?? $settings->get('color_dark_alt');
        }

        if ($this->logo) {
            $store['logo'] = $this->logo->store('settings', 'public');
        }

        $settings->set($store);

        $existing = Team::query()->pluck('name')->map(fn (string $name) => mb_strtolower(trim($name)))->all();
        $order = (int) Team::query()->max('display_order');
        $created = 0;

        foreach ($this->filledTeams() as $team) {
            if (in_array(mb_strtolower($team['name']), $existing, true)) {
                continue;
            }

            Team::create([
                'name' => $team['name'],
                'slug' => Slug::unique(Team::class, $team['name']),
                'category' => $team['category'],
                'display_order' => ++$order,
            ]);
            $existing[] = mb_strtolower($team['name']);
            $created++;
        }

        SearchIndex::forget();

        session()->flash('success', $created > 0
            ? trans_choice('Gotowe. Klub jest ustawiony, dodano :count drużynę.|Gotowe. Klub jest ustawiony, dodano :count drużyny.|Gotowe. Klub jest ustawiony, dodano :count drużyn.', $created)
            : __('Gotowe. Klub jest ustawiony.'));

        $this->redirectRoute('admin.settings.setup', ['gotowe' => 1]);
    }

    /**
     * @return list<array{name: string, category: string}>
     */
    private function filledTeams(): array
    {
        return collect($this->teams)
            ->map(fn (array $team) => ['name' => trim((string) ($team['name'] ?? '')), 'category' => (string) ($team['category'] ?? 'senior')])
            ->filter(fn (array $team) => $team['name'] !== '')
            ->values()
            ->all();
    }

    private function teamNameFor(string $category): string
    {
        $short = trim($this->values['club_short_name'] ?? '');

        return trim($short.' '.match ($category) {
            'junior' => 'Juniorzy',
            'junior_mlodszy' => 'Kadeci',
            'mlodzik' => 'Młodzicy',
            default => 'Seniorzy',
        });
    }

    private function applyLogoColors(): bool
    {
        if (! $this->logo) {
            return false;
        }

        $suggested = LogoColors::suggest($this->logo->getRealPath());

        if ($suggested === null) {
            return false;
        }

        foreach (self::COLOR_KEYS as $key) {
            $this->values[$key] = $suggested[$key];
        }

        $this->touched = array_values(array_diff($this->touched, self::COLOR_KEYS));

        return true;
    }

    /**
     * Nazwa klubu podpowiada pełną nazwę, tytuł strony i opis dla Google (dopóki nikt ich nie zmienił).
     */
    private function suggestFromName(): void
    {
        $name = trim($this->values['club_name']);

        if ($name === '') {
            return;
        }

        $this->suggest('club_short_name', strtok($name, ' ') ?: $name);
        $this->suggest('club_legal_name', $name);
        $this->suggest('site_title', $name.' - Klub Siatkówki');
        $this->suggest('site_description', $name.' - klub siatkarski. Drużyny, terminarz, wyniki i aktualności.');
        $this->suggestForms();
    }

    private function suggestForms(): void
    {
        $forms = ClubNameForms::suggest($this->values['club_short_name']);
        $this->suggest('club_name_genitive', $forms['genitive']);
        $this->suggest('club_name_locative', $forms['locative']);
        $this->suggest('footer_button_label', 'Dołącz do '.$forms['genitive']);
    }

    private function suggest(string $key, string $value): void
    {
        if (! in_array($key, $this->touched, true)) {
            $this->values[$key] = $value;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesFor(int $step): array
    {
        $color = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return match (self::STEPS[$step] ?? null) {
            'klub' => [
                'values.club_name' => ['required', 'string', 'max:120'],
                'values.club_short_name' => ['required', 'string', 'max:40'],
                'values.club_name_genitive' => ['required', 'string', 'max:60'],
                'values.club_name_locative' => ['required', 'string', 'max:60'],
                'values.club_legal_name' => ['nullable', 'string', 'max:200'],
                'values.club_tagline' => ['nullable', 'string', 'max:120'],
            ],
            'wyglad' => [
                'logo' => ['nullable', 'image', 'max:4096'],
                ...collect(self::COLOR_KEYS)->mapWithKeys(fn (string $key) => ['values.'.$key => $color])->all(),
            ],
            'kontakt' => [
                'values.contact_email' => ['nullable', 'email', 'max:120'],
                'values.contact_phone' => ['nullable', 'string', 'max:40'],
                'values.contact_address' => ['nullable', 'string', 'max:200'],
                'values.footer_text' => ['nullable', 'string', 'max:200'],
                'values.footer_button_label' => ['nullable', 'string', 'max:60'],
                'values.contact_school_name' => ['nullable', 'string', 'max:200'],
                'values.site_title' => ['required', 'string', 'max:120'],
                'values.site_description' => ['nullable', 'string', 'max:300'],
            ],
            'druzyny' => [
                'teams' => ['array', 'max:30'],
                'teams.*.name' => ['nullable', 'string', 'max:255'],
                'teams.*.category' => ['required', 'in:'.implode(',', array_keys(self::CATEGORIES))],
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'required' => 'Uzupełnij pole „:attribute”.',
            'regex' => 'Kolor w polu „:attribute” wpisz jako #rrggbb, np. #45a0e0.',
            'email' => 'Wpisz poprawny adres e-mail.',
            'image' => 'Herb musi być obrazkiem (PNG, JPG, WebP).',
            'max' => 'Pole „:attribute” jest za długie.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function attributeNames(): array
    {
        $fields = SiteSettings::fields();

        return [
            ...collect([...self::NAME_KEYS, ...self::COLOR_KEYS, ...self::CONTACT_KEYS])
                ->mapWithKeys(fn (string $key) => ['values.'.$key => $this->label($key, $fields)])
                ->all(),
            'teams.*.name' => 'nazwa drużyny',
            'logo' => 'herb',
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $fields
     */
    private function label(string $key, array $fields): string
    {
        return match ($key) {
            'color_accent' => 'Kolor główny',
            'color_topbar_bg' => 'Pasek nad menu',
            'color_topbar_text' => 'Tekst na pasku',
            'color_dark' => 'Nagłówek i stopka',
            'color_club_navy' => 'Kolor ciemny klubu',
            'color_panel' => 'Przyciski w panelu',
            default => (string) ($fields[$key]['label'] ?? $key),
        };
    }

    public function render(SiteSettings $settings): View
    {
        return view('livewire.admin.setup-wizard', [
            'steps' => ['Klub', 'Herb i kolory', 'Kontakt', 'Drużyny'],
            'fields' => SiteSettings::fields(),
            'logoUrl' => $this->logo && ! $this->getErrorBag()->has('logo') ? $this->logo->temporaryUrl() : $settings->image('logo', asset('img/logo-256.webp')),
            'existingTeams' => Team::query()->orderBy('display_order')->get(['id', 'name', 'category']),
        ]);
    }
}
