<div>
    <!-- Toolbar -->
    <div class="space-y-3 border-b border-gray-200 bg-white p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Szukaj zawodnika</span>
                <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Szukaj: nazwisko, imię albo numer (np. 7)"
                    class="w-full rounded-xl border-zinc-200 bg-zinc-50 py-2.5 pl-9 text-sm focus:bg-white"
                >
            </label>
            <button type="button" wire:click="openCreateModal" class="admin-fab inline-flex shrink-0 items-center justify-center gap-1.5 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                <x-admin-icon name="plus" class="h-4 w-4" /> Dodaj zawodnika
            </button>
        </div>
        @php
            // Ten sam wygląd co wspólne filtry list (components/filters): spokojne „Wszystkie”, niebieskie działające filtry.
            $chip = 'inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1.5 text-[13px] font-medium whitespace-nowrap ring-1 ring-inset transition active:scale-[0.97]';
            $on = 'bg-sky-600 text-white ring-sky-600';
            $off = 'bg-white text-zinc-600 ring-zinc-200 hover:bg-zinc-50 hover:text-zinc-900';
            $calm = 'bg-zinc-100 font-semibold text-zinc-900 ring-zinc-200';
            $count = fn ($n) => '<span class="rounded-full bg-black/[0.07] px-1.5 text-[11px] tabular-nums">'.(int) $n.'</span>';
        @endphp
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Drużyna">
            <span class="w-20 text-[11px] font-bold tracking-wider text-zinc-500 uppercase">Drużyna</span>
            <button type="button" wire:click="$set('teamFilter', '')" class="{{ $chip }} {{ $teamFilter === '' ? $calm : $off }}">Wszystkie {!! $count($totalCount) !!}</button>
            @foreach($teams as $team)
                <button type="button" wire:key="team-chip-{{ $team->id }}" wire:click="$set('teamFilter', '{{ $team->id }}')" class="{{ $chip }} {{ $teamFilter === (string) $team->id ? $on : $off }}"><span class="size-2.5 rounded-full" style="background: {{ \App\Support\AgeColors::forTeam($team) }}" aria-hidden="true"></span>{{ $team->name }} {!! $count($teamCounts[$team->id] ?? 0) !!}</button>
            @endforeach
            @if(($teamCounts['mini'] ?? 0) > 0)
                <button type="button" wire:click="$set('teamFilter', 'mini')" class="{{ $chip }} {{ $teamFilter === 'mini' ? $on : $off }}">Minisiatkówka {!! $count($teamCounts['mini']) !!}</button>
            @endif
            @if(($teamCounts[''] ?? 0) > 0)
                <button type="button" wire:click="$set('teamFilter', 'brak')" class="{{ $chip }} {{ $teamFilter === 'brak' ? $on : $off }}">Bez drużyny {!! $count($teamCounts['']) !!}</button>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Pozycja">
            <span class="w-20 text-[11px] font-bold tracking-wider text-zinc-500 uppercase">Pozycja</span>
            <button type="button" wire:click="$set('positionFilter', '')" class="{{ $chip }} {{ $positionFilter === '' ? $calm : $off }}">Wszystkie</button>
            @foreach($positions as $pos)
                <button type="button" wire:key="pos-chip-{{ $pos->value }}" wire:click="$set('positionFilter', '{{ $pos->value }}')" class="{{ $chip }} {{ $positionFilter === $pos->value ? $on : $off }}">{{ $pos->label() }} {!! $count($positionCounts[$pos->value] ?? 0) !!}</button>
            @endforeach
            @if(($positionCounts[''] ?? 0) > 0)
                <button type="button" wire:click="$set('positionFilter', 'brak')" class="{{ $chip }} {{ $positionFilter === 'brak' ? $on : $off }}">Bez pozycji {!! $count($positionCounts['']) !!}</button>
            @endif
        </div>
        @php
            $definitions = \App\Livewire\Admin\PlayerManager::filterDefinitions();
            $months = ['', 'styczeń', 'luty', 'marzec', 'kwiecień', 'maj', 'czerwiec', 'lipiec', 'sierpień', 'wrzesień', 'październik', 'listopad', 'grudzień'];
            $activeFilters = $this->activeFiltersCount();
        @endphp
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" wire:click="$toggle('showFilters')" aria-expanded="{{ $showFilters ? 'true' : 'false' }}" class="{{ $chip }} {{ $showFilters || $activeFilters ? 'bg-sky-50 text-sky-800 ring-sky-300' : $off }}">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3 4.75A.75.75 0 0 1 3.75 4h12.5a.75.75 0 0 1 .55 1.26L12 10.43v4.82a.75.75 0 0 1-.42.67l-2.5 1.25A.75.75 0 0 1 8 16.5v-6.07L3.2 5.26A.75.75 0 0 1 3 4.75Z"/></svg>
                Więcej filtrów @if($activeFilters){!! $count($activeFilters) !!}@endif
                <svg @class(['size-4 transition', 'rotate-180' => $showFilters]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
            </button>
            {{-- Włączone filtry jako etykiety z krzyżykiem --}}
            @foreach($filters as $key => $value)
                @if(isset($definitions[$key]) && in_array($value, ['tak', 'nie'], true))
                    <button type="button" wire:key="active-filter-{{ $key }}" wire:click="$set('filters.{{ $key }}', '')" class="{{ $chip }} bg-sky-600 text-white ring-sky-600" title="Usuń filtr">
                        {{ $definitions[$key]['label'] }}: {{ $value === 'tak' ? ($definitions[$key]['yes'] ?? 'jest') : ($definitions[$key]['no'] ?? 'brak') }} <span aria-hidden="true">×</span>
                    </button>
                @endif
            @endforeach
            @if($birthMonth !== '')
                <button type="button" wire:click="$set('birthMonth', '')" class="{{ $chip }} bg-sky-600 text-white ring-sky-600">Urodziny: {{ $months[(int) $birthMonth] ?? $birthMonth }} <span aria-hidden="true">×</span></button>
            @endif
            @if($yearFrom !== '' || $yearTo !== '')
                <button type="button" wire:click="clearYears" class="{{ $chip }} bg-sky-600 text-white ring-sky-600">Rocznik {{ $yearFrom ?: '…' }}–{{ $yearTo ?: '…' }} <span aria-hidden="true">×</span></button>
            @endif
            @if($activeFilters)
                <button type="button" wire:click="clearFilters" class="text-sm font-medium text-sky-700 hover:underline">Wyczyść filtry</button>
            @endif
        </div>
        @if($showFilters)
            <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200">
                <div class="grid gap-x-8 gap-y-5 md:grid-cols-2 xl:grid-cols-3">
                    <fieldset>
                        <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500">Urodziny</legend>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <label class="col-span-2 text-sm text-gray-700">Urodziny w miesiącu
                                <select wire:model.live="birthMonth" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">dowolny</option>
                                    @foreach(range(1, 12) as $month)<option value="{{ $month }}">{{ $months[$month] }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-sm text-gray-700">Rocznik od<input type="number" inputmode="numeric" min="1950" max="2100" wire:model.live.debounce.400ms="yearFrom" placeholder="2008" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"></label>
                            <label class="text-sm text-gray-700">do<input type="number" inputmode="numeric" min="1950" max="2100" wire:model.live.debounce.400ms="yearTo" placeholder="2010" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"></label>
                        </div>
                    </fieldset>
                    @foreach(collect($definitions)->groupBy('section', true) as $section => $items)
                        <fieldset>
                            <legend class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $section }}</legend>
                            <div class="mt-2 divide-y divide-gray-200 rounded-lg bg-white ring-1 ring-gray-200">
                                @foreach($items as $key => $definition)
                                    @php $current = $filters[$key] ?? ''; @endphp
                                    <div wire:key="filter-row-{{ $key }}" class="flex items-center justify-between gap-3 px-3 py-2">
                                        <span class="min-w-0 text-sm text-gray-800">{{ $definition['label'] }}</span>
                                        <div class="inline-flex shrink-0 rounded-md bg-gray-100 p-0.5 text-xs font-semibold" role="radiogroup" aria-label="{{ $definition['label'] }}">
                                            @foreach(['' => 'Wszyscy', 'tak' => $definition['yes'] ?? 'Jest', 'nie' => $definition['no'] ?? 'Brak'] as $option => $optionLabel)
                                                <button type="button" role="radio" aria-checked="{{ $current === $option ? 'true' : 'false' }}" wire:click="$set('filters.{{ $key }}', '{{ $option }}')" @class(['rounded px-2 py-1 transition', 'bg-white text-gray-900 shadow-sm' => $current === $option, 'text-gray-500 hover:text-gray-800' => $current !== $option])>
                                                    {{ $optionLabel }}@if($option === 'nie' && ($missingCounts[$key] ?? 0) > 0 && $current !== 'tak') <span class="tabular-nums text-amber-700">{{ $missingCounts[$key] }}</span>@endif
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="md:overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"><x-sort-button field="numer" :current="$sortField" :direction="$sortDirection">#</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><span class="inline-flex items-center gap-3"><x-sort-button field="nazwisko" :current="$sortField" :direction="$sortDirection">Imię i nazwisko</x-sort-button><x-sort-button field="pozycja" :current="$sortField" :direction="$sortDirection">Pozycja</x-sort-button></span></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="druzyna" :current="$sortField" :direction="$sortDirection">Drużyna</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="status" :current="$sortField" :direction="$sortDirection">Status</x-sort-button></th>
                <th data-label="Wyświetlenia i polubienia" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="wyswietlenia" hint="Wyświetlenia" :current="$sortField" :direction="$sortDirection"><svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.57 3 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.57-3-9.96-7.18Z"/><circle cx="12" cy="12" r="3"/></svg><span class="sr-only">Wyświetlenia</span></x-sort-button> <x-sort-button field="polubienia" hint="Polubienia" :current="$sortField" :direction="$sortDirection"><x-heart :filled="false" class="text-zinc-400" /><span class="sr-only">Polubienia</span></x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="logowanie" :current="$sortField" :direction="$sortDirection">Ostatnie logowanie</x-sort-button></th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($players as $player)
                <tr wire:key="player-{{ $player->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">
                        {{ $player->jersey_number ?? '—' }}
                        @if(in_array($player->team_id.'-'.$player->jersey_number, $duplicateNumbers, true))
                            <span class="ml-1 inline-flex align-middle text-amber-500" title="Ten numer ma w drużynie jeszcze ktoś: popraw jednego z zawodników"><x-admin-icon name="flag" class="h-4 w-4" /></span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                        {{ $player->full_name }}
                        @if(in_array($player->id, $duplicatePeople, true))
                            <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800" title="Ta sama osoba jest wpisana drugi raz. Otwórz Edytuj i scal profile.">duplikat?</span>
                        @endif
                        @if($player->user_id)<span class="ml-1 inline-flex align-middle text-sky-600" title="Ma konto na stronie"><x-admin-icon name="user" class="h-4 w-4" /></span>@endif
                        <span class="block text-xs font-normal text-zinc-500">{{ $player->position?->label() ?? 'bez pozycji' }}</span>
                        @if($birthMonth !== '' || $yearFrom !== '' || $yearTo !== '' || isset($filters['urodzenie']) || isset($filters['rocznik']))
                            <span class="block text-xs font-normal text-gray-500">{{ $player->birth_date?->format('d.m.Y') ?? ($player->birth_year ? 'rocznik '.$player->birth_year : 'brak daty urodzenia') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if($player->team){{ $player->team->name }}@foreach($player->teams->reject(fn ($extra) => $extra->id === $player->team_id) as $extra)<span class="text-gray-400"> + {{ $extra->name }}</span>@endforeach@elseif($player->isMini() && $player->miniGroup)<span class="rounded bg-emerald-50 px-1.5 py-0.5 text-xs font-semibold text-emerald-700">mini: {{ $player->miniGroup->name }}</span>@else<span class="rounded bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700">bez drużyny</span>@endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($player->is_active)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktywny</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nieaktywny</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <x-admin-reception :views="$player->views_count" :likes="$player->likes_count" />
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if(! $player->user)
                            <span class="text-gray-300">—</span>
                        @elseif($player->user->last_login_at)
                            <span title="{{ $player->user->last_login_at->format('d.m.Y H:i') }}">{{ $player->user->last_login_at->diffForHumans() }}</span>
                        @else
                            <span class="text-gray-400">jeszcze nie</span>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $player->id }})" class="text-sky-700 hover:text-sky-900 mr-4">Edytuj</button>
                        <button
                            type="button"
                            wire:click="delete({{ $player->id }})"
                            wire:confirm="Czy na pewno chcesz usunąć zawodnika {{ $player->full_name }}?"
                            class="text-red-600 hover:text-red-900"
                        >Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">Brak zawodników do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="mt-4 px-4 pb-4">
        {{ $players->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="admin-sheet flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-2xl" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj zawodnika' : 'Dodaj zawodnika' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4">
                            @if($editingId && ($mergeCandidates = \App\Support\PlayerMerge::candidates(\App\Models\Player::find($editingId)))->isNotEmpty())
                                <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm">
                                    <p class="font-semibold text-amber-900">Ta sama osoba jest wpisana jeszcze raz</p>
                                    <p class="mt-0.5 text-xs text-amber-800">Scalenie przenosi do tego profilu drużynę (jako dodatkową, z numerem), nagrody, osiągnięcia, zdjęcia, serduszka i rodziców, a drugi wpis trafia do kosza. Stary adres profilu prowadzi tutaj.</p>
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach($mergeCandidates as $candidate)
                                            <li class="flex flex-wrap items-center justify-between gap-2">
                                                <span>{{ $candidate->full_name }} · {{ $candidate->team?->name ?? 'bez drużyny' }}@if($candidate->jersey_number) · #{{ $candidate->jersey_number }}@endif</span>
                                                <button type="button" wire:click="mergeDuplicate({{ $candidate->id }})" wire:confirm="Scalić {{ $candidate->full_name }} ({{ $candidate->team?->name ?? 'bez drużyny' }}) z tym profilem? Zapisz najpierw zmiany w formularzu." class="rounded-md bg-amber-500 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-600">Scal z tym profilem</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Imię</label>
                                    <input type="text" wire:model="first_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nazwisko</label>
                                    <input type="text" wire:model="last_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('last_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Numer</label>
                                    <input type="number" wire:model.blur="jersey_number" min="0" max="99" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('jersey_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @if(! $errors->has('jersey_number') && ($taken = $this->numberTakenBy()))
                                        <p class="mt-1 text-xs text-red-600">Ten numer w drużynie ma już {{ implode(', ', $taken) }}.</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Pozycja główna</label>
                                    <select wire:model.live="position" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">— jeszcze nie wiadomo —</option>
                                        @foreach($positions as $pos)
                                            <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('position') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Gra też na pozycji</label>
                                <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                    @foreach($positions as $pos)
                                        @continue($pos->value === $position)
                                        <label class="flex items-center text-sm text-gray-700" wire:key="extra-position-{{ $pos->value }}">
                                            <input type="checkbox" wire:model="extra_positions" value="{{ $pos->value }}" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500 mr-2">
                                            {{ $pos->label() }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                                {{-- Drużyny, a pod nimi grupy minisiatkówki (dziecko z mini nie ma drużyny, tylko grupę). --}}
                                <select wire:model.live="group" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">{{ auth()->user()->managedTeamIds() === null ? '— bez drużyny (uzupełnisz później) —' : 'Wybierz drużynę' }}</option>
                                    @if($miniGroups->isNotEmpty())<optgroup label="Drużyny">@endif
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                    @if($miniGroups->isNotEmpty())
                                        </optgroup>
                                        <optgroup label="Minisiatkówka">
                                            @foreach($miniGroups as $miniGroup)
                                                <option value="mini-{{ $miniGroup->id }}">Minisiatkówka: {{ $miniGroup->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                                @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @error('mini_group_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @if($mini_group_id)
                                    <p class="mt-1 text-xs text-emerald-700">Dziecko z minisiatkówki: jest na stronie grupy, a zdjęcie pokaże się tylko za zgodą rodzica. Gdy przejdzie do drużyny, wybierz ją tutaj.</p>
                                @endif
@unless($mini_group_id)
                                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="is_captain" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                    Kapitan drużyny w bieżącym sezonie
                                </label>
                                @endunless
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wzrost (cm)</label>
                                    <input type="number" wire:model="height" min="100" max="250" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('height') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Waga (kg)</label>
                                    <input type="number" wire:model="weight" min="30" max="150" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('weight') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data urodzenia</label>
                                    <input type="date" wire:model="birth_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('birth_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zasięg w staniu (cm)</label>
                                    <input type="number" wire:model="reach" min="150" max="400" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('reach') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zasięg w ataku (cm)</label>
                                    <input type="number" wire:model="spike_reach" min="150" max="400" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('spike_reach') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Zasięg w bloku (cm)</label>
                                    <input type="number" wire:model="block_reach" min="150" max="400" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('block_reach') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Urodziny w kalendarzu</label>
                                    <select wire:model="birthday_display" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        @foreach(\App\BirthdayDisplay::cases() as $mode)
                                            <option value="{{ $mode->value }}">{{ $mode->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <fieldset class="rounded-xl border border-gray-200 p-4">
                                <legend class="px-1 text-sm font-semibold text-gray-900">Konto i rodzice</legend>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Konto zawodnika</label>
                                        <select wire:model.live="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            <option value="">— brak —</option>
                                            @foreach($playerAccounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->email }})</option>
                                            @endforeach
                                        </select>
                                        @error('user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        @if($linkedAccount)
                                            <p class="mt-1 text-xs text-gray-500">Loguje się jako {{ $linkedAccount->email }}. Uprawnienia (np. komentowanie) zmienisz w „Użytkownicy”, zakładka „Uprawnienia”.</p>
                                        @else
                                            <label class="mt-3 block text-sm font-medium text-gray-700">albo e-mail zawodnika</label>
                                            <input type="email" wire:model="account_email" placeholder="np. adam.nowak@gmail.com" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                            @error('account_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            <p class="mt-1 text-xs text-gray-500">Po zapisaniu zawodnik dostanie konto i link do ustawienia hasła. Będzie mógł się logować, komentować i proponować zmiany profilu.</p>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Rodzice (może być kilku)</label>
                                        <div class="mt-1 max-h-28 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
                                            @forelse($parentAccounts as $account)
                                                <label class="flex items-center text-sm text-gray-700" wire:key="guardian-{{ $account->id }}">
                                                    <input type="checkbox" wire:model="guardian_ids" value="{{ $account->id }}" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500 mr-2">
                                                    {{ $account->name }}
                                                </label>
                                            @empty
                                                <p class="text-xs text-gray-500">Brak kont z rolą „Rodzic”.</p>
                                            @endforelse
                                        </div>
                                        <div class="mt-2 space-y-2">
                                            @foreach($new_guardians as $index => $guardian)
                                                <div class="flex gap-2" wire:key="new-guardian-{{ $index }}">
                                                    <input type="text" wire:model="new_guardians.{{ $index }}.name" placeholder="Imię i nazwisko" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                                                    <input type="email" wire:model="new_guardians.{{ $index }}.email" placeholder="e-mail" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                                                    <button type="button" wire:click="removeGuardian({{ $index }})" class="px-1 text-gray-400 hover:text-red-600" aria-label="Usuń">✕</button>
                                                </div>
                                                @error('new_guardians.'.$index.'.email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                            @endforeach
                                            <button type="button" wire:click="addGuardian" class="text-sm font-semibold text-sky-700 hover:underline">+ Dodaj rodzica po e-mailu</button>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Rodzic bez konta dostanie je po zapisaniu, z linkiem do ustawienia hasła.</p>
                                    </div>
                                </div>
                            </fieldset>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700">Nagrody indywidualne</label>
                                    <button type="button" wire:click="addAward" class="text-sm font-semibold text-sky-700 hover:underline">+ Dodaj</button>
                                </div>
                                <p class="text-xs text-gray-500">Kolejność jak w profilu (strzałki). Nagrody z naszych turniejów (★) trafiają tu same z sekcji „Wyróżnienia” turnieju; tu możesz je poprawić, np. dopisać miejsce.</p>
                                <div class="mt-2 space-y-3">
                                    @foreach($awards as $index => $award)
                                        <div class="flex gap-2 rounded-md border border-amber-200 bg-amber-50/50 p-2" wire:key="award-{{ $award['id'] ?? 'new-'.$index }}">
                                            <div class="flex shrink-0 flex-col items-center gap-1 pt-1">
                                                <button type="button" wire:click="moveAward({{ $index }}, -1)" @disabled($loop->first) class="text-gray-400 hover:text-sky-700 disabled:opacity-30" aria-label="Wyżej">▲</button>
                                                @if($award['tournament'])<span class="text-amber-500" title="Z turnieju na stronie">★</span>@endif
                                                <button type="button" wire:click="moveAward({{ $index }}, 1)" @disabled($loop->last) class="text-gray-400 hover:text-sky-700 disabled:opacity-30" aria-label="Niżej">▼</button>
                                            </div>
                                            <div class="grid min-w-0 flex-1 gap-2 sm:grid-cols-2">
                                                <input type="text" wire:model="awards.{{ $index }}.name" placeholder="Wyróżnienie, np. MVP turnieju" class="min-w-0 rounded-md border-gray-300 text-sm">
                                                <input type="text" wire:model="awards.{{ $index }}.event" placeholder="Impreza, np. Turniej o Puchar Prezydenta" class="min-w-0 rounded-md border-gray-300 text-sm">
                                                <input type="date" wire:model="awards.{{ $index }}.date" class="min-w-0 rounded-md border-gray-300 text-sm" aria-label="Data">
                                                <input type="text" wire:model="awards.{{ $index }}.place" placeholder="Miejsce, np. Kalisz" class="min-w-0 rounded-md border-gray-300 text-sm">
                                                <input type="text" wire:model="awards.{{ $index }}.note" placeholder="Dopisek, np. kategoria i drużyna (opcjonalnie)" class="min-w-0 rounded-md border-gray-300 text-sm sm:col-span-2">
                                            </div>
                                            <button type="button" wire:click="removeAward({{ $index }})" class="self-start px-1 text-gray-400 hover:text-red-600" aria-label="Usuń nagrodę">✕</button>
                                        </div>
                                        @error("awards.{$index}.date") <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="block text-sm font-medium text-gray-700">Osiągnięcia</label>
                                    <button type="button" wire:click="addAchievement" class="text-sm font-semibold text-sky-700 hover:underline">+ Dodaj</button>
                                </div>
                                <div class="mt-1 space-y-2">
                                    @foreach($achievements as $index => $achievement)
                                        <div class="flex gap-2" wire:key="achievement-{{ $index }}">
                                            <input type="text" wire:model="achievements.{{ $index }}.season" placeholder="Sezon, np. 2025/26" class="w-32 rounded-md border-gray-300 text-sm">
                                            <input type="text" wire:model="achievements.{{ $index }}.title" placeholder="np. Mistrzostwo Wielkopolski kadetów" class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                                            <button type="button" wire:click="removeAchievement({{ $index }})" class="px-2 text-gray-400 hover:text-red-600" aria-label="Usuń osiągnięcie">✕</button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dodatkowe drużyny w bieżącym sezonie</label>
                                <p class="text-xs text-gray-500 mb-2">Zaznacz, jeśli zawodnik dogrywa również w innej drużynie (np. młodszy zawodnik grywający ze starszym rocznikiem). Obok wpisz numer, z którym tam gra.</p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-2">
                                    @foreach($teams as $team)
                                        @if($team->id !== $team_id)
                                            <div class="flex items-center gap-2 text-sm text-gray-700" x-data="{ on: $wire.extra_team_ids.map(String).includes('{{ $team->id }}') }">
                                                <label class="flex min-w-0 flex-1 items-center">
                                                    <input type="checkbox" wire:model="extra_team_ids" value="{{ $team->id }}" x-on:change="on = $el.checked" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500 mr-2">
                                                    <span class="truncate">{{ $team->name }}</span>
                                                </label>
                                                <input type="number" min="0" max="99" x-show="on" x-cloak wire:model="extra_team_numbers.{{ $team->id }}" placeholder="nr" aria-label="Numer w drużynie {{ $team->name }}" class="w-16 rounded-md border border-gray-300 px-2 py-1 text-sm">
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kilka słów o zawodniku</label>
                                <x-rich-text-editor model="bio" placeholder="Biografia zawodnika..." height="min-h-[170px]" />
                                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zainteresowania</label>
                                <input type="text" wire:model="interests" maxlength="500" placeholder="np. gitara, góry, matematyka" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <p class="mt-1 text-xs text-gray-500">Kilka słów rozdzielonych przecinkami; w profilu pokażą się jako etykiety.</p>
                                @error('interests') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            @if($socialPlatforms)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Media społecznościowe</label>
                                    <div class="mt-1 grid gap-2 sm:grid-cols-2">
                                        @foreach($socialPlatforms as $platformKey => $platform)
                                            <div wire:key="social-{{ $platformKey }}">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700" title="{{ $platform['label'] }}"><x-social-icon :network="$platform['icon']" class="h-4 w-4" /></span>
                                                    <input type="url" wire:model="social_links.{{ $platformKey }}" placeholder="{{ $platform['label'] }}: https://..." class="min-w-0 flex-1 rounded-md border-gray-300 text-sm">
                                                </div>
                                                @error('social_links.'.$platformKey) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            </div>
                                        @endforeach
                                    </div>
                                    @can('system')<p class="mt-1 text-xs text-gray-500">Listę serwisów i ikony ustawisz w <a href="{{ route('admin.settings.index') }}" class="text-sky-700 underline">System → Profile zawodników</a>.</p>@endcan
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zdjęcie</label>
                                <x-photo-cropper model="photo" class="mt-1" :current="$existingPhoto ? \App\Support\Media::url($existingPhoto) : null" :pending="$photo && ! $errors->has('photo') ? $photo->temporaryUrl() : null" />
                                @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="player_is_active" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                <label for="player_is_active" class="ml-2 text-sm text-gray-700">Zawodnik aktywny</label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="image_consent" id="player_image_consent" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                <label for="player_image_consent" class="ml-2 text-sm text-gray-700">Zgoda na wizerunek (oznaczenia na zdjęciach widoczne)</label>
                            </div>
                        </div>

                        <div class="sticky bottom-0 z-10 px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg border-t border-gray-200">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                                <span wire:loading.remove wire:target="save">Zapisz</span>
                                <span wire:loading wire:target="save">Zapisywanie...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
