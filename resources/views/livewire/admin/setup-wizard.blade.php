@php
    $hex = fn (string $key) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($values[$key] ?? '')) ? $values[$key] : '#888888';
    $total = count($steps);
@endphp
<div class="mx-auto max-w-3xl">
    @if($done)
        {{-- Po zapisaniu: co dalej --}}
        <div class="admin-card overflow-hidden text-center">
            <div class="px-6 pt-10 pb-8">
                <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="size-8" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                </div>
                <h2 class="mt-5 text-2xl font-bold text-zinc-900">Klub jest ustawiony</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-zinc-600">Nazwa, herb, kolory i kontakt są już na stronie. Resztę (menu, stronę główną, powiadomienia) zmienisz w każdej chwili w ustawieniach.</p>
            </div>
            <div class="grid gap-px border-t border-zinc-200 bg-zinc-200 sm:grid-cols-3">
                <a href="{{ route('home') }}" target="_blank" class="bg-white px-4 py-4 text-sm font-semibold text-sky-700 hover:bg-sky-50">Zobacz stronę ↗</a>
                <a href="{{ route('admin.teams.index') }}" class="bg-white px-4 py-4 text-sm font-semibold text-sky-700 hover:bg-sky-50">Uzupełnij drużyny</a>
                <a href="{{ route('admin.settings.index') }}" class="bg-white px-4 py-4 text-sm font-semibold text-sky-700 hover:bg-sky-50">Wszystkie ustawienia</a>
            </div>
        </div>
        <p class="mt-4 text-center"><button type="button" wire:click="again" class="text-sm font-semibold text-zinc-500 hover:text-zinc-800">Przejdź kreator jeszcze raz</button></p>
    @else
        {{-- Kroki: numer, nazwa i pasek postępu --}}
        <nav aria-label="Kroki kreatora" class="mb-5">
            <ol class="grid grid-cols-4 gap-2">
                @foreach($steps as $index => $label)
                    <li>
                        <button type="button" wire:click="goTo({{ $index }})" @if($index === $step) aria-current="step" @endif class="group w-full text-left">
                            <span @class([
                                'block h-1.5 rounded-full transition-colors duration-300',
                                'bg-sky-600' => $index <= $step,
                                'bg-zinc-200 group-hover:bg-zinc-300' => $index > $step,
                            ])></span>
                            <span @class([
                                'mt-2 flex items-center gap-1.5 text-xs font-semibold sm:text-sm',
                                'text-sky-700' => $index === $step,
                                'text-zinc-700' => $index < $step,
                                'text-zinc-400' => $index > $step,
                            ])>
                                <span class="tabular-nums">{{ $index + 1 }}.</span>
                                <span class="truncate">{{ $label }}</span>
                            </span>
                        </button>
                    </li>
                @endforeach
            </ol>
        </nav>

        <form wire:submit="{{ $step === $total - 1 ? 'finish' : 'next' }}" class="admin-card overflow-clip">
            <div class="space-y-6 p-4 sm:p-6" wire:key="setup-step-{{ $step }}">
                @if($errors->any())
                    <div class="rounded-lg bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
                @endif

                @switch($step)
                    @case(0)
                        <header>
                            <h2 class="text-lg font-bold text-zinc-900">Jak nazywa się klub?</h2>
                            <p class="mt-1 text-sm text-zinc-600">Nazwa pojawi się w nagłówku, stopce, kalendarzu meczów i w Google. Odmianę podpowiadamy sami, sprawdź ją tylko.</p>
                        </header>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Nazwa klubu</span>
                                <input type="text" wire:model.blur.live="values.club_name" placeholder="np. Orlik Gniezno" class="mt-1 w-full" autocomplete="organization">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Krótka nazwa</span>
                                <span class="block text-xs text-zinc-500">W wynikach: „Orlik – Kalisz 3:1”.</span>
                                <input type="text" wire:model.blur.live="values.club_short_name" class="mt-1 w-full">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Podpis pod nazwą</span>
                                <span class="block text-xs text-zinc-500">Np. „Klub siatkówki od 1995 roku”.</span>
                                <input type="text" wire:model.blur.live="values.club_tagline" class="mt-1 w-full">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Odmiana: kogo? czego?</span>
                                <span class="block text-xs text-zinc-500">„wychowankowie <strong class="font-semibold text-zinc-700">{{ $values['club_name_genitive'] ?: '…' }}</strong>”</span>
                                <input type="text" wire:model.blur.live="values.club_name_genitive" class="mt-1 w-full">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Odmiana: w kim? w czym?</span>
                                <span class="block text-xs text-zinc-500">„lata w <strong class="font-semibold text-zinc-700">{{ $values['club_name_locative'] ?: '…' }}</strong>”</span>
                                <input type="text" wire:model.blur.live="values.club_name_locative" class="mt-1 w-full">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Pełna nazwa (stowarzyszenie)</span>
                                <span class="block text-xs text-zinc-500">W zgodach RODO, np. „UKS Orlik Gniezno”.</span>
                                <input type="text" wire:model.blur.live="values.club_legal_name" class="mt-1 w-full">
                            </label>
                        </div>
                        @break

                    @case(1)
                        <header>
                            <h2 class="text-lg font-bold text-zinc-900">Herb i kolory</h2>
                            <p class="mt-1 text-sm text-zinc-600">Po wgraniu herbu kolory dobiorą się same. Możesz je potem zmienić, podgląd poniżej pokazuje efekt.</p>
                        </header>

                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                            <img src="{{ $logoUrl }}" alt="Herb" class="size-24 shrink-0 rounded-2xl bg-zinc-50 object-contain p-2 ring-1 ring-zinc-200">
                            <div class="min-w-0 flex-1 space-y-2">
                                <x-photo-cropper model="logo" :preview="false" aspect="1/1" format="png" :width="512" />
                                @if($logo)
                                    <button type="button" wire:click="suggestColors" class="admin-action">Dobierz kolory z herbu jeszcze raz</button>
                                @endif
                            </div>
                        </div>

                        @if($colorsFromLogo)
                            <p class="flex items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800">
                                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                Kolory dobrane z herbu.
                            </p>
                        @endif

                        {{-- Podgląd: górny pasek, nagłówek, przycisk i kafelek z wynikiem --}}
                        <div class="overflow-hidden rounded-2xl ring-1 ring-zinc-200" data-theme-keep aria-label="Podgląd strony">
                            <div class="flex gap-4 px-4 py-1.5 text-xs font-semibold" style="background: {{ $hex('color_topbar_bg') }}; color: {{ $hex('color_topbar_text') }}">
                                <span>Dla rodziców</span><span>Nabór</span><span class="hidden sm:inline">Galerie</span>
                            </div>
                            <div class="flex items-center gap-3 px-4 py-3" style="background: {{ $hex('color_dark') }}">
                                <img src="{{ $logoUrl }}" alt="" class="size-9 object-contain">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold tracking-wide text-white uppercase">{{ $values['club_name'] ?: 'Nazwa klubu' }}</p>
                                    <p class="truncate text-[11px] tracking-wide uppercase" style="color: {{ $hex('color_accent') }}">{{ $values['club_tagline'] }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 bg-zinc-50 px-4 py-4">
                                <span class="rounded-full px-4 py-2 text-sm font-semibold text-white" style="background: {{ $hex('color_accent') }}">Terminarz</span>
                                <span class="rounded-lg px-3 py-2 text-xs font-semibold text-white" style="background: {{ $hex('color_club_navy') }}">{{ $values['club_short_name'] ?: 'Klub' }} 3:1 Kalisz</span>
                                <span class="ml-auto rounded-md px-3 py-2 text-xs font-semibold text-white" style="background: {{ $hex('color_panel') }}">Przycisk w panelu</span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach(\App\Livewire\Admin\SetupWizard::COLOR_KEYS as $key)
                                <label class="flex items-center gap-3 rounded-xl p-2 ring-1 ring-zinc-200" wire:key="color-{{ $key }}">
                                    <input type="color" wire:model.live="values.{{ $key }}" class="h-10 w-12 shrink-0 cursor-pointer rounded-md border border-zinc-300 bg-white p-0.5">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-semibold text-zinc-900">{{ ['color_accent' => 'Kolor główny', 'color_topbar_bg' => 'Pasek nad menu', 'color_topbar_text' => 'Tekst na pasku', 'color_dark' => 'Nagłówek i stopka', 'color_club_navy' => 'Kolor ciemny klubu', 'color_panel' => 'Przyciski w panelu'][$key] }}</span>
                                        <input type="text" wire:model.live.debounce.500ms="values.{{ $key }}" maxlength="7" class="mt-0.5 h-8! min-h-0! w-28 py-0! font-mono text-xs uppercase" aria-label="Kod koloru">
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @break

                    @case(2)
                        <header>
                            <h2 class="text-lg font-bold text-zinc-900">Kontakt i stopka</h2>
                            <p class="mt-1 text-sm text-zinc-600">Pojawi się na stronie Kontakt i w stopce każdej strony. Puste pola po prostu się nie pokażą.</p>
                        </header>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">E-mail</span>
                                <input type="email" wire:model.blur.live="values.contact_email" class="mt-1 w-full" autocomplete="email">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Telefon</span>
                                <input type="tel" wire:model.blur.live="values.contact_phone" class="mt-1 w-full" autocomplete="tel">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Adres</span>
                                <span class="block text-xs text-zinc-500">Ulica, kod i miasto. Na stronie Kontakt pojawi się link do mapy.</span>
                                <input type="text" wire:model.blur.live="values.contact_address" class="mt-1 w-full" autocomplete="street-address">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Szkoła współpracująca z klubem</span>
                                <span class="block text-xs text-zinc-500">Puste pole = bez szkoły na stronie Kontakt.</span>
                                <input type="text" wire:model.blur.live="values.contact_school_name" class="mt-1 w-full">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Hasło w stopce</span>
                                <input type="text" wire:model.blur.live="values.footer_text" class="mt-1 w-full">
                            </label>
                            <label class="block sm:col-span-2">
                                <span class="text-sm font-semibold text-zinc-900">Przycisk w stopce</span>
                                <input type="text" wire:model.blur.live="values.footer_button_label" class="mt-1 w-full">
                            </label>
                        </div>
                        <fieldset class="space-y-4 rounded-xl bg-zinc-50 p-4">
                            <legend class="sr-only">Google</legend>
                            <p class="text-sm font-semibold text-zinc-900">Tak strona pokaże się w Google</p>
                            <div class="rounded-lg bg-white p-3 ring-1 ring-zinc-200" data-theme-keep>
                                <p class="truncate text-base text-[#1a0dab]">{{ $values['site_title'] ?: 'Tytuł strony' }}</p>
                                <p class="mt-0.5 line-clamp-2 text-xs text-zinc-600">{{ $values['site_description'] }}</p>
                            </div>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Tytuł</span>
                                <input type="text" wire:model.blur.live="values.site_title" class="mt-1 w-full">
                            </label>
                            <label class="block">
                                <span class="text-sm font-semibold text-zinc-900">Opis</span>
                                <textarea wire:model.blur.live="values.site_description" rows="3" class="mt-1 w-full"></textarea>
                            </label>
                        </fieldset>
                        @break

                    @case(3)
                        <header>
                            <h2 class="text-lg font-bold text-zinc-900">Drużyny</h2>
                            <p class="mt-1 text-sm text-zinc-600">Wpisz drużyny klubu. Trenerów, roczniki i zdjęcia dodasz potem w Drużynach. Ten krok możesz pominąć.</p>
                        </header>

                        @if($existingTeams->isNotEmpty())
                            <div class="rounded-xl bg-zinc-50 p-3">
                                <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Już są na stronie</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($existingTeams as $team)
                                        <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 ring-1 ring-zinc-200">{{ $team->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="space-y-2">
                            @foreach($teams as $index => $team)
                                <div class="flex items-center gap-2" wire:key="team-row-{{ $index }}">
                                    <input type="text" wire:model.blur="teams.{{ $index }}.name" placeholder="Nazwa, np. {{ ($values['club_short_name'] ?: 'Orlik') }} Seniorzy" class="min-w-0 flex-1" aria-label="Nazwa drużyny {{ $index + 1 }}">
                                    <select wire:model="teams.{{ $index }}.category" class="w-32 shrink-0 sm:w-48" aria-label="Kategoria drużyny {{ $index + 1 }}">
                                        @foreach(\App\Livewire\Admin\SetupWizard::CATEGORIES as $categoryKey => $categoryLabel)
                                            <option value="{{ $categoryKey }}">{{ $categoryLabel }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" wire:click="removeTeam({{ $index }})" class="flex size-10 shrink-0 items-center justify-center rounded-lg text-zinc-400 hover:bg-red-50 hover:text-red-600" aria-label="Usuń wiersz {{ $index + 1 }}">
                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                                    </button>
                                </div>
                                @error('teams.'.$index.'.name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                            @endforeach
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach(\App\Livewire\Admin\SetupWizard::CATEGORIES as $categoryKey => $categoryLabel)
                                <button type="button" wire:click="addTeam('{{ $categoryKey }}')" class="admin-action">+ {{ \Illuminate\Support\Str::before($categoryLabel, ' (') }}</button>
                            @endforeach
                        </div>
                        @break
                @endswitch
            </div>

            {{-- Przyciski: na telefonie przyklejone nad dolnym menu --}}
            <div class="sticky bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-10 flex items-center gap-3 border-t border-zinc-200 bg-white/95 px-4 py-3 backdrop-blur sm:static sm:px-6">
                @if($step > 0)
                    <button type="button" wire:click="back" class="admin-action">← Wstecz</button>
                @endif
                <span class="hidden text-xs text-zinc-500 tabular-nums sm:inline">Krok {{ $step + 1 }} z {{ $total }}</span>
                <button type="submit" class="ml-auto inline-flex min-h-11 items-center rounded-xl whitespace-nowrap bg-sky-600 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                    @if($step === $total - 1)
                        <span wire:loading.remove wire:target="finish">Zapisz i zakończ</span>
                        <span wire:loading wire:target="finish">Zapisywanie…</span>
                    @else
                        Dalej →
                    @endif
                </button>
            </div>
        </form>
        <p class="mt-3 text-center text-xs text-zinc-500">Nic nie zmienia się na stronie, dopóki nie klikniesz „Zapisz i zakończ”.</p>
    @endif
</div>
