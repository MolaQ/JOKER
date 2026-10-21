@php
    $input = 'w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm';
    $label = 'block text-sm font-medium text-gray-700 mb-1';
    $primary = 'inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700';
    $secondary = 'inline-flex items-center justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50';
    $sections = ['strona' => 'Strona i informator', 'kategorie' => 'Kategorie', 'druzyny' => 'Drużyny', 'grupy' => 'Grupy', 'mecze' => 'Mecze i wyniki'];
@endphp

<div class="space-y-6">

    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
        @foreach($sections as $key => $name)
            <button type="button" wire:click="$set('section', '{{ $key }}')"
                class="rounded-full px-4 py-1.5 text-sm font-semibold {{ $section === $key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $name }}
            </button>
        @endforeach
        @if($tournament->has_page)
            <a href="{{ route('tournaments.show', $tournament) }}" target="_blank" class="ml-auto text-sm font-semibold text-blue-600 hover:underline self-center">Zobacz stronę ↗</a>
        @endif
    </div>

    @if($section !== 'strona' && $section !== 'kategorie')
        <div class="flex flex-wrap items-center gap-3 rounded-lg bg-gray-50 p-3">
            <span class="text-sm font-medium text-gray-700">Kategoria:</span>
            @forelse($categories as $cat)
                <button type="button" wire:click="$set('categoryId', {{ $cat->id }})"
                    class="rounded-md px-3 py-1 text-sm font-semibold {{ $categoryId === $cat->id ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300' }}">
                    {{ $cat->name }}
                </button>
            @empty
                <span class="text-sm text-gray-500">Najpierw dodaj kategorię wiekową w zakładce „Kategorie”.</span>
            @endforelse
        </div>
    @endif

    {{-- Strona i informator --}}
    @if($section === 'strona')
        <div class="space-y-5">
            <p class="text-xs text-gray-500">Zmiany zapisują się same po wyjściu z pola.</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                    <input type="checkbox" wire:model.live="has_page" class="rounded border-gray-300 text-blue-600">
                    <span class="text-sm"><strong>Publiczna strona turnieju</strong><br><span class="text-gray-500">Zakładki: drużyny, grupy, mecze, drabinka, klasyfikacja, zasady, informator.</span></span>
                </label>
                <div>
                    <label class="{{ $label }}">Cykl</label>
                    <select wire:model.live="series" class="{{ $input }}">
                        <option value="">— pojedynczy turniej —</option>
                        <option value="{{ \App\Models\Tournament::SERIES_MEMORIAL }}">Memoriał im. Włodzimierza Winklera (zakładka „Memoriał” w menu)</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Zakładka „Memoriał” otwiera najnowszą edycję z cyklu, starsze trafiają do archiwum.</p>
                </div>
            </div>

            <div>
                <label class="{{ $label }}">Zdjęcie w nagłówku strony</label>
                @if($tournament->image_path)
                    <img src="{{ \App\Support\Media::url($tournament->image_path) }}" alt="" class="mb-2 h-24 rounded-md object-cover">
                @endif
                <input type="file" wire:model="image" accept="image/*" class="text-sm">
                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-lg border border-gray-200 p-4">
                @php $scoringFields = ['win' => 'Wygrana', 'win_tiebreak' => 'Wygrana w tie-breaku', 'loss_tiebreak' => 'Porażka w tie-breaku', 'loss' => 'Porażka', 'per_set' => 'Za każdy wygrany set']; @endphp
                <p class="text-sm font-semibold text-gray-900">Punktacja meczów grupowych (domyślna dla wszystkich grup)</p>
                <p class="text-xs text-gray-500">Tie-break to mecz rozstrzygnięty w ostatnim secie (2:1, 3:2). Grupa może mieć własną punktację w zakładce „Grupy”.</p>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
                    @foreach($scoringFields as $field => $fieldName)
                        <label class="text-sm text-gray-700">{{ $fieldName }}
                            <input type="number" min="0" max="20" wire:model.blur="scoring.{{ $field }}" class="{{ $input }} mt-1">
                        </label>
                    @endforeach
                </div>
                @error('scoring.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                        @if($renaming === 'category:'.$cat->id)
                            <input type="text" wire:model.blur="categoryNames.{{ $cat->id }}" x-init="$el.focus()" x-on:keydown.enter="$el.blur()" x-on:blur="$wire.set('renaming', '')" class="{{ $input }} max-w-xs">
                        @else
                            <span class="text-sm"><strong>{{ $cat->name }}</strong> <span class="text-gray-500">· drużyn: {{ $cat->participants_count }} · meczów: {{ $cat->matches_count }}</span></span>
                        @endif
                        @error('categoryNames.'.$cat->id) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        <span class="ml-auto flex gap-3">
                        <button type="button" wire:click="$set('renaming', 'category:{{ $cat->id }}')" class="text-sm text-blue-600 hover:text-blue-800">Edytuj</button>
                        <button type="button" wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Usunąć kategorię {{ $cat->name }} razem z drużynami, grupami i meczami?" class="text-sm text-red-600 hover:text-red-800">Usuń</button>
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-gray-500">Brak kategorii. Dodaj np. „Kadetki”, „Juniorki młodsze”, „Młodziczki”.</li>
                @endforelse
            </ul>
            <form wire:submit="addCategory" class="flex gap-2">
                <input type="text" wire:model="newCategory" placeholder="Nazwa kategorii wiekowej" class="{{ $input }} max-w-sm">
                <button type="submit" class="{{ $primary }}">Dodaj kategorię</button>
            </form>
            @error('newCategory') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    @endif

    {{-- Drużyny --}}
    @if($section === 'druzyny' && $category)
        <div class="space-y-5">
            <form wire:submit="addParticipant" class="grid gap-3 rounded-lg border border-dashed border-gray-300 p-4 sm:grid-cols-[1fr_1fr_auto_auto] sm:items-end">
                <div>
                    <label class="{{ $label }}">Drużyna z listy</label>
                    <select wire:model="newParticipant" class="{{ $input }}">
                        <option value="">— nowa drużyna (wpisz nazwę) —</option>
                        @if($teamOptions->isNotEmpty())
                            <optgroup label="Nasze drużyny">
                                @foreach($teamOptions as $team)
                                    <option value="team:{{ $team->id }}">Joker: {{ $team->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @foreach($rivalOptions->groupBy(fn ($rival) => $rival->club?->name ?? 'Rywale bez klubu')->sortKeys() as $clubName => $clubRivals)
                            <optgroup label="{{ $clubName }}">
                                @foreach($clubRivals as $rival)
                                    <option value="rival:{{ $rival->id }}">{{ $rival->full_name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $label }}">albo nazwa nowej drużyny</label>
                    <input type="text" wire:model="newParticipantName" class="{{ $input }}" placeholder="np. UKS Dwójka Kalisz">
                </div>
                <div>
                    <label class="{{ $label }}">Logo</label>
                    <input type="file" wire:model="newParticipantLogo" accept="image/*" class="text-sm w-48">
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
                                <input type="text" wire:model.blur="participants.{{ $participant->id }}.display_name" class="{{ $input }}" placeholder="opcjonalnie">
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
                                <input type="number" min="1" wire:model.blur="participants.{{ $participant->id }}.final_position" class="{{ $input }}" placeholder="auto">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Skład (jedna osoba w linii)</label>
                                <textarea rows="2" wire:model.blur="participants.{{ $participant->id }}.roster" class="{{ $input }}" placeholder="opcjonalnie"></textarea>
                            </div>
                            <button type="button" wire:click="deleteParticipant({{ $participant->id }})" wire:confirm="Usunąć {{ $participant->name() }} z turnieju?" class="text-sm text-red-600 hover:text-red-800 lg:mt-6">Usuń</button>
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
                                <input type="text" wire:model.blur="groupNames.{{ $group->id }}" x-init="$el.focus()" x-on:keydown.enter="$el.blur()" x-on:blur="$wire.set('renaming', '')" class="{{ $input }} max-w-xs">
                            @else
                                <h3 class="font-semibold text-gray-900">{{ $group->name }}</h3>
                            @endif
                            @error('groupNames.'.$group->id) <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                            <span class="ml-auto flex gap-3">
                                <button type="button" wire:click="$set('renaming', 'group:{{ $group->id }}')" class="text-sm text-blue-600 hover:text-blue-800">Edytuj</button>
                                <button type="button" wire:click="deleteGroup({{ $group->id }})" wire:confirm="Usunąć {{ $group->name }}? Drużyny zostaną bez grupy, a mecze grupy zostaną usunięte." class="text-sm text-red-600 hover:text-red-800">Usuń</button>
                            </span>
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
                            <button type="button" wire:click="addGroupSlot({{ $group->id }})" class="mt-2 text-sm font-semibold text-blue-600 hover:text-blue-800">+ Dodaj miejsce w składzie</button>
                        </div>

                        @if($members->isNotEmpty())
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase">Reguły grupy: miejsce w tabeli → klasyfikacja końcowa</p>
                                <div class="mt-1 grid grid-cols-2 gap-x-4 gap-y-2 xl:grid-cols-3">
                                    @foreach(range(1, $members->count()) as $position)
                                        <label class="flex items-center gap-2 text-sm whitespace-nowrap text-gray-700">
                                            <span class="shrink-0">{{ $position }}. miejsce →</span>
                                            <input type="number" min="1" wire:model.blur="groupPlaces.{{ $group->id }}.{{ $position }}" class="{{ $input }} !w-16" placeholder="—">
                                        </label>
                                    @endforeach
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Np. w grupie finałowej 1 → 1, 2 → 2… Puste pole = to miejsce nie trafia do klasyfikacji (rozstrzygają dalsze mecze).</p>
                            </div>
                        @endif

                        <div>
                            @php $scoringFields = ['win' => 'Wygrana', 'win_tiebreak' => 'Wygrana w tie-breaku', 'loss_tiebreak' => 'Porażka w tie-breaku', 'loss' => 'Porażka', 'per_set' => 'Za każdy wygrany set']; @endphp
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.live="groupScoring.{{ $group->id }}.custom" class="rounded border-gray-300 text-blue-600">
                                Własna punktacja grupy <span class="text-xs text-gray-500">(inaczej obowiązuje punktacja turnieju)</span>
                            </label>
                            @if(! empty($groupScoring[$group->id]['custom']))
                                <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    @foreach($scoringFields as $field => $fieldName)
                                        <label class="text-xs text-gray-600">{{ $fieldName }}
                                            <input type="number" min="0" max="20" wire:model.blur="groupScoring.{{ $group->id }}.{{ $field }}" class="{{ $input }} mt-1">
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>

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
    @if($section === 'mecze' && $category)
        <div class="space-y-6">
            <form wire:submit="addMatch" class="space-y-3 rounded-lg border border-dashed border-gray-300 p-4">
                <h3 class="font-semibold text-gray-900">Dodaj mecz</h3>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-5">
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
                <div class="grid gap-3 sm:grid-cols-4">
                    @include('livewire.admin.tournament-match-sides', ['prefix' => 'match', 'data' => $match, 'sourceOptions' => \App\Support\TournamentBracket::sourceOptions($category)])
                </div>
                <div class="grid gap-3 sm:grid-cols-4">
                    <div>
                        <label class="{{ $label }}">Termin</label>
                        <input type="datetime-local" wire:model="match.starts_at" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="{{ $label }}">Boisko / hala</label>
                        <input type="text" wire:model="match.court" class="{{ $input }}" placeholder="np. Hala Lotnisko, boisko 2">
                    </div>
                    <div class="sm:col-span-2 flex items-end">
                        <button type="submit" class="{{ $primary }}">Dodaj mecz</button>
                    </div>
                </div>
            </form>

            @foreach(\App\Models\TournamentMatch::STAGES as $stage => $stageName)
                @php
                    $stageMatches = $category->matches->where('stage', $stage)->sortBy(fn ($m) => [$m->tournament_group_id ?? 0, $m->bracket_round ?? 0, $m->bracket_slot ?? 0, $m->placement ?? 0, $m->starts_at?->timestamp ?? 0, $m->id]);
                @endphp
                @if($stageMatches->isNotEmpty())
                    <div>
                        <h3 class="mb-2 font-semibold text-gray-900">{{ $stageName }}</h3>
                        <div class="space-y-2">
                            @foreach($stageMatches as $tournamentMatch)
                                <div class="grid gap-2 rounded-lg border border-gray-200 p-3 lg:grid-cols-[minmax(0,2fr)_10rem_11rem_9rem_auto] lg:items-center" wire:key="match-{{ $tournamentMatch->id }}">
                                    <div class="text-sm">
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
                                                @if($side === 'home') <span class="text-gray-400">–</span> @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div>
                                        <input type="text" wire:model.blur="results.{{ $tournamentMatch->id }}.sets" class="{{ $input }}" placeholder="25:20, 23:25, 15:12">
                                        @error("results.{$tournamentMatch->id}.sets") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <input type="datetime-local" wire:model.blur="results.{{ $tournamentMatch->id }}.starts_at" class="{{ $input }}">
                                    <input type="text" wire:model.blur="results.{{ $tournamentMatch->id }}.court" class="{{ $input }}" placeholder="boisko">
                                    <div class="flex items-center gap-3">
                                        <button type="button" wire:click="startMatchEdit({{ $tournamentMatch->id }})" class="text-sm text-blue-600 hover:text-blue-800">Edytuj</button>
                                        <button type="button" wire:click="deleteMatch({{ $tournamentMatch->id }})" wire:confirm="Usunąć ten mecz?" class="text-sm text-red-600 hover:text-red-800">Usuń</button>
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
        </div>
    @endif
</div>
