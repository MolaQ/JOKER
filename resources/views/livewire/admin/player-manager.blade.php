<div>
    <!-- Toolbar -->
    <div class="space-y-3 border-b border-gray-200 bg-white p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj: nazwisko, imię albo numer (np. 7)"
                class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
            >
            <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center gap-1.5 rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                <x-admin-icon name="plus" class="h-4 w-4" /> Dodaj zawodnika
            </button>
        </div>
        @php
            $chip = 'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium ring-1 ring-inset transition';
            $on = 'bg-sky-600 text-white ring-sky-600';
            $off = 'bg-white text-gray-700 ring-gray-300 hover:bg-gray-50';
            $count = fn ($n) => '<span class="rounded-full bg-black/10 px-1.5 text-xs tabular-nums">'.(int) $n.'</span>';
        @endphp
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Drużyna">
            <span class="w-16 text-xs font-semibold uppercase tracking-wide text-gray-500">Drużyna</span>
            <button type="button" wire:click="$set('teamFilter', '')" class="{{ $chip }} {{ $teamFilter === '' ? $on : $off }}">Wszystkie {!! $count($totalCount) !!}</button>
            @foreach($teams as $team)
                <button type="button" wire:key="team-chip-{{ $team->id }}" wire:click="$set('teamFilter', '{{ $team->id }}')" class="{{ $chip }} {{ $teamFilter === (string) $team->id ? $on : $off }}">{{ $team->name }} {!! $count($teamCounts[$team->id] ?? 0) !!}</button>
            @endforeach
            @if(($teamCounts[''] ?? 0) > 0)
                <button type="button" wire:click="$set('teamFilter', 'brak')" class="{{ $chip }} {{ $teamFilter === 'brak' ? $on : $off }}">Bez drużyny {!! $count($teamCounts['']) !!}</button>
            @endif
        </div>
        <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Pozycja">
            <span class="w-16 text-xs font-semibold uppercase tracking-wide text-gray-500">Pozycja</span>
            <button type="button" wire:click="$set('positionFilter', '')" class="{{ $chip }} {{ $positionFilter === '' ? $on : $off }}">Wszystkie</button>
            @foreach($positions as $pos)
                <button type="button" wire:key="pos-chip-{{ $pos->value }}" wire:click="$set('positionFilter', '{{ $pos->value }}')" class="{{ $chip }} {{ $positionFilter === $pos->value ? $on : $off }}">{{ $pos->label() }} {!! $count($positionCounts[$pos->value] ?? 0) !!}</button>
            @endforeach
            @if(($positionCounts[''] ?? 0) > 0)
                <button type="button" wire:click="$set('positionFilter', 'brak')" class="{{ $chip }} {{ $positionFilter === 'brak' ? $on : $off }}">Bez pozycji {!! $count($positionCounts['']) !!}</button>
            @endif
        </div>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"><x-sort-button field="numer" :current="$sortField" :direction="$sortDirection">#</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="nazwisko" :current="$sortField" :direction="$sortDirection">Imię i nazwisko</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="pozycja" :current="$sortField" :direction="$sortDirection">Pozycja</x-sort-button></th>
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
                        @if($player->user_id)<span class="ml-1 inline-flex align-middle text-sky-600" title="Ma konto na stronie"><x-admin-icon name="user" class="h-4 w-4" /></span>@endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $player->position?->label() ?? '—' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if($player->team){{ $player->team->name }}@foreach($player->teams->reject(fn ($extra) => $extra->id === $player->team_id) as $extra)<span class="text-gray-400"> + {{ $extra->name }}</span>@endforeach@else<span class="rounded bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-700">bez drużyny</span>@endif
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
                    <td colspan="7" class="px-3 py-4 text-sm text-gray-500 text-center">Brak zawodników do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $players->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-2xl" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj zawodnika' : 'Dodaj zawodnika' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4">
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
                                <select wire:model.live="team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">{{ auth()->user()->managedTeamIds() === null ? '— bez drużyny (uzupełnisz później) —' : 'Wybierz drużynę' }}</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                                @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                <label class="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="is_captain" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                    Kapitan drużyny w bieżącym sezonie
                                </label>
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
                                    <label class="block text-sm font-medium text-gray-700">Osiągnięcia</label>
                                    <button type="button" wire:click="addAchievement" class="text-sm font-semibold text-sky-700 hover:underline">+ Dodaj</button>
                                </div>
                                @if($editingId && ($fromTournaments = \App\Models\PlayerAchievement::where('player_id', $editingId)->whereNotNull('tournament_award_id')->get())->isNotEmpty())
                                    <ul class="mt-1 space-y-1 text-sm text-gray-700">
                                        @foreach($fromTournaments as $auto)
                                            <li class="flex gap-2 rounded-md bg-amber-50 px-2 py-1"><span class="text-amber-600">★</span><span class="w-20 shrink-0 text-gray-500">{{ $auto->season }}</span><span class="min-w-0 flex-1">{{ $auto->title }}</span><span class="text-xs text-gray-500">z turnieju</span></li>
                                        @endforeach
                                    </ul>
                                @endif
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
                                <p class="text-xs text-gray-500 mb-2">Zaznacz, jeśli zawodnik dogrywa również w innej drużynie (np. młodszy zawodnik grywający ze starszym rocznikiem).</p>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border border-gray-200 rounded-md p-2">
                                    @foreach($teams as $team)
                                        @if($team->id !== $team_id)
                                            <label class="flex items-center text-sm text-gray-700">
                                                <input type="checkbox" wire:model="extra_team_ids" value="{{ $team->id }}" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500 mr-2">
                                                {{ $team->name }}
                                            </label>
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
