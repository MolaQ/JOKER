@php
    $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500';
    $label = 'block text-sm font-medium text-gray-700';
    $primary = 'inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700';
@endphp
<div class="space-y-6">
    <nav class="flex flex-wrap items-center gap-2">
        <button type="button" wire:click="$set('section', 'wyjazdy')" @class(['rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $section === 'wyjazdy', 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' => $section !== 'wyjazdy'])>Turnieje i wyniki</button>
        <span class="mx-1 h-6 w-px bg-gray-300" aria-hidden="true"></span>
        @foreach($groups as $tabGroup)
            <button type="button" wire:click="openGroup({{ $tabGroup->id }})" wire:key="group-tab-{{ $tabGroup->id }}" @class(['inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $section === 'grupa' && $groupTab === $tabGroup->id, 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' => ! ($section === 'grupa' && $groupTab === $tabGroup->id)])>
                <x-mini-players :group="$tabGroup" size="xs" /> {{ $tabGroup->name }}
                @unless($tabGroup->is_active)<span class="rounded bg-amber-100 px-1 text-[10px] font-bold text-amber-800 uppercase">ukryta</span>@endunless
            </button>
        @endforeach
        <span class="mx-1 h-6 w-px bg-gray-300" aria-hidden="true"></span>
        @foreach(['grupy' => '＋ Grupy', 'dzieci' => 'Wszystkie dzieci'] as $key => $name)
            <button type="button" wire:click="$set('section', '{{ $key }}')" @class(['rounded-full px-4 py-1.5 text-sm font-semibold', 'bg-sky-600 text-white' => $section === $key, 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50' => $section !== $key])>{{ $name }}</button>
        @endforeach
    </nav>

    {{-- ── WYJAZDY ── --}}
    @if($section === 'wyjazdy')
        @if($showTripForm)
            <form wire:submit="saveTrip" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
                <h2 class="text-lg font-semibold text-gray-900">{{ $tripId ? 'Dane turnieju' : 'Nowy turniej (wyjazd albo u nas)' }}</h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="sm:col-span-2"><label class="{{ $label }}">Turniej</label><input type="text" wire:model="trip.title" placeholder="np. Mistrzostwa Wielkopolski, eliminacje" class="{{ $input }}">@error('trip.title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="{{ $label }}">Data</label><input type="date" wire:model="trip.starts_on" class="{{ $input }}">@error('trip.starts_on')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="{{ $label }}">Do (gdy kilka dni)</label><input type="date" wire:model="trip.ends_on" class="{{ $input }}">@error('trip.ends_on')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label class="{{ $label }}">Miasto</label><input type="text" wire:model="trip.city" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Hala / adres</label><input type="text" wire:model="trip.venue" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Organizator</label><input type="text" wire:model="trip.organizer" placeholder="np. WZPS" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">Nasz turniej (pełne wyniki)</label>
                        <select wire:model="trip.tournament_id" class="{{ $input }}"><option value="">nie</option>@foreach($tournaments as $tournament)<option value="{{ $tournament->id }}">{{ $tournament->fullName() }} ({{ $tournament->start_date->format('Y') }})</option>@endforeach</select></div>
                    <div class="sm:col-span-2"><label class="{{ $label }}">Opis dla wszystkich (opcjonalnie)</label><textarea wire:model="trip.description" rows="3" class="{{ $input }}"></textarea></div>
                    <div class="sm:col-span-2"><label class="{{ $label }}">🔒 Dla rodziców: zbiórka, transport, koszt, co zabrać</label><textarea wire:model="trip.parent_info" rows="3" placeholder="Zbiórka 7:00 pod halą, powrót ok. 17:00, bus klubowy, koszt 30 zł." class="{{ $input }}"></textarea><p class="mt-1 text-xs text-gray-500">Widzą to tylko zalogowani rodzice i osoby z panelu klubu.</p></div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="{{ $tripId ? '$set(\'showTripForm\', false)' : 'closeTrip' }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>
                    <button type="submit" class="{{ $primary }}">Zapisz</button>
                </div>
            </form>
        @endif

        @if($openTrip)
            {{-- Otwarty wyjazd: drużyny, składy i wynik (duże pola, wygodne na telefonie) --}}
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" wire:click="closeTrip" class="text-sm font-semibold text-sky-700 hover:text-sky-900">← Wszystkie turnieje</button>
                    <h2 class="text-xl font-bold text-gray-900">{{ $openTrip->title }}</h2>
                    <span class="text-sm text-gray-500">{{ $openTrip->dateLabel() }}@if($openTrip->city) · {{ $openTrip->city }}@endif</span>
                    @unless($showTripForm)<button type="button" wire:click="editTripDetails" class="admin-action ml-auto">Edytuj dane</button>@endunless
                    <button type="button" wire:click="deleteTrip({{ $openTrip->id }})" wire:confirm="Usunąć wyjazd razem z drużynami i wynikami?" class="admin-action admin-action-danger">Usuń</button>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    @foreach($openTrip->teams as $team)
                        @php $chosen = $team->players->pluck('id'); @endphp
                        <section class="space-y-4 rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200" wire:key="team-{{ $team->id }}">
                            <div class="flex items-center gap-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ $team->name }}</h3>
                                @if($team->group)<span class="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-800">{{ $team->group->name }}</span>@endif
                                <button type="button" wire:click="deleteTeam({{ $team->id }})" wire:confirm="Usunąć drużynę {{ $team->name }} z wyjazdu?" class="ml-auto text-xs text-red-600 hover:text-red-800">Usuń drużynę</button>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="block text-sm text-gray-700">Miejsce<input type="number" min="1" inputmode="numeric" wire:model.blur.live="results.{{ $team->id }}.place" class="{{ $input }} text-2xl font-bold"></label>
                                <label class="block text-sm text-gray-700">na ile drużyn<input type="number" min="1" inputmode="numeric" wire:model.blur.live="results.{{ $team->id }}.teams_count" class="{{ $input }} text-2xl font-bold"></label>
                                <label class="col-span-2 block text-sm text-gray-700">Krótko (opcjonalnie)<input type="text" wire:model.blur.live="results.{{ $team->id }}.note" placeholder="np. finał przegrany 1:2" class="{{ $input }}"></label>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-700">Skład <span class="font-normal text-gray-500">({{ $chosen->count() }}) · kliknij, żeby dodać albo usunąć; młodsze dziecko może grać w starszej grupie</span></p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($allPlayers->sortBy(fn ($p) => [$p->mini_group_id === $team->mini_group_id ? 0 : 1, $p->last_name]) as $child)
                                        <button type="button" wire:click="togglePlayer({{ $team->id }}, {{ $child->id }})" @class(['rounded-full px-2.5 py-1 text-xs font-semibold ring-1', 'bg-sky-600 text-white ring-sky-600' => $chosen->contains($child->id), 'bg-white text-gray-700 ring-gray-200 hover:bg-gray-50' => ! $chosen->contains($child->id)])>
                                            {{ $child->name() }}@if($child->group && $child->mini_group_id !== $team->mini_group_id)<span class="opacity-60"> · {{ $child->group->name }}</span>@endif
                                        </button>
                                    @endforeach
                                    @if($allPlayers->isEmpty())<p class="text-xs text-gray-500">Najpierw dodaj dzieci w zakładce „Dzieci”.</p>@endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                @if($team->photoUrl())<img src="{{ $team->photoUrl() }}" alt="" class="h-16 w-24 rounded object-cover">@endif
                                @if($photoTeamId === $team->id)
                                    <x-photo-cropper model="teamPhoto" :preview="false" aspect="free" :width="1600" label="Wybierz zdjęcie" />
                                @else
                                    <button type="button" wire:click="$set('photoTeamId', {{ $team->id }})" class="admin-action">📷 {{ $team->photoUrl() ? 'Zmień zdjęcie' : 'Dodaj zdjęcie' }}</button>
                                @endif
                            </div>
                        </section>
                    @endforeach
                </div>

                <form wire:submit="addTeam" class="flex flex-wrap items-end gap-3 rounded-lg bg-gray-50 p-4">
                    <label class="block text-sm text-gray-700">Grupa<select wire:model="newTeam.group" class="{{ $input }}"><option value="">—</option>@foreach($groups as $miniGroup)<option value="{{ $miniGroup->id }}">{{ $miniGroup->name }}</option>@endforeach</select></label>
                    <label class="block text-sm text-gray-700">Nazwa drużyny<input type="text" wire:model="newTeam.name" class="{{ $input }}"></label>
                    <button type="submit" class="{{ $primary }}">+ Dodaj drużynę</button>
                    @error('newTeam.name')<p class="w-full text-xs text-red-600">{{ $message }}</p>@enderror
                </form>
            </div>
        @else
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Turnieje <span class="text-sm font-normal text-gray-500">({{ $trips->count() }})</span></h2>
                <button type="button" wire:click="newTrip" class="{{ $primary }}">+ Nowy turniej</button>
            </div>
            <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
                @forelse($trips as $item)
                    <li class="flex flex-wrap items-center gap-3 p-4" wire:key="trip-{{ $item->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900">{{ $item->title }}
                                @if($item->status === \App\Models\MiniTrip::STATUS_PENDING)
                                    <a href="{{ route('admin.mini-approvals.index') }}" class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">czeka na zatwierdzenie</a>
                                @elseif($item->pending_results)
                                    <a href="{{ route('admin.mini-approvals.index') }}" class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800">nowe wyniki do zatwierdzenia</a>
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">{{ $item->dateLabel() }}@if($item->city) · {{ $item->city }}@endif · {{ $item->teams->pluck('name')->implode(', ') ?: 'bez drużyn' }}</p>
                        </div>
                        @foreach($item->teams->filter(fn ($team) => $team->place) as $team)
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $team->name }}: {{ $team->place }}.</span>
                        @endforeach
                        <button type="button" wire:click="openTrip({{ $item->id }})" class="admin-action">{{ $item->isPast() ? 'Wynik i skład' : 'Otwórz' }}</button>
                    </li>
                @empty
                    <li class="p-6 text-sm text-gray-500">Nie ma jeszcze turniejów. Dodaj pierwszy.</li>
                @endforelse
            </ul>
        @endif
    @endif

    {{-- ── ZAKŁADKA GRUPY: strona grupy i baza dzieci ── --}}
    @if($section === 'grupa' && ($currentGroup = $groups->firstWhere('id', $groupTab)))
        <div class="flex flex-wrap items-center gap-4 rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200">
            <x-mini-players :group="$currentGroup" size="md" />
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold text-gray-900">{{ $currentGroup->name }}</h2>
                <p class="text-sm text-gray-500">{{ $currentGroup->ageLine() ?? 'bez kategorii wiekowej' }} · dzieci: {{ $currentGroup->players_count }}</p>
            </div>
            <button type="button" wire:click="toggleGroupVisible({{ $currentGroup->id }})" role="switch" aria-checked="{{ $currentGroup->is_active ? 'true' : 'false' }}" class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                <span @class(['relative inline-flex h-6 w-11 shrink-0 rounded-full transition', 'bg-emerald-500' => $currentGroup->is_active, 'bg-gray-300' => ! $currentGroup->is_active])><span @class(['absolute top-0.5 size-5 rounded-full bg-white shadow transition', 'left-5.5' => $currentGroup->is_active, 'left-0.5' => ! $currentGroup->is_active])></span></span>
                {{ $currentGroup->is_active ? 'Widoczna na stronie' : 'Ukryta na stronie' }}
            </button>
            <a href="{{ route('mini.show', $currentGroup) }}" target="_blank" class="text-sm font-semibold text-sky-700">Zobacz stronę ↗</a>
        </div>
        @unless($currentGroup->is_active)
            <p class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-200">Grupa jest ukryta: nie ma jej strony ani kafla pod drużynami i na stronie głównej. Włącz „Widoczna na stronie”, gdy treść będzie gotowa.</p>
        @endunless

        <div class="grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1 sm:inline-grid">
            @foreach(['strona' => 'Strona grupy', 'dzieci' => 'Dzieci ('.$currentGroup->players_count.')'] as $key => $name)
                <button type="button" wire:click="$set('groupPane', '{{ $key }}')" @class(['rounded-md px-4 py-1.5 text-sm font-semibold', 'bg-white text-gray-900 shadow' => $groupPane === $key, 'text-gray-600 hover:text-gray-900' => $groupPane !== $key])>{{ $name }}</button>
            @endforeach
        </div>

        @if($groupPane === 'strona')
        <form wire:submit="saveGroup" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingGroupId ? 'Strona grupy' : 'Nowa grupa' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="{{ $label }}">Nazwa</label><input type="text" wire:model="group.name" placeholder="np. Czwórki" class="{{ $input }}">@error('group.name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Graczy na boisku</label><select wire:model="group.players_on_court" class="{{ $input }}">@foreach([4 => 'czwórki (4)', 3 => 'trójki (3)', 2 => 'dwójki (2)', 1 => 'singielki (1)'] as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Kolor ikonek zawodników</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" wire:change="$set('group.color', $event.target.value)" value="{{ $group['color'] ?: (\App\Models\MiniGroup::DEFAULT_COLORS[(int) ($group['players_on_court'] ?? 4)] ?? '#f5b301') }}" class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1">
                        <span class="flex gap-0.5" style="color: {{ $group['color'] ?: (\App\Models\MiniGroup::DEFAULT_COLORS[(int) ($group['players_on_court'] ?? 4)] ?? '#f5b301') }}">@for($i = 0; $i < (int) ($group['players_on_court'] ?? 4); $i++)<svg viewBox="0 0 24 24" class="size-6" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7.2" r="4.6"/><path d="M3.5 22c0-5 3.8-8.6 8.5-8.6s8.5 3.6 8.5 8.6z"/></svg>@endfor</span>
                        @if($group['color'])<button type="button" wire:click="$set('group.color', '')" class="text-xs text-gray-500 underline">domyślny</button>@endif
                    </div>
                    @error('group.color') <p class="mt-1 text-xs text-red-600">Podaj kolor w formacie #RRGGBB.</p> @enderror
                </div>
                <div><label class="{{ $label }}">Kategoria wiekowa</label><input type="text" wire:model="group.age_label" placeholder="np. U-13" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Płeć (opcjonalnie)</label><select wire:model="group.gender" class="{{ $input }}"><option value="">nie pokazuj</option>@foreach(\App\Models\MiniGroup::GENDERS as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Rocznik od</label><input type="number" wire:model="group.birth_year_from" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Rocznik do</label><input type="number" wire:model="group.birth_year_to" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Kolejność</label><input type="number" wire:model="group.display_order" min="0" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Adres strony</label><input type="text" wire:model="group.slug" placeholder="np. czworki" class="{{ $input }}">@error('group.slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Jedno zdanie na kafel (opcjonalnie)</label><input type="text" wire:model="group.lead" placeholder="np. Nabór trwa, przyjdź na trening" class="{{ $input }}"></div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Zdjęcie grupy</label>
                    <div class="mt-1 flex items-center gap-3">
                        @if($groupPhoto)<img src="{{ $groupPhoto->temporaryUrl() }}" alt="" class="h-16 w-24 rounded object-cover">@elseif($group['photo_path'] ?? null)<img src="{{ \App\Support\Media::url($group['photo_path']) }}" alt="" class="h-16 w-24 rounded object-cover">@endif
                        <x-photo-cropper model="groupPhoto" :preview="false" aspect="4/3" :width="1600" />
                    </div>
                </div>
                <div class="sm:col-span-2 lg:col-span-4"><label class="{{ $label }}">O grupie (pierwsza zakładka na stronie grupy)</label><p class="mb-1 text-xs text-gray-500">Kto trenuje, kiedy i gdzie są treningi, kto prowadzi, jak dołączyć.</p><div wire:key="about-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.about" save-on-blur placeholder="Np. Treningi: wtorek i czwartek 16:00, hala przy ul. …" height="min-h-[160px]" /></div></div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Zasady gry</label><div wire:key="rules-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.rules" save-on-blur placeholder="Boisko, wysokość siatki, punktacja, zmiany…" height="min-h-[200px]" /></div></div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Historia i sukcesy (medale z turniejów dopisują się same)</label><div wire:key="history-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.history" save-on-blur placeholder="Najważniejsze wyniki, wspomnienia, wychowankowie…" height="min-h-[200px]" /></div></div>
                @unless($editingGroupId)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="group.is_active"> Widoczna na stronie</label>@endunless
            </div>
            <div class="flex justify-end gap-3">
                                <button type="submit" class="{{ $primary }}">{{ $editingGroupId ? 'Zapisz' : 'Dodaj grupę' }}</button>
            </div>
        </form>
        @else
            <p class="text-sm text-gray-600">Baza dzieci tej grupy. Drużyny i składy na konkretny turniej ustawiasz w „Turnieje i wyniki”, bo zgłasza się je przed zawodami.</p>
        <form wire:submit="savePlayer" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingPlayerId ? 'Edytuj dane dziecka' : 'Dodaj dziecko' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="{{ $label }}">Imię</label><input type="text" wire:model="player.first_name" class="{{ $input }}">@error('player.first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Nazwisko</label><input type="text" wire:model="player.last_name" class="{{ $input }}">@error('player.last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Rocznik</label><input type="number" wire:model="player.birth_year" placeholder="np. 2014" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Grupa</label><select wire:model="player.group" class="{{ $input }}"><option value="">bez grupy</option>@foreach($groups as $miniGroup)<option value="{{ $miniGroup->id }}">{{ $miniGroup->name }}</option>@endforeach</select></div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Zdjęcie</label>
                    <div class="mt-1 flex items-center gap-3">
                        @if($playerPhoto)<img src="{{ $playerPhoto->temporaryUrl() }}" alt="" class="size-16 rounded-full object-cover">@elseif($player['photo_path'] ?? null)<img src="{{ \App\Support\Media::url($player['photo_path']) }}" alt="" class="size-16 rounded-full object-cover">@endif
                        <x-photo-cropper model="playerPhoto" :preview="false" aspect="1/1" :width="600" />
                    </div>
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="player.image_consent" class="mt-0.5"> <span>Rodzic zgodził się na publikację wizerunku <span class="block text-xs text-gray-500">Bez zgody na stronie zamiast zdjęcia jest sylwetka.</span></span></label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="player.is_active"> Trenuje (nieaktywne dzieci nie są na stronie)</label>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                @if($editingPlayerId)<button type="button" wire:click="resetPlayer" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>@endif
                <button type="submit" class="{{ $primary }}">{{ $editingPlayerId ? 'Zapisz' : 'Dodaj dziecko' }}</button>
            </div>
        </form>
        <ul class="grid gap-2 sm:grid-cols-2">
            @forelse($players as $child)
                <li class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-zinc-200" wire:key="child-{{ $child->id }}">
                    @if($child->photo_path)<img src="{{ \App\Support\Media::url($child->photo_path) }}" alt="" class="size-10 rounded-full object-cover">@else<span class="flex size-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-400"><x-admin-icon name="user" /></span>@endif
                    <div class="min-w-0 flex-1">
                        <p @class(['truncate font-semibold', 'text-gray-900' => $child->is_active, 'text-gray-400' => ! $child->is_active])>{{ $child->name() }}</p>
                        <p class="text-xs text-gray-500">{{ collect([$child->birth_year, $child->group?->name])->filter()->implode(' · ') }} @unless($child->image_consent)<span class="text-amber-700">· bez zgody na zdjęcia</span>@endunless</p>
                    </div>
                    <button type="button" wire:click="editPlayer({{ $child->id }})" class="admin-action">Edytuj</button>
                    <button type="button" wire:click="deletePlayer({{ $child->id }})" wire:confirm="Usunąć {{ $child->name() }} z listy?" class="admin-action admin-action-danger">Usuń</button>
                </li>
            @empty
                <li class="rounded-lg bg-white p-4 text-sm text-gray-500 ring-1 ring-zinc-200 sm:col-span-2">Nie ma jeszcze dzieci w tej grupie. Dodaj pierwsze powyżej.</li>
            @endforelse
        </ul>
        @endif
    @endif

    {{-- ── DZIECI ── --}}
    @if($section === 'dzieci')
        <form wire:submit="savePlayer" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingPlayerId ? 'Edytuj dane dziecka' : 'Dodaj dziecko' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="{{ $label }}">Imię</label><input type="text" wire:model="player.first_name" class="{{ $input }}">@error('player.first_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Nazwisko</label><input type="text" wire:model="player.last_name" class="{{ $input }}">@error('player.last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Rocznik</label><input type="number" wire:model="player.birth_year" placeholder="np. 2014" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Grupa</label><select wire:model="player.group" class="{{ $input }}"><option value="">bez grupy</option>@foreach($groups as $miniGroup)<option value="{{ $miniGroup->id }}">{{ $miniGroup->name }}</option>@endforeach</select></div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Zdjęcie</label>
                    <div class="mt-1 flex items-center gap-3">
                        @if($playerPhoto)<img src="{{ $playerPhoto->temporaryUrl() }}" alt="" class="size-16 rounded-full object-cover">@elseif($player['photo_path'] ?? null)<img src="{{ \App\Support\Media::url($player['photo_path']) }}" alt="" class="size-16 rounded-full object-cover">@endif
                        <x-photo-cropper model="playerPhoto" :preview="false" aspect="1/1" :width="600" />
                    </div>
                </div>
                <div class="space-y-2 sm:col-span-2">
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" wire:model="player.image_consent" class="mt-0.5"> <span>Rodzic zgodził się na publikację wizerunku <span class="block text-xs text-gray-500">Bez zgody na stronie zamiast zdjęcia jest sylwetka.</span></span></label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="player.is_active"> Trenuje (nieaktywne dzieci nie są na stronie)</label>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                @if($editingPlayerId)<button type="button" wire:click="resetPlayer" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>@endif
                <button type="submit" class="{{ $primary }}">{{ $editingPlayerId ? 'Zapisz' : 'Dodaj dziecko' }}</button>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-2">
            <label class="text-sm text-gray-600">Pokaż:</label>
            <select wire:model.live="playerGroup" class="w-auto rounded-md border-gray-300 text-sm"><option value="">wszystkie dzieci</option>@foreach($groups as $miniGroup)<option value="{{ $miniGroup->id }}">{{ $miniGroup->name }}</option>@endforeach<option value="none">bez grupy</option></select>
            <span class="text-sm text-gray-500">{{ $players->count() }}</span>
        </div>
        <ul class="grid gap-2 sm:grid-cols-2">
            @foreach($players as $child)
                <li class="flex items-center gap-3 rounded-lg bg-white p-3 shadow-sm ring-1 ring-zinc-200" wire:key="child-{{ $child->id }}">
                    @if($child->photo_path)<img src="{{ \App\Support\Media::url($child->photo_path) }}" alt="" class="size-10 rounded-full object-cover">@else<span class="flex size-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-400"><x-admin-icon name="user" /></span>@endif
                    <div class="min-w-0 flex-1">
                        <p @class(['truncate font-semibold', 'text-gray-900' => $child->is_active, 'text-gray-400' => ! $child->is_active])>{{ $child->name() }}</p>
                        <p class="text-xs text-gray-500">{{ collect([$child->birth_year, $child->group?->name])->filter()->implode(' · ') }} @unless($child->image_consent)<span class="text-amber-700">· bez zgody na zdjęcia</span>@endunless</p>
                    </div>
                    <button type="button" wire:click="editPlayer({{ $child->id }})" class="admin-action">Edytuj</button>
                    <button type="button" wire:click="deletePlayer({{ $child->id }})" wire:confirm="Usunąć {{ $child->name() }} z listy?" class="admin-action admin-action-danger">Usuń</button>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- ── GRUPY ── --}}
    @if($section === 'grupy')
        @if(! $editingGroupId)
        <form wire:submit="saveGroup" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-zinc-200">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingGroupId ? 'Strona grupy' : 'Nowa grupa' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div><label class="{{ $label }}">Nazwa</label><input type="text" wire:model="group.name" placeholder="np. Czwórki" class="{{ $input }}">@error('group.name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div><label class="{{ $label }}">Graczy na boisku</label><select wire:model="group.players_on_court" class="{{ $input }}">@foreach([4 => 'czwórki (4)', 3 => 'trójki (3)', 2 => 'dwójki (2)', 1 => 'singielki (1)'] as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Kolor ikonek zawodników</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="color" wire:change="$set('group.color', $event.target.value)" value="{{ $group['color'] ?: (\App\Models\MiniGroup::DEFAULT_COLORS[(int) ($group['players_on_court'] ?? 4)] ?? '#f5b301') }}" class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1">
                        <span class="flex gap-0.5" style="color: {{ $group['color'] ?: (\App\Models\MiniGroup::DEFAULT_COLORS[(int) ($group['players_on_court'] ?? 4)] ?? '#f5b301') }}">@for($i = 0; $i < (int) ($group['players_on_court'] ?? 4); $i++)<svg viewBox="0 0 24 24" class="size-6" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7.2" r="4.6"/><path d="M3.5 22c0-5 3.8-8.6 8.5-8.6s8.5 3.6 8.5 8.6z"/></svg>@endfor</span>
                        @if($group['color'])<button type="button" wire:click="$set('group.color', '')" class="text-xs text-gray-500 underline">domyślny</button>@endif
                    </div>
                    @error('group.color') <p class="mt-1 text-xs text-red-600">Podaj kolor w formacie #RRGGBB.</p> @enderror
                </div>
                <div><label class="{{ $label }}">Kategoria wiekowa</label><input type="text" wire:model="group.age_label" placeholder="np. U-13" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Płeć (opcjonalnie)</label><select wire:model="group.gender" class="{{ $input }}"><option value="">nie pokazuj</option>@foreach(\App\Models\MiniGroup::GENDERS as $value => $name)<option value="{{ $value }}">{{ $name }}</option>@endforeach</select></div>
                <div><label class="{{ $label }}">Rocznik od</label><input type="number" wire:model="group.birth_year_from" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Rocznik do</label><input type="number" wire:model="group.birth_year_to" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Kolejność</label><input type="number" wire:model="group.display_order" min="0" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">Adres strony</label><input type="text" wire:model="group.slug" placeholder="np. czworki" class="{{ $input }}">@error('group.slug')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Jedno zdanie na kafel (opcjonalnie)</label><input type="text" wire:model="group.lead" placeholder="np. Nabór trwa, przyjdź na trening" class="{{ $input }}"></div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Zdjęcie grupy</label>
                    <div class="mt-1 flex items-center gap-3">
                        @if($groupPhoto)<img src="{{ $groupPhoto->temporaryUrl() }}" alt="" class="h-16 w-24 rounded object-cover">@elseif($group['photo_path'] ?? null)<img src="{{ \App\Support\Media::url($group['photo_path']) }}" alt="" class="h-16 w-24 rounded object-cover">@endif
                        <x-photo-cropper model="groupPhoto" :preview="false" aspect="4/3" :width="1600" />
                    </div>
                </div>
                <div class="sm:col-span-2 lg:col-span-4"><label class="{{ $label }}">O grupie (pierwsza zakładka na stronie grupy)</label><p class="mb-1 text-xs text-gray-500">Kto trenuje, kiedy i gdzie są treningi, kto prowadzi, jak dołączyć.</p><div wire:key="about-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.about" save-on-blur placeholder="Np. Treningi: wtorek i czwartek 16:00, hala przy ul. …" height="min-h-[160px]" /></div></div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Zasady gry</label><div wire:key="rules-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.rules" save-on-blur placeholder="Boisko, wysokość siatki, punktacja, zmiany…" height="min-h-[200px]" /></div></div>
                <div class="sm:col-span-2"><label class="{{ $label }}">Historia i sukcesy (medale z turniejów dopisują się same)</label><div wire:key="history-{{ $editingGroupId ?? 'new' }}"><x-rich-text-editor model="group.history" save-on-blur placeholder="Najważniejsze wyniki, wspomnienia, wychowankowie…" height="min-h-[200px]" /></div></div>
                @unless($editingGroupId)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="group.is_active"> Widoczna na stronie</label>@endunless
            </div>
            <div class="flex justify-end gap-3">
                                <button type="submit" class="{{ $primary }}">{{ $editingGroupId ? 'Zapisz' : 'Dodaj grupę' }}</button>
            </div>
        </form>
        @endif

        <ul class="divide-y divide-gray-200 overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
            @forelse($groups as $miniGroup)
                <li class="flex flex-wrap items-center gap-3 p-4" wire:key="mini-group-{{ $miniGroup->id }}">
                    <x-mini-players :group="$miniGroup" size="sm" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $miniGroup->name }} @unless($miniGroup->is_active)<span class="text-xs font-normal text-gray-400">(ukryta)</span>@endunless</p>
                        <p class="text-sm text-gray-500">{{ $miniGroup->ageLine() ?? 'bez kategorii' }} · dzieci: {{ $miniGroup->players_count }}</p>
                    </div>
                    <a href="{{ route('mini.show', $miniGroup) }}" target="_blank" class="text-sm font-semibold text-sky-700">Strona ↗</a>
                    <button type="button" wire:click="openGroup({{ $miniGroup->id }})" class="admin-action">Otwórz</button>
                    <button type="button" wire:click="deleteGroup({{ $miniGroup->id }})" wire:confirm="Usunąć grupę {{ $miniGroup->name }}?" class="admin-action admin-action-danger">Usuń</button>
                </li>
            @empty
                <li class="p-6 text-sm text-gray-500">Nie ma jeszcze grup. Dodaj np. Czwórki, Trójki, Dwójki i Singielki.</li>
            @endforelse
        </ul>
    @endif
</div>
