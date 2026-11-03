@php
    $input = 'w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm';
    $label = 'block text-sm font-medium text-gray-700 mb-1';
    $primary = 'inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700';
    $secondary = 'inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
    $extraHalls = \App\Support\TournamentHalls::extra($tournament);
    $mainVenues = \App\Support\TournamentHalls::mainVenues($tournament);
    $sections = ['strona' => 'Strona i informator', 'kategorie' => 'Kategorie', 'druzyny' => 'Drużyny', 'grupy' => 'Grupy', 'mecze' => 'Mecze i wyniki', 'wyroznienia' => 'Wyróżnienia', 'partnerzy' => 'Partnerzy turnieju', 'wpisujacy' => 'Wpisujący wyniki'];
@endphp

<div class="space-y-6">

    {{-- Sekcje: na telefonie jeden przewijany w bok pasek przyklejony pod nagłówkiem panelu --}}
    <div class="sticky top-[calc(4rem+env(safe-area-inset-top))] z-20 -mx-4 border-b border-gray-200 bg-zinc-50/95 px-4 py-2 backdrop-blur sm:static sm:mx-0 sm:bg-transparent sm:px-0 sm:pt-0 sm:pb-3 sm:backdrop-blur-none">
        <div class="flex items-center gap-2">
            <div data-scroll-fade x-data x-init="const active = $el.querySelector('[aria-current]'); if (active) $el.scrollLeft += active.getBoundingClientRect().left - $el.getBoundingClientRect().left - ($el.clientWidth - active.offsetWidth) / 2" class="-my-1 flex min-w-0 flex-1 gap-2 overflow-x-auto py-1 whitespace-nowrap [scrollbar-width:none] sm:flex-wrap sm:overflow-visible sm:whitespace-normal [&::-webkit-scrollbar]:hidden">
                @foreach($sections as $key => $name)
                    <button type="button" wire:click="$set('section', '{{ $key }}')" @if($section === $key) aria-current="page" @endif
                        class="min-h-10 shrink-0 rounded-full px-4 py-1.5 text-sm font-semibold sm:min-h-0 {{ $section === $key ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                        {{ $name }}
                    </button>
                @endforeach
            </div>
            @if($tournament->has_page)
                <div class="hidden shrink-0 gap-3 sm:flex">
                    <a href="{{ route('tournaments.tv', $tournament) }}" target="_blank" class="text-sm font-semibold text-sky-700 hover:underline" title="Otwórz na komputerze podłączonym do telewizora i naciśnij F11 (pełny ekran)">Ekran TV ↗</a>
                    <a href="{{ route('tournaments.show', $tournament) }}" target="_blank" class="text-sm font-semibold text-sky-700 hover:underline">Zobacz stronę ↗</a>
                </div>
            @endif
        </div>
    </div>
    @if($tournament->has_page)
        <div class="-mt-3 flex gap-2 sm:hidden">
            <a href="{{ route('tournaments.show', $tournament) }}" target="_blank" class="admin-action">Zobacz stronę ↗</a>
            <a href="{{ route('tournaments.tv', $tournament) }}" target="_blank" class="admin-action">Ekran TV ↗</a>
        </div>
    @endif

    @if(! in_array($section, ['strona', 'kategorie', 'wpisujacy', 'partnerzy'], true))
        <div data-scroll-fade class="flex items-center gap-3 overflow-x-auto rounded-lg bg-gray-50 p-3 whitespace-nowrap [scrollbar-width:none] sm:flex-wrap sm:whitespace-normal [&::-webkit-scrollbar]:hidden">
            <span class="shrink-0 text-sm font-medium text-gray-700">Kategoria:</span>
            @forelse($categories as $cat)
                <button type="button" wire:click="$set('categoryId', {{ $cat->id }})"
                    class="min-h-10 shrink-0 rounded-md px-3 py-1 text-sm font-semibold sm:min-h-0 {{ $categoryId === $cat->id ? 'bg-sky-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300' }}">
                    {{ $cat->name }}
                </button>
            @empty
                <span class="text-sm text-gray-500">Najpierw dodaj kategorię wiekową w zakładce „Kategorie”.</span>
            @endforelse
            @if($section === 'mecze' && $category)
                {{-- Rozpiska dla gości wybranej kategorii: kartka A4 z logo, meczami i adresami hal (druk albo PDF) --}}
                <a href="{{ route('admin.tournaments.schedule-sheet', [$tournament, 'category' => $category->id]) }}" target="_blank" class="ml-auto inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-md bg-white px-3 py-1 sm:min-h-0 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                    <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 2.75C5 1.784 5.784 1 6.75 1h6.5c.966 0 1.75.784 1.75 1.75v3.552c.377.046.752.097 1.126.153A2.212 2.212 0 0 1 18 8.653v4.097A2.25 2.25 0 0 1 15.75 15h-.241l.305 1.984A1.75 1.75 0 0 1 14.084 19H5.915a1.75 1.75 0 0 1-1.73-2.016L4.492 15H4.25A2.25 2.25 0 0 1 2 12.75V8.653c0-1.082.775-2.034 1.874-2.198.374-.056.75-.107 1.127-.153L5 6.25v-3.5Zm8.5 3.397a41.533 41.533 0 0 0-7 0V2.75a.25.25 0 0 1 .25-.25h6.5a.25.25 0 0 1 .25.25v3.397ZM6.608 12.5a.25.25 0 0 0-.247.212l-.693 4.5a.25.25 0 0 0 .247.288h8.17a.25.25 0 0 0 .246-.288l-.692-4.5a.25.25 0 0 0-.247-.212H6.608Z" clip-rule="evenodd"/></svg>
                    Rozpiska dla gości
                </a>
            @endif
        </div>
    @endif

    {{-- Strona i informator --}}
    @if($section === 'strona')
        <div class="space-y-5">
            <p class="text-xs text-gray-500">Zmiany zapisują się same po wyjściu z pola.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                    <input type="checkbox" wire:model.live="has_page" class="rounded border-gray-300 text-sky-700">
                    <span class="text-sm"><strong>Publiczna strona turnieju</strong><br><span class="text-gray-500">Zakładki: drużyny, grupy, mecze, drabinka, klasyfikacja, zasady, informator.</span></span>
                </label>
                <div>
                    <label class="{{ $label }}">Cykl</label>
                    <select wire:model.live="series" class="{{ $input }}">
                        <option value="">— pojedynczy turniej —</option>
                        @if($site['featured_tournament_name'] || $series === \App\Models\Tournament::SERIES_MEMORIAL)
                            <option value="{{ \App\Models\Tournament::SERIES_MEMORIAL }}">{{ $site['featured_tournament_name'] ?: 'Cykliczny turniej klubu' }} (adres /memorial)</option>
                        @endif
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Adres /memorial otwiera najnowszą edycję z cyklu, starsze trafiają do archiwum. Nazwę cyklu ustawisz w System → Moduły.</p>
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Zdjęcie w nagłówku strony</label>
                @if($tournament->image_path)
                    <img src="{{ \App\Support\Media::url($tournament->image_path) }}" alt="" class="mb-2 h-24 rounded-md object-cover">
                @endif
                <x-photo-cropper model="image" :preview="false" aspect="16/9" :width="1920" />
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="{{ $label }}">Logo turnieju <span class="font-normal text-gray-400">(opcjonalnie, np. na miniatury transmisji)</span></label>
                @if($tournament->logo_path)
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ \App\Support\Media::url($tournament->logo_path) }}" alt="" class="h-20 w-20 rounded-md bg-gray-100 object-contain p-1">
                        <button type="button" wire:click="removeLogo" wire:confirm="Usunąć logo turnieju?" class="text-sm text-red-600 hover:underline">Usuń</button>
                    </div>
                @endif
                <x-photo-cropper model="logo" :preview="false" aspect="free" format="png" :width="800" :label="$tournament->logo_path ? 'Zmień logo' : 'Wybierz logo'" />
                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg border border-gray-200 p-4">
                @php $scoringFields = ['win' => 'Wygrana', 'win_tiebreak' => 'Wygrana w tie-breaku', 'loss_tiebreak' => 'Porażka w tie-breaku', 'loss' => 'Porażka', 'per_set' => 'Za każdy wygrany set']; @endphp
                <p class="text-sm font-semibold text-gray-900">Punktacja meczów grupowych (domyślna dla wszystkich grup)</p>
                <p class="text-xs text-gray-500">Tie-break to mecz rozstrzygnięty w ostatnim secie (2:1, 3:2). Grupa może mieć własną punktację w zakładce „Grupy”.</p>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach($scoringFields as $field => $fieldName)
                        <label class="text-sm text-gray-700">{{ $fieldName }}
                            <input type="number" min="0" max="20" wire:model.blur.live="scoring.{{ $field }}" class="{{ $input }} mt-1">
                        </label>
                    @endforeach
                </div>
                @error('scoring.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div class="mt-4 max-w-md">
                    @include('livewire.admin.tournament-criteria', ['target' => 'tournament', 'criteria' => $scoring['criteria'] ?? []])
                </div>
                <p class="mt-2 text-xs text-gray-500">„Mecze bezpośrednie” liczą małą tabelę tylko z meczów między drużynami równymi na wcześniejszych kryteriach. Ostateczną kolejność możesz też ustawić ręcznie strzałkami przy tabeli w zakładce „Grupy”.</p>
            </div>

            <div>
                <label class="{{ $label }}">O turnieju</label>
                <p class="mb-1 text-xs text-gray-500">Pierwsza zakładka na stronie turnieju: historia, idea, patron, organizatorzy. Puste = zakładki nie ma.</p>
                <x-rich-text-editor model="about" save-on-blur placeholder="Kilka słów o turnieju: historia, patron, organizatorzy..." height="min-h-[180px]" />
            </div>

            <div>
                <label class="{{ $label }}">Zasady i regulamin</label>
                <x-rich-text-editor model="rules" save-on-blur placeholder="System rozgrywek, punktacja, czas rozgrzewki, piłki..." height="min-h-[180px]" />
            </div>

            <div>
                <label class="{{ $label }}">Informator dla drużyn przyjezdnych i kibiców</label>
                <x-rich-text-editor model="guest_info" save-on-blur placeholder="Hale i adresy, dojazd i parking, noclegi, wyżywienie, kontakt do organizatora, plan dnia..." height="min-h-[220px]" />
            </div>

        </div>
    @endif

    {{-- Kategorie --}}
    @if($section === 'kategorie')
        <div class="space-y-4">
            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200">
                @forelse($categories as $cat)
                    <li class="flex flex-wrap items-center gap-3 px-4 py-3" wire:key="cat-{{ $cat->id }}">
                        <input type="color" value="{{ $cat->color() }}" wire:change="setCategoryColor({{ $cat->id }}, $event.target.value)" class="h-7 w-9 cursor-pointer rounded border border-gray-300 p-0.5" title="{{ $cat->ageGroup() ? 'Kolor wspólny z planem treningów: '.\App\Support\AgeColors::GROUPS[$cat->ageGroup()][0] : 'Kolor kategorii w obłożeniu sektorów' }}">
                        @if($renaming === 'category:'.$cat->id)
                            <input type="text" wire:model.blur.live="categoryNames.{{ $cat->id }}" x-init="$el.focus()" x-on:keydown.enter="$el.blur()" x-on:blur="$wire.set('renaming', '')" class="{{ $input }} max-w-xs">
                        @else
                            <span class="text-sm"><strong>{{ $cat->name }}</strong> <span class="text-gray-500">· drużyn: {{ $cat->participants_count }} · meczów: {{ $cat->matches_count }}</span></span>
                        @endif
                        @error('categoryNames.'.$cat->id) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        <span class="ml-auto flex gap-3">
                        <button type="button" wire:click="startTemplate({{ $cat->id }})" class="text-sm text-sky-700 hover:text-blue-800" title="Zapisz grupy, mecze, drabinkę i dni do użycia w kolejnych turniejach">Zapisz jako szablon</button>
                        <button type="button" wire:click="$set('renaming', 'category:{{ $cat->id }}')" class="admin-action">Edytuj</button>
                        <button type="button" wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Usunąć kategorię {{ $cat->name }} razem z drużynami, grupami i meczami?" class="admin-action admin-action-danger">Usuń</button>
                        </span>
                    </li>
                    @if($templateFrom === $cat->id)
                        <li class="bg-sky-50 px-4 py-3">
                            <form wire:submit="saveTemplate" class="grid gap-3 sm:grid-cols-[1fr_2fr_auto_auto] sm:items-end">
                                <div>
                                    <label class="{{ $label }}">Nazwa szablonu</label>
                                    <input type="text" wire:model="templateName" class="{{ $input }}" placeholder="np. 8 drużyn: 2 grupy + półfinały">
                                </div>
                                <div>
                                    <label class="{{ $label }}">Krótki opis</label>
                                    <input type="text" wire:model="templateDescription" class="{{ $input }}" placeholder="np. 2 grupy po 4, krzyżowe półfinały, mecze o miejsca 1–8, 2 dni">
                                </div>
                                <button type="submit" class="{{ $primary }}">Zapisz szablon</button>
                                <button type="button" wire:click="cancelTemplate" class="text-sm text-gray-600 hover:text-gray-900">Anuluj</button>
                                <label class="flex items-center gap-2 text-sm text-gray-800 sm:col-span-4">
                                    <input type="checkbox" wire:model="templateTeams" class="rounded border-gray-300 text-sky-600">
                                    Zapisz też drużyny (stały skład, np. co roku te same zespoły)
                                </label>
                                <p class="text-xs text-gray-600 sm:col-span-4">Szablon zapamięta grupy z sektorami i punktacją, wszystkie mecze z numerami, godzinami i sektorami, drabinkę, mecze o miejsca oraz dni i odstęp między meczami. Daty liczą się od pierwszego dnia turnieju, więc szablon pasuje do turnieju w innym terminie. Wyników nie zapisuje.</p>
                                @error('templateName') <p class="text-sm text-red-600 sm:col-span-4">{{ $message }}</p> @enderror
                            </form>
                        </li>
                    @endif
                @empty
                    <li class="px-4 py-3 text-sm text-gray-500">Brak kategorii. Dodaj np. „Kadetki”, „Juniorki młodsze”, „Młodziczki”.</li>
                @endforelse
            </ul>
            <form wire:submit="addCategory" class="flex flex-wrap gap-2">
                <input type="text" wire:model="newCategory" placeholder="Nazwa kategorii wiekowej" class="{{ $input }} max-w-sm">
                @if($templates->isNotEmpty())
                    <select wire:model="newCategoryTemplate" class="{{ $input }} max-w-sm">
                        <option value="">pusta, bez szablonu</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">z szablonu: {{ $template->name }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="{{ $primary }}">Dodaj kategorię</button>
            </form>
            @error('newCategory') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

            @if($templates->isNotEmpty())
                <div>
                    <h3 class="mt-4 font-semibold text-gray-900">Szablony turniejów</h3>
                    <p class="text-xs text-gray-500">Wybierz szablon przy dodawaniu kategorii. Potem dodaj drużyny i przypisz je do grup: pierwsza drużyna grupy na liście zajmie pozycję 1 w meczach, druga pozycję 2 itd.</p>
                    <ul class="mt-2 divide-y divide-gray-200 rounded-lg border border-gray-200">
                        @foreach($templates as $template)
                            <li class="flex flex-wrap items-start gap-3 px-4 py-3" wire:key="template-{{ $template->id }}">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ $template->name }} <span class="font-normal text-gray-500">· {{ $template->summary() }}</span></p>
                                    @if($template->description)
                                        <p class="text-sm text-gray-600">{{ $template->description }}</p>
                                    @endif
                                </div>
                                <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Usunąć szablon {{ $template->name }}? Turnieje już z niego zrobione zostaną bez zmian." class="admin-action admin-action-danger">Usuń</button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- Drużyny --}}
    @if($section === 'druzyny' && $category)
        <div class="space-y-5">
            <form wire:submit="addParticipant" class="grid gap-3 rounded-lg border border-dashed border-gray-300 p-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
                <div>
                    <label class="{{ $label }}">Drużyna z listy</label>
                    <x-search-select model="newParticipant" empty="— nowa drużyna (wpisz nazwę obok) —" placeholder="Szukaj drużyny lub klubu…" :options="$teamOptions->map(fn ($team) => ['value' => 'team:'.$team->id, 'label' => $site['club_short_name'].': '.$team->name, 'group' => 'Nasze drużyny'])
                        ->concat($rivalOptions->sortBy(fn ($rival) => ($rival->club?->name ?? '~').' '.$rival->full_name)->map(fn ($rival) => ['value' => 'rival:'.$rival->id, 'label' => $rival->full_name, 'group' => $rival->club?->name ?? 'Rywale bez klubu']))->all()" />
                </div>
                <div>
                    <label class="{{ $label }}">albo nazwa nowej drużyny</label>
                    <input type="text" wire:model="newParticipantName" class="{{ $input }}" placeholder="np. UKS Dwójka Kalisz">
                </div>
                <div>
                    <label class="{{ $label }}">Logo</label>
                    <x-photo-cropper model="newParticipantLogo" :preview="false" aspect="free" format="png" :width="400" label="Wybierz logo" hint="" />
                </div>
                <button type="submit" class="{{ $primary }}">Dodaj</button>
                @error('newParticipantName') <p class="text-sm text-red-600 sm:col-span-4">{{ $message }}</p> @enderror
                @error('newParticipantLogo') <p class="text-sm text-red-600 sm:col-span-4">{{ $message }}</p> @enderror
            </form>

            @if($category->participants->isNotEmpty())
                <div class="space-y-3">
                    @foreach($category->participants as $participant)
                        <div class="grid gap-3 rounded-lg border border-gray-200 p-3 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_8rem_6rem_minmax(0,1.5fr)_auto] lg:items-start" wire:key="participant-{{ $participant->id }}">
                            <div class="flex items-center gap-3">
                                @if($participant->logoPath())
                                    <img src="{{ \App\Support\Media::url($participant->logoPath()) }}" alt="" class="h-10 w-10 object-contain">
                                @else
                                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500">{{ mb_substr($participant->name(), 0, 2) }}</span>
                                @endif
                                <span class="text-sm font-semibold text-gray-900">{{ $participant->name() }}</span>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Nazwa na tym turnieju</label>
                                <input type="text" wire:model.blur.live="participants.{{ $participant->id }}.display_name" class="{{ $input }}" placeholder="opcjonalnie">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Grupa</label>
                                <select wire:model.live="participants.{{ $participant->id }}.group" class="{{ $input }}">
                                    <option value="">—</option>
                                    @foreach($category->groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Miejsce</label>
                                <input type="number" min="1" wire:model.blur.live="participants.{{ $participant->id }}.final_position" class="{{ $input }}" placeholder="auto">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Skład (jedna osoba w linii)</label>
                                <textarea rows="2" wire:model.blur.live="participants.{{ $participant->id }}.roster" class="{{ $input }}" placeholder="opcjonalnie"></textarea>
                            </div>
                            <div class="flex gap-3 text-sm lg:mt-6 lg:flex-col lg:gap-1">
                                <button type="button" wire:click="startSwap({{ $participant->id }})" class="text-sky-700 hover:text-sky-900">Zamień</button>
                                <button type="button" wire:click="deleteParticipant({{ $participant->id }})" wire:confirm="Usunąć {{ $participant->name() }} z turnieju?" class="text-red-600 hover:text-red-800">Usuń</button>
                            </div>
                            @if($swapParticipant === $participant->id)
                                {{-- Zamiana drużyny na tej samej pozycji: mecze, grupa i terminarz zostają --}}
                                <form wire:submit="swapTeam" class="grid gap-3 rounded-lg bg-sky-50 p-3 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end lg:col-span-full">
                                    <div>
                                        <label class="{{ $label }}">Zamień {{ $participant->name() }} na</label>
                                        <x-search-select model="swapWith" empty="— nowa drużyna (wpisz nazwę obok) —" placeholder="Szukaj drużyny lub klubu…" :options="$teamOptions->map(fn ($team) => ['value' => 'team:'.$team->id, 'label' => $site['club_short_name'].': '.$team->name, 'group' => 'Nasze drużyny'])
                                            ->concat($rivalOptions->sortBy(fn ($rival) => ($rival->club?->name ?? '~').' '.$rival->full_name)->map(fn ($rival) => ['value' => 'rival:'.$rival->id, 'label' => $rival->full_name, 'group' => $rival->club?->name ?? 'Rywale bez klubu']))->all()" />
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">albo nazwa nowej drużyny</label>
                                        <input type="text" wire:model="swapName" class="{{ $input }}" placeholder="np. UKS Dwójka Kalisz">
                                    </div>
                                    <button type="submit" class="{{ $primary }}" wire:confirm="Zamienić drużynę? Grupa, mecze, godziny i wpisane wyniki zostaną przy nowej drużynie.">Zamień</button>
                                    <button type="button" wire:click="cancelSwap" class="text-sm text-gray-600 hover:text-gray-900">Anuluj</button>
                                    <p class="text-xs text-gray-600 sm:col-span-4">Nowa drużyna wchodzi na to samo miejsce: zostaje grupa, wszystkie mecze z godzinami i sektorami oraz drabinka. Skład i „nazwa na tym turnieju” czyszczą się, bo dotyczyły poprzedniej drużyny.</p>
                                    @error('swapName') <p class="text-sm text-red-600 sm:col-span-4">{{ $message }}</p> @enderror
                                </form>
                            @endif
                        </div>
                    @endforeach
                    <p class="text-xs text-gray-500">Zmiany zapisują się same. „Miejsce” zostaw puste, a końcowa klasyfikacja wyliczy się z meczów o miejsca, finału i reguł grup.</p>
                </div>
            @endif
        </div>
    @endif

    {{-- Grupy: skład z drużyn albo reguł, reguły miejsc w klasyfikacji; wszystko zapisuje się samo --}}
    @if($section === 'grupy' && $category)
        @php $groupBracket = \App\Support\TournamentBracket::for($category); @endphp
        <div class="space-y-4">
            <p class="text-xs text-gray-500">Skład grupy to drużyny przypisane w zakładce „Drużyny” oraz miejsca dodane tutaj: konkretna drużyna albo reguła, np. „1. miejsce – Grupa A” (grupa finałowa). Zmiany zapisują się same.</p>
            <div class="grid gap-4 lg:grid-cols-2">
                @forelse($category->groups as $group)
                    @php $members = $groupBracket->members($group); $slotOptions = \App\Support\TournamentBracket::slotOptions($category, $group); @endphp
                    <div class="space-y-3 rounded-lg border border-gray-200 p-4" wire:key="group-{{ $group->id }}">
                        <div class="flex items-center gap-3">
                            @if($renaming === 'group:'.$group->id)
                                <input type="text" wire:model.blur.live="groupNames.{{ $group->id }}" x-init="$el.focus()" x-on:keydown.enter="$el.blur()" x-on:blur="$wire.set('renaming', '')" class="{{ $input }} max-w-xs">
                            @else
                                <h3 class="font-semibold text-gray-900">{{ $group->name }}</h3>
                            @endif
                            @error('groupNames.'.$group->id) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            <span class="ml-auto flex gap-3">
                                <button type="button" wire:click="$set('renaming', 'group:{{ $group->id }}')" class="admin-action">Edytuj</button>
                                <button type="button" wire:click="deleteGroup({{ $group->id }})" wire:confirm="Usunąć {{ $group->name }}? Drużyny zostaną bez grupy, a mecze grupy zostaną usunięte." class="admin-action admin-action-danger">Usuń</button>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase">Domyślne boisko grupy</p>
                            <x-court-picker :halls="$extraHalls" :main="$mainVenues" model="groupCourts.{{ $group->id }}" :value="$groupCourts[$group->id] ?? null" :free="false" />
                            @unless(filled($groupCourts[$group->id] ?? null))<span class="text-xs text-gray-500">brak: przy układaniu godzin mecze trafią na wolne sektory</span>@endunless
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-gray-500 uppercase">Skład ({{ $members->count() }})</p>
                            <ul class="mt-1 space-y-1 text-sm text-gray-700">
                                @foreach($group->participants as $participant)
                                    <li>{{ $participant->name() }} <span class="text-xs text-gray-400">· z zakładki „Drużyny”</span></li>
                                @endforeach
                            </ul>
                            <div class="mt-2 space-y-2">
                                @foreach($groupSlots[$group->id] ?? [] as $index => $slot)
                                    <div class="flex items-center gap-2" wire:key="slot-{{ $group->id }}-{{ $index }}">
                                        <select wire:model.live="groupSlots.{{ $group->id }}.{{ $index }}" class="{{ $input }}">
                                            <option value="">— wybierz drużynę albo regułę —</option>
                                            @foreach(collect($slotOptions)->groupBy(fn ($name, $key) => match (true) { str_starts_with($key, 'participant:') => 'Drużyny', str_starts_with($key, 'group:') => 'Miejsce w innej grupie', default => 'Wynik meczu' }, true) as $optgroup => $options)
                                                <optgroup label="{{ $optgroup }}">
                                                    @foreach($options as $value => $name)
                                                        <option value="{{ $value }}">{{ $name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                        <button type="button" wire:click="removeGroupSlot({{ $group->id }}, {{ $index }})" class="text-sm text-red-600 hover:text-red-800" title="Usuń miejsce">✕</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" wire:click="addGroupSlot({{ $group->id }})" class="mt-2 text-sm font-semibold text-sky-700 hover:text-blue-800">+ Dodaj miejsce w składzie</button>
                        </div>

                        @if($members->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase">Reguły grupy: miejsce w tabeli → klasyfikacja końcowa</p>
                                <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-2 xl:grid-cols-3">
                                    @foreach(range(1, $members->count()) as $position)
                                        <label class="flex items-center gap-2 text-sm whitespace-nowrap text-gray-700">
                                            <span class="shrink-0">{{ $position }}. miejsce →</span>
                                            <input type="number" min="1" wire:model.blur.live="groupPlaces.{{ $group->id }}.{{ $position }}" class="{{ $input }} !w-16" placeholder="—">
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Np. w grupie finałowej 1 → 1, 2 → 2… Puste pole = to miejsce nie trafia do klasyfikacji (rozstrzygają dalsze mecze).</p>
                            </div>
                        @endif

                        <div>
                            @php $scoringFields = ['win' => 'Wygrana', 'win_tiebreak' => 'Wygrana w tie-breaku', 'loss_tiebreak' => 'Porażka w tie-breaku', 'loss' => 'Porażka', 'per_set' => 'Za każdy wygrany set']; @endphp
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.live="groupScoring.{{ $group->id }}.custom" class="rounded border-gray-300 text-sky-700">
                                Własna punktacja grupy <span class="text-xs text-gray-500">(inaczej obowiązuje punktacja turnieju)</span>
                            </label>
                            @if(! empty($groupScoring[$group->id]['custom']))
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach($scoringFields as $field => $fieldName)
                                        <label class="text-xs text-gray-600">{{ $fieldName }}
                                            <input type="number" min="0" max="20" wire:model.blur.live="groupScoring.{{ $group->id }}.{{ $field }}" class="{{ $input }} mt-1">
                                        </label>
                                    @endforeach
                                </div>
                                <div class="mt-3">
                                    @include('livewire.admin.tournament-criteria', ['target' => (string) $group->id, 'criteria' => $groupScoring[$group->id]['criteria'] ?? []])
                                </div>
                            @endif
                        </div>

                        @php $table = $groupBracket->groupTable($group); @endphp
                        @if($table->isNotEmpty())
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-semibold text-gray-500 uppercase">Tabela {{ $table->first()['manual'] ? '· kolejność ustawiona ręcznie' : '' }}</p>
                                    @if($table->first()['manual'])
                                        <button type="button" wire:click="resetTableOrder({{ $group->id }})" class="ml-auto text-xs font-semibold text-sky-700 hover:text-blue-800">Przywróć kolejność według kryteriów</button>
                                    @endif
                                </div>
                                <ol class="mt-1 divide-y divide-gray-100 rounded-md border border-gray-200 text-sm">
                                    @foreach($table as $row)
                                        <li class="flex items-center gap-2 px-2 py-1" wire:key="row-{{ $group->id }}-{{ $row['participant']->id }}">
                                            <span class="w-5 font-bold text-gray-400">{{ $row['position'] }}.</span>
                                            <span class="flex-1 truncate">{{ $row['participant']->name() }}</span>
                                            <span class="text-xs text-gray-500 tabular-nums">{{ $row['points'] }} pkt · {{ $row['sets_won'] }}:{{ $row['sets_lost'] }}</span>
                                            <button type="button" wire:click="moveTableRow({{ $group->id }}, {{ $row['participant']->id }}, -1)" @disabled($loop->first) class="px-1 text-gray-500 hover:text-gray-900 disabled:opacity-30" title="W górę">↑</button>
                                            <button type="button" wire:click="moveTableRow({{ $group->id }}, {{ $row['participant']->id }}, 1)" @disabled($loop->last) class="px-1 text-gray-500 hover:text-gray-900 disabled:opacity-30" title="W dół">↓</button>
                                        </li>
                                    @endforeach
                                </ol>
                                <p class="mt-1 text-xs text-gray-500">Strzałki ustawiają ostateczną kolejność ręcznie, np. po rozpisaniu małej tabeli. Kolejne mecze i klasyfikacja biorą ją pod uwagę.</p>
                            </div>
                        @endif

                        <div class="flex items-center gap-3">
                            <p class="text-xs text-gray-500">Meczów w grupie: {{ $category->matches->where('tournament_group_id', $group->id)->count() }}</p>
                            @if($members->count() > 1)
                                <button type="button" wire:click="generateGroupMatches({{ $group->id }})" class="{{ $secondary }} ml-auto">Utwórz mecze „każdy z każdym”</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Brak grup w tej kategorii.</p>
                @endforelse
            </div>
            <form wire:submit="addGroup" class="flex gap-2">
                <input type="text" wire:model="newGroup" placeholder="np. Grupa A, Grupa finałowa" class="{{ $input }} max-w-xs">
                <button type="submit" class="{{ $primary }}">Dodaj grupę</button>
            </form>
            @error('newGroup') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- Mecze i wyniki --}}
    @if($section === 'partnerzy')
        <livewire:admin.tournament-partners :tournament-id="$tournament->id" :key="'partners-'.$tournament->id" />
    @endif

    @if($section === 'wpisujacy')
        <livewire:admin.tournament-scorers :tournament-id="$tournament->id" :key="'scorers-'.$tournament->id" />
    @endif

    @if($section === 'wyroznienia' && $category)
        <livewire:admin.tournament-awards :category-id="$category->id" :key="'awards-'.$category->id" />
    @endif

    @if($section === 'mecze' && $category)
        <div class="space-y-6">
            {{-- Dodawanie meczu i harmonogram obok siebie (na telefonie jeden pod drugim) --}}
            <div class="grid items-start gap-6 xl:grid-cols-2">
                <form wire:submit="addMatch" class="space-y-3 rounded-lg border border-dashed border-gray-300 p-4">
                    <h3 class="font-semibold text-gray-900">Dodaj mecz</h3>
                    <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5 xl:grid-cols-3">
                        <div>
                            <label class="{{ $label }}">Etap</label>
                            <select wire:model.live="match.stage" class="{{ $input }}">
                                @foreach(\App\Models\TournamentMatch::STAGES as $value => $name)
                                    <option value="{{ $value }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if($match['stage'] === 'group')
                            <div>
                                <label class="{{ $label }}">Grupa</label>
                                <select wire:model="match.group" class="{{ $input }}">
                                    <option value="">—</option>
                                    @foreach($category->groups as $group)
                                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('match.group') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @elseif($match['stage'] === 'playoff')
                            <div>
                                <label class="{{ $label }}">Faza = kolumna (1 = pierwsza)</label>
                                <input type="number" min="1" wire:model="match.bracket_round" class="{{ $input }}">
                                @error('match.bracket_round') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $label }}">Nazwa fazy</label>
                                <input type="text" wire:model="match.phase_name" class="{{ $input }}" placeholder="np. Półfinały, Finały">
                            </div>
                            <div>
                                <label class="{{ $label }}">Decyduje o miejscu</label>
                                <select wire:model="match.decides_place" class="{{ $input }}">
                                    <option value="">— nie (np. półfinał) —</option>
                                    <option value="1">1. miejsce (finał)</option>
                                    @foreach([3, 5, 7, 9, 11, 13, 15] as $place)
                                        <option value="{{ $place }}">o {{ $place }}. miejsce</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $label }}">Pozycja w kolumnie</label>
                                <input type="number" min="1" wire:model="match.bracket_slot" class="{{ $input }}" placeholder="kolejność od góry">
                            </div>
                            <div>
                                <label class="{{ $label }}">Drabinka</label>
                                <select wire:model="match.placement" class="{{ $input }}">
                                    <option value="">główna (o 1. miejsce)</option>
                                    @foreach([3, 5, 7, 9, 11, 13] as $from)
                                        <option value="{{ $from }}">o miejsca od {{ $from }}. (np. {{ $from }}–{{ $from + 3 }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div>
                                <label class="{{ $label }}">Mecz o miejsce</label>
                                <input type="number" min="1" wire:model="match.placement" class="{{ $input }}" placeholder="np. 7 → o 7.–8.">
                                @error('match.placement') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div>
                            <label class="{{ $label }}">Etykieta</label>
                            <input type="text" wire:model="match.label" class="{{ $input }}" placeholder="opcjonalnie, np. Półfinał 1, Mecz o 3. miejsce">
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4 xl:grid-cols-2">
                        @include('livewire.admin.tournament-match-sides', ['prefix' => 'match', 'data' => $match, 'sourceOptions' => \App\Support\TournamentBracket::sourceOptions($category)])
                    </div>
                    <div class="grid gap-3 sm:grid-cols-4 xl:grid-cols-2">
                        <div>
                            <label class="{{ $label }}">Termin</label>
                            <x-datetime-input model="match.starts_at" class="{{ $input }}" />
                        </div>
                        <div>
                            <label class="{{ $label }}">Boisko / hala</label>
                            <x-court-picker :halls="$extraHalls" :main="$mainVenues" model="match.court" :value="$match['court'] ?? null" />
                        </div>
                        <div class="sm:col-span-2 flex items-end">
                            <button type="submit" class="{{ $primary }}">Dodaj mecz</button>
                        </div>
                    </div>
                </form>
                {{-- Harmonogram: pierwszy mecz i odstęp; zmiana godziny meczu przesuwa kolejne na tym samym sektorze --}}
                @if($category->matches->isNotEmpty())
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-semibold text-gray-900">Harmonogram</h3>
                        </div>
                        {{-- Dni turnieju: każdy dzień zaczyna się od podanego meczu, na każdym sektorze od godziny rozpoczęcia --}}
                        <div class="mt-3 space-y-2">
                            @foreach($scheduleDays as $index => $day)
                                <div class="flex flex-wrap items-center gap-2 text-sm" wire:key="schedule-day-{{ $index }}">
                                    <span class="w-16 font-semibold text-gray-900">Dzień {{ $index + 1 }}</span>
                                    <input type="date" wire:model.blur.live="scheduleDays.{{ $index }}.date" class="{{ str_replace('w-full', 'w-40', $input) }}" aria-label="Data dnia {{ $index + 1 }}">
                                    <span class="text-gray-500">od</span>
                                    <x-time-input :model="'scheduleDays.'.$index.'.time'" live class="{{ str_replace('w-full', 'w-32', $input) }}" aria-label="Godzina pierwszego meczu dnia {{ $index + 1 }}" />
                                    <span class="text-gray-500">mecze od</span>
                                    <span class="inline-flex items-center gap-1">#<input type="number" min="1" wire:model.blur.live="scheduleDays.{{ $index }}.from" @disabled($index === 0) placeholder="nr" class="{{ str_replace('w-full', 'w-20', $input) }} disabled:bg-gray-50" aria-label="Pierwszy mecz dnia {{ $index + 1 }}"></span>
                                    @if($index > 0)
                                        <button type="button" wire:click="removeScheduleDay({{ $index }})" class="text-xs text-red-600 hover:text-red-800">Usuń dzień</button>
                                    @endif
                                    @error("scheduleDays.{$index}.date") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    @error("scheduleDays.{$index}.time") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                    @error("scheduleDays.{$index}.from") <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                                </div>
                            @endforeach
                            <button type="button" wire:click="addScheduleDay" class="text-sm font-semibold text-sky-700 hover:text-sky-900">+ Dodaj dzień</button>
                        </div>
                        <div class="mt-3 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-3">
                            @if($extraHalls !== [])
                                <label class="block w-56 text-sm">
                                    <span class="text-gray-700">Obiekt kategorii</span>
                                    <select wire:model.live="categoryHall" class="{{ $input }} mt-1">
                                        <option value="">{{ \App\Support\TournamentHalls::mainName() }}</option>
                                        @foreach($extraHalls as $hall)
                                            <option value="{{ $hall['name'] }}">{{ $hall['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif
                            <label class="block w-40 text-sm">
                                <span class="text-gray-700">Odstęp na sektorze</span>
                                <input type="text" wire:model.blur.live="matchInterval" placeholder="1:30" class="{{ $input }} mt-1">
                            </label>
                            <button type="button" wire:click="arrangeSchedule" wire:confirm="Ustawić godziny wszystkim meczom tej kategorii? Każdego dnia na każdym sektorze pierwszy mecz zacznie się o godzinie rozpoczęcia dnia, a kolejne (według numeru) co odstęp. Mecze trwające i z wynikiem zostaną bez zmian." class="{{ $primary }}">Ułóż godziny według numerów</button>
                            <button type="button" wire:click="resetSchedule" wire:confirm="Usunąć daty i godziny wszystkich meczów tej kategorii? Sektory zostaną, mecze trwające i z wynikiem bez zmian. Można to cofnąć." class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-100">Resetuj harmonogram</button>
                            <label class="inline-flex items-center gap-2 pb-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.live="shiftFollowing" class="rounded border-gray-300">
                                Przesuwaj kolejne mecze na sektorze, gdy zmienię godzinę
                            </label>
                        </div>
                        @error('matchInterval') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="mt-2 text-xs text-gray-500">Zmiana godziny meczu przesuwa o tyle samo kolejne mecze tego dnia na tym samym sektorze, także z innych kategorii. Mecze trwające i z wynikiem się nie ruszają. Zmiana w dniu meczu pokazuje kibicom opóźnienie.</p>
                    </div>
                @endif
            </div>

            @if($scheduleUndo)
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900 ring-1 ring-emerald-200" role="status">
                    <span>✓ {{ $scheduleUndo['message'] }}</span>
                    <button type="button" wire:click="undoSchedule" class="ml-auto font-semibold underline hover:text-emerald-700">Cofnij</button>
                    <button type="button" wire:click="dismissScheduleUndo" class="text-emerald-700 hover:text-emerald-900" aria-label="Zamknij">×</button>
                </div>
            @endif

            @if($category->matches->count() > 1)
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-gray-50 px-4 py-3">
                    <button type="button" wire:click="renumberMatchesBySchedule" wire:confirm="Nadać meczom numery 1, 2, 3… według terminu (a przy tej samej godzinie według boiska)?" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-800 hover:bg-gray-100">Numeruj według harmonogramu</button>
                    <span class="text-xs text-gray-500">Numery nadajesz raz, przed turniejem. Późniejsza zmiana godziny albo boiska nie zmienia numeru meczu.</span>
                </div>
            @endif

            {{-- Dwie listy: faza grupowa oraz drabinka razem z meczami o miejsca (numeracja wspólna, kolejność dowolna) --}}
            @foreach(['group' => 'Faza grupowa', 'knockout' => 'Drabinka i mecze o miejsca'] as $list => $stageName)
                @php
                    $stageMatches = $category->matches->filter(fn ($m) => ($m->stage === \App\Models\TournamentMatch::STAGE_GROUP) === ($list === 'group'))->sortBy(fn ($m) => [$m->number ?? PHP_INT_MAX, $m->id]);
                @endphp
                @if($stageMatches->isNotEmpty())
                    <div>
                        <h3 class="mb-2 font-semibold text-gray-900">{{ $stageName }} <span class="text-xs font-normal text-gray-500">· kolejność i numery zmieniasz, przeciągając mecz za uchwyt ⠿ albo strzałkami</span></h3>
                        <div class="space-y-2" wire:sort="moveMatch">
                            @foreach($stageMatches as $tournamentMatch)
                                <div class="grid gap-2 rounded-lg border border-gray-200 bg-white p-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(8rem,1fr)_16rem_12rem_auto] lg:items-center" wire:key="match-{{ $tournamentMatch->id }}" wire:sort:item="{{ $tournamentMatch->id }}">
                                    <div class="flex items-center gap-2 text-sm">
                                        <div class="flex shrink-0 flex-col items-center text-gray-400">
                                            <button type="button" wire:click="moveMatchBy({{ $tournamentMatch->id }}, -1)" @disabled($loop->first) class="flex size-8 items-center justify-center rounded leading-none hover:bg-gray-100 hover:text-gray-900 disabled:opacity-30 lg:size-auto lg:hover:bg-transparent" aria-label="Przesuń wyżej">▲</button>
                                            <span wire:sort:handle class="cursor-grab text-lg leading-none hover:text-gray-900 active:cursor-grabbing" title="Przeciągnij, żeby zmienić kolejność">⠿</span>
                                            <button type="button" wire:click="moveMatchBy({{ $tournamentMatch->id }}, 1)" @disabled($loop->last) class="flex size-8 items-center justify-center rounded leading-none hover:bg-gray-100 hover:text-gray-900 disabled:opacity-30 lg:size-auto lg:hover:bg-transparent" aria-label="Przesuń niżej">▼</button>
                                        </div>
                                        <div class="min-w-0">
                                        <span class="text-xs font-semibold uppercase text-gray-500"><span class="mr-1 rounded bg-gray-900 px-1.5 py-0.5 text-white">#{{ $tournamentMatch->number }}</span>{{ $tournamentMatch->group?->name ?? $tournamentMatch->title() }}</span>
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach(['home', 'away'] as $side)
                                                @if($tournamentMatch->{$side})
                                                    <span class="font-semibold text-gray-900">{{ $tournamentMatch->{$side.'Name'}() }}</span>
                                                @else
                                                    <select wire:change="setMatchTeams({{ $tournamentMatch->id }}, '{{ $side }}', $event.target.value)" class="rounded-md border-gray-300 py-1 text-xs">
                                                        <option value="">{{ $tournamentMatch->{$side.'_placeholder'} ?: 'wybierz drużynę' }}</option>
                                                        @foreach($category->participants as $participant)
                                                            <option value="{{ $participant->id }}">{{ $participant->name() }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                                @if($side === 'home') <button type="button" wire:click="swapMatchSides({{ $tournamentMatch->id }})" class="flex size-8 items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-900 lg:size-auto lg:px-1" title="Zamień gospodarza z gościem" aria-label="Zamień gospodarza z gościem">⇄</button> @endif
                                            @endforeach
                                        </div>
                                        </div>
                                    </div>
                                    <div>
                                        <input type="text" wire:model.blur.live="results.{{ $tournamentMatch->id }}.sets" class="{{ $input }}" placeholder="25:20, 23:25, 15:12">
                                        @error("results.{$tournamentMatch->id}.sets") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                        <input type="text" wire:model.blur.live="results.{{ $tournamentMatch->id }}.mvp" maxlength="120" class="{{ $input }} mt-1" placeholder="Wyróżnienie (opcjonalnie), np. MVP: Jan Kowalski" aria-label="Wyróżnienie meczu">
                                    </div>
                                    <div>
                                        <x-datetime-input :model="'results.'.$tournamentMatch->id.'.starts_at'" live class="{{ $input }}" />
                                        @if($delay = $tournamentMatch->delayMinutes())
                                            <p class="mt-1 text-xs"><span class="rounded-full bg-amber-100 px-1.5 font-semibold text-amber-800">{{ $delay > 0 ? '+' : '−' }}{{ abs($delay) }} min</span> <span class="text-gray-500">plan {{ $tournamentMatch->planned_starts_at->format('H:i') }}</span></p>
                                        @endif
                                    </div>
                                    <x-court-picker :halls="$extraHalls" :main="$mainVenues" model="results.{{ $tournamentMatch->id }}.court" :value="$results[$tournamentMatch->id]['court'] ?? null" :free="false" />
                                    <div class="flex flex-wrap items-center gap-2 lg:gap-3">
                                        <label @class(['inline-flex min-h-10 cursor-pointer items-center gap-1.5 rounded-full px-3 py-0.5 text-xs lg:min-h-0 lg:px-2 font-semibold ring-1', 'bg-red-50 text-red-700 ring-red-200' => $results[$tournamentMatch->id]['live'] ?? false, 'text-gray-500 ring-gray-200' => ! ($results[$tournamentMatch->id]['live'] ?? false)]) title="Włącz, gdy mecz się zaczął; wyłącz po wpisaniu wyniku końcowego">
                                            <input type="checkbox" wire:model.live="results.{{ $tournamentMatch->id }}.live" class="size-3.5 rounded border-gray-300 text-red-600">
                                            Trwa
                                        </label>
                                        @if($tournamentMatch->hasStreams())<span class="text-xs text-red-600" title="Mecz ma transmisję">▶</span>@endif
                                        <button type="button" wire:click="startMatchEdit({{ $tournamentMatch->id }})" class="admin-action">Edytuj</button>
                                        <button type="button" wire:click="deleteMatch({{ $tournamentMatch->id }})" wire:confirm="Usunąć ten mecz?" class="admin-action admin-action-danger">Usuń</button>
                                    </div>
                                    @if($editingMatchId === $tournamentMatch->id)
                                        @include('livewire.admin.tournament-match-edit', ['tournamentMatch' => $tournamentMatch])
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Obiekty turnieju: hala klubowa jest zawsze; inne obiekty mają kolor, adres i dowolne sale (sektory, całe boisko) --}}
            <details class="rounded-lg border border-gray-200 bg-white p-4" @if($halls !== []) open @endif>
                <summary class="cursor-pointer font-semibold text-gray-900">Obiekty turnieju <span class="text-xs font-normal text-gray-500">· {{ \App\Support\TournamentHalls::mainName() }}@foreach($extraHalls as $hall), {{ $hall['name'] }}@endforeach</span></summary>
                <p class="mt-2 text-xs text-gray-500">Gdy część meczów gra się poza {{ \App\Support\TournamentHalls::mainName() }}, dodaj obiekt (np. MOSiR, szkołę). Obiekt może mieć kilka sal; każdą salę dzielisz na sektory albo zostawiasz jako jedno boisko. Kolor obiektu widać przy wyborze boiska, w obłożeniu i w legendzie dla kibiców.</p>
                <div class="mt-3 grid gap-3 lg:grid-cols-2">
                    {{-- Hala klubowa: zawsze jest; w turnieju można wyłączyć sektory albo główne boisko --}}
                    @php $mainOff = \App\Support\TournamentHalls::mainOff($tournament); $venueNames = \App\Models\TrainingSession::venueNames(); @endphp
                    <div class="space-y-2 rounded-lg border-l-4 border-sky-500 bg-gray-50 p-3 ring-1 ring-gray-200 lg:col-span-2">
                        <p class="font-semibold text-gray-900">{{ \App\Support\TournamentHalls::mainName() }} <span class="text-xs font-normal text-gray-500">· hala klubowa, adres z ustawień turnieju</span></p>
                        <p class="text-[11px] text-gray-500">Kliknij część hali, której w tym turnieju nie będzie (np. główne boisko, gdy sala jest podzielona na trzy sektory).</p>
                        <div class="flex flex-wrap items-end gap-2">
                            @foreach(\App\Support\TournamentHalls::MAIN_VENUES as $venue)
                                @php $isOff = in_array($venue, $mainOff, true); @endphp
                                <button type="button" wire:click="toggleMainVenue('{{ $venue }}')" aria-pressed="{{ $isOff ? 'false' : 'true' }}" title="{{ $isOff ? 'Niedostępne: kliknij, żeby włączyć' : 'Dostępne: kliknij, żeby wyłączyć' }}"
                                    @class(['flex flex-col items-center gap-0.5 rounded-md p-1 text-[11px] font-semibold ring-1 transition', 'text-gray-400 opacity-60 grayscale ring-0 outline-1 outline-gray-300 outline-dashed' => $isOff, 'text-gray-700 ring-gray-200 hover:ring-gray-400' => ! $isOff])>
                                    <x-venue-icon :venue="$venue" />
                                    <span>{{ $venueNames[$venue] }}</span>
                                    <span @class(['text-[10px] font-medium', 'text-gray-400' => $isOff, 'text-emerald-700' => ! $isOff])>{{ $isOff ? 'niedostępne' : 'dostępne' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    @foreach($halls as $index => $hall)
                        <div class="space-y-3 rounded-lg border-l-4 bg-gray-50 p-3 ring-1 ring-gray-200" style="border-left-color: {{ $hall['color'] ?? '#f97316' }}" wire:key="hall-{{ $index }}">
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live.debounce.400ms="halls.{{ $index }}.color" class="h-9 w-10 shrink-0 cursor-pointer rounded border border-gray-300 bg-white p-0.5" title="Kolor obiektu" aria-label="Kolor obiektu">
                                <input type="text" wire:model.blur.live="halls.{{ $index }}.name" maxlength="60" placeholder="Krótka nazwa, np. SP 6" class="{{ $input }} font-semibold" aria-label="Krótka nazwa obiektu" title="Krótka nazwa: w nagłówkach terminarza i przy meczach">
                                <button type="button" wire:click="removeHall({{ $index }})" wire:confirm="Usunąć ten obiekt?" class="shrink-0 rounded-md px-2 py-1.5 text-sm text-red-600 hover:bg-red-50">Usuń</button>
                            </div>
                            @error("halls.{$index}.name") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <input type="text" wire:model.blur.live="halls.{{ $index }}.full_name" maxlength="160" placeholder="Pełna nazwa (opcjonalnie), np. Szkoła Podstawowa nr 6 im. Lotników Polskich" class="{{ $input }}" aria-label="Pełna nazwa obiektu">
                            <input type="text" wire:model.blur.live="halls.{{ $index }}.address" maxlength="160" placeholder="Adres, np. ul. Żeromskiego 3, Piła" class="{{ $input }}" aria-label="Adres obiektu">

                            @foreach($hall['rooms'] as $roomIndex => $room)
                                @php
                                    $sectors = max(1, min(4, (int) $room['sectors']));
                                    $roomOff = \App\Support\TournamentHalls::room($room)['off'];
                                @endphp
                                <div class="space-y-2 rounded-md bg-white p-2.5 ring-1 ring-gray-200" wire:key="hall-{{ $index }}-room-{{ $roomIndex }}">
                                    <div class="flex items-center gap-2">
                                        <input type="text" wire:model.blur.live="halls.{{ $index }}.rooms.{{ $roomIndex }}.name" maxlength="40" placeholder="{{ count($hall['rooms']) > 1 ? 'Nazwa sali, np. Hala 1' : 'Nazwa sali (opcjonalnie)' }}" class="{{ $input }}" aria-label="Nazwa sali">
                                        @if(count($hall['rooms']) > 1)
                                            <button type="button" wire:click="removeRoom({{ $index }}, {{ $roomIndex }})" wire:confirm="Usunąć tę salę?" class="shrink-0 rounded-md px-2 py-1 text-xs text-red-600 hover:bg-red-50">Usuń salę</button>
                                        @endif
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        {{-- Podział sali: jedno boisko, dwa osobne boiska (np. dwie sale w szkole) albo 2–4 sektory --}}
                                        @php $separate = (bool) ($room['separate'] ?? false); @endphp
                                        <span class="inline-flex flex-wrap overflow-hidden rounded-md font-semibold ring-1 ring-gray-300" role="group" aria-label="Podział sali">
                                            @foreach([[1, false, 'Jedno boisko'], [2, true, '2 boiska'], [2, false, '2 sektory'], [3, false, '3 sektory'], [4, false, '4 sektory']] as [$count, $courtsOption, $countLabel])
                                                @php $chosen = $sectors === $count && $separate === $courtsOption; @endphp
                                                <button type="button" wire:click="setDivision({{ $index }}, {{ $roomIndex }}, {{ $count }}, {{ $courtsOption ? 'true' : 'false' }})" aria-pressed="{{ $chosen ? 'true' : 'false' }}" @class(['px-2.5 py-1.5 not-first:border-l not-first:border-gray-300', 'bg-sky-600 text-white' => $chosen, 'bg-white text-gray-700 hover:bg-gray-50' => ! $chosen])>{{ $countLabel }}</button>
                                            @endforeach
                                        </span>
                                        @if($sectors > 1 && ! $separate)
                                            <label class="inline-flex items-center gap-1.5 text-gray-700"><input type="checkbox" wire:model.live="halls.{{ $index }}.rooms.{{ $roomIndex }}.whole" class="rounded border-gray-300"> także całe boisko</label>
                                        @endif
                                    </div>
                                    {{-- Podgląd boisk; przy sali z sektorami kliknięcie sektora włącza go albo wyłącza dla turnieju --}}
                                    @if($sectors > 1)<p class="text-[11px] text-gray-500">Kliknij {{ $separate ? 'boisko' : 'sektor' }}, którego nie macie do dyspozycji (np. gra tam inny turniej).</p>@endif
                                    <div class="flex flex-wrap items-end gap-2">
                                        @if($sectors === 1)
                                            <span class="flex flex-col items-center gap-0.5 text-[11px] font-semibold text-gray-600">
                                                <x-venue-icon :parts="1" :whole="true" :color="$hall['color'] ?? null" />{{ trim($room['name']) ?: 'Całe boisko' }}
                                            </span>
                                        @else
                                            @foreach(range(0, $sectors - 1) as $sectorIndex)
                                                @php $isOff = in_array($sectorIndex, $roomOff, true); @endphp
                                                <button type="button" wire:click="toggleSector({{ $index }}, {{ $roomIndex }}, {{ $sectorIndex }})" aria-pressed="{{ $isOff ? 'false' : 'true' }}" title="{{ $isOff ? 'Niedostępny: kliknij, żeby włączyć' : 'Dostępny: kliknij, żeby wyłączyć' }}"
                                                    @class(['flex flex-col items-center gap-0.5 rounded-md p-1 text-[11px] font-semibold ring-1 transition', 'text-gray-400 ring-0 outline-1 outline-gray-300 outline-dashed' => $isOff, 'text-gray-700 ring-gray-200 hover:ring-gray-400' => ! $isOff])>
                                                    <x-venue-icon :parts="$sectors" :index="$isOff ? null : $sectorIndex" :color="$hall['color'] ?? null" :off="$isOff ? [$sectorIndex] : []" :separate="$separate" />
                                                    <span>{{ \App\Support\TournamentHalls::sectorLabel($sectorIndex + 1, $separate) }}</span>
                                                    <span @class(['text-[10px] font-medium', 'text-gray-400' => $isOff, 'text-emerald-700' => ! $isOff])>{{ $isOff ? 'niedostępny' : 'dostępny' }}</span>
                                                </button>
                                            @endforeach
                                            @if(($room['whole'] ?? false) && ! $separate)
                                                <span @class(['flex flex-col items-center gap-0.5 p-1 text-[11px] font-semibold', 'text-gray-600' => $roomOff === [], 'text-gray-400' => $roomOff !== []])>
                                                    <x-venue-icon :parts="$sectors" :whole="$roomOff === []" :color="$roomOff === [] ? ($hall['color'] ?? null) : '#d4d4d8'" :off="$roomOff" />Całe boisko
                                                    @if($roomOff !== [])<span class="text-[10px] font-medium">tylko gdy cała sala</span>@endif
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            <button type="button" wire:click="addRoom({{ $index }})" class="text-sm font-semibold text-sky-700 hover:text-sky-900">+ Dodaj salę</button>
                        </div>
                    @endforeach
                    <div class="flex min-h-24 flex-col items-stretch justify-center gap-2 rounded-lg border-2 border-dashed border-gray-300 p-3">
                        <button type="button" wire:click="addHall" class="rounded-md py-1.5 text-sm font-semibold text-sky-700 hover:bg-sky-50">+ Dodaj obiekt</button>
                        @if($knownHalls = $this->knownHalls())
                            {{-- Obiekty z poprzednich turniejów: adres, kolor i sale przechodzą od razu --}}
                            <p class="text-center text-xs text-gray-500">albo z poprzednich turniejów:</p>
                            <div class="flex flex-wrap justify-center gap-1.5">
                                @foreach($knownHalls as $known)
                                    <button type="button" wire:click="addKnownHall(@js($known['name']))" title="{{ $known['address'] }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-xs font-semibold text-gray-800 ring-1 ring-gray-300 hover:bg-gray-50">
                                        <span class="size-2.5 rounded-full" style="background: {{ $known['color'] }}"></span>{{ $known['name'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                @error('halls') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </details>

            @if($category->matches->isNotEmpty())
                <details class="group rounded-lg border border-gray-200 bg-white p-4">
                    <summary class="cursor-pointer font-semibold text-gray-900">Obłożenie sektorów i hal <span class="text-xs font-normal text-gray-500">· wszystkie kategorie, cały turniej</span></summary>
                    <x-sector-occupancy :tournament="$tournament" class="mt-4" />
                </details>
            @endif
        </div>

        {{-- Ukryte na dole, żeby w trakcie turnieju nie wyczyścić wyników przypadkiem --}}
        @php($backups = $category->resultBackups()->get())
        <details class="mt-10 rounded-lg border border-red-200 bg-red-50/40" @if($backups->isNotEmpty() || $errors->has('clearConfirm')) open @endif>
            <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-red-800">Strefa niebezpieczna: wyczyść wyniki kategorii {{ $category->name }}</summary>
            <div class="space-y-4 border-t border-red-200 px-4 py-4">
                <p class="text-sm text-gray-700">Usuwa wszystkie wyniki, zaznaczenia „Trwa”, miejsca końcowe i ręczną kolejność tabel w tej kategorii. Mecze, godziny, sektory, drużyny i grupy zostają. Przed wyczyszczeniem zapisuje się kopia, którą można przywrócić.</p>
                <form wire:submit="clearResults" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label class="{{ $label }}">Przepisz nazwę kategorii, żeby potwierdzić</label>
                        <input type="text" wire:model="clearConfirm" class="{{ $input }} max-w-xs" placeholder="{{ $category->name }}" autocomplete="off">
                    </div>
                    <button type="submit" wire:confirm="Na pewno wyczyścić wszystkie wyniki kategorii {{ $category->name }}?" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700">Wyczyść wyniki</button>
                </form>
                @error('clearConfirm') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                @if($backups->isNotEmpty())
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900">Kopie wyników</h4>
                        <ul class="mt-2 divide-y divide-gray-200 rounded-lg border border-gray-200 bg-white">
                            @foreach($backups as $backup)
                                <li class="flex flex-wrap items-center gap-3 px-4 py-2 text-sm" wire:key="backup-{{ $backup->id }}">
                                    <span>{{ $backup->created_at->format('d.m.Y H:i') }} · wyników: {{ $backup->results_count }}</span>
                                    <span class="ml-auto flex gap-3">
                                        <button type="button" wire:click="restoreResults({{ $backup->id }})" wire:confirm="Przywrócić wyniki z {{ $backup->created_at->format('d.m.Y H:i') }}? Wyniki wpisane później w tych meczach zostaną zastąpione." class="text-sky-700 hover:text-blue-800">Przywróć</button>
                                        <button type="button" wire:click="deleteResultBackup({{ $backup->id }})" wire:confirm="Usunąć tę kopię? Nie da się jej potem przywrócić." class="text-red-600 hover:text-red-800">Usuń kopię</button>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </details>
    @endif
</div>
