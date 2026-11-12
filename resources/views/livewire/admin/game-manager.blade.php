<div>
    @php
        $teamColors = $teams->mapWithKeys(fn ($team) => [$team->id => \App\Support\AgeColors::forTeam($team)])->all();
    @endphp
    <x-filters.bar :total="$games->total()" :nouns="['mecz', 'mecze', 'meczów']" :active="$this->activeFiltersCount()" placeholder="Szukaj: przeciwnik, hala, liga, kolejka, drużyna…">
        <x-slot:actions>
            <button type="button" wire:click="openCreateModal" class="admin-fab inline-flex shrink-0 items-center justify-center rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                + Dodaj mecz
            </button>
        </x-slot:actions>

        <x-filters.chips class="@4xl:col-span-2" label="Drużyna" field="teamFilter" :current="$teamFilter" :options="$teams->pluck('name', 'id')->all()" :colors="$teamColors" />
        <x-filters.dates class="@4xl:col-span-2" label="Data" :preset="$this->activeDatePreset()" />
        <x-filters.chips label="Rodzaj" field="kindFilter" :current="$kindFilter" :options="\App\Models\Game::KINDS" />
        <x-filters.chips label="Miejsce" field="placeFilter" :current="$placeFilter" :options="['home' => 'U siebie', 'away' => 'Na wyjeździe']" />
        <x-filters.chips label="Status" field="statusFilter" :current="$statusFilter" :options="['scheduled' => 'Zaplanowany', 'live' => 'Na żywo', 'finished' => 'Zakończony', 'cancelled' => 'Odwołany']" />
        <x-filters.chips label="Wynik" field="resultFilter" :current="$resultFilter" :options="\App\Livewire\Admin\GameManager::RESULT_FILTERS" />
        <x-filters.chips label="Materiały" field="mediaFilter" :current="$mediaFilter" :options="\App\Livewire\Admin\GameManager::MEDIA_FILTERS" />
        @if($seasons->count() > 1)
            <x-filters.chips label="Sezon" field="seasonFilter" :current="$seasonFilter" :options="$seasons->pluck('name', 'id')->all()" />
        @endif
    </x-filters.bar>

    <div class="md:overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6"><x-sort-button field="data" :current="$sortField" :direction="$sortDirection">Data</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="druzyna" :current="$sortField" :direction="$sortDirection">Drużyna</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="przeciwnik" :current="$sortField" :direction="$sortDirection">Przeciwnik</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="wynik" hint="Wynik (różnica setów naszej drużyny)" :current="$sortField" :direction="$sortDirection">Wynik</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="status" :current="$sortField" :direction="$sortDirection">Status</x-sort-button></th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900"><x-sort-button field="materialy" :current="$sortField" :direction="$sortDirection">Materiały</x-sort-button></th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($games as $game)
                <tr wire:key="game-{{ $game->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                        @if($game->game_date)
                            <span class="block font-semibold tabular-nums text-zinc-900">{{ $game->game_date->format('d.m.Y') }}</span>
                            <span class="block text-xs text-zinc-500">{{ $game->game_date->translatedFormat('l') }}@if($game->game_date->format('H:i') !== '00:00') · {{ $game->game_date->format('H:i') }}@else · <span class="text-amber-700">bez godziny</span>@endif</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                        <span class="inline-flex items-center gap-2 font-medium">
                            <span class="h-5 w-1 rounded-full" style="background: {{ $teamColors[$game->team_id] ?? \App\Support\AgeColors::forTeam($game->team) }}" aria-hidden="true"></span>
                            {{ $game->team->name }}
                        </span>
                    </td>
                    <td class="max-w-72 px-3 py-4 text-sm text-gray-700">
                        <span class="flex flex-wrap items-center gap-1.5">
                            <span @class(['rounded px-1.5 py-0.5 text-[10px] font-bold tracking-wide uppercase', 'bg-emerald-50 text-emerald-700' => $game->is_home, 'bg-zinc-100 text-zinc-600' => ! $game->is_home]) title="{{ $game->is_home ? 'U siebie' : 'Na wyjeździe' }}">{{ $game->is_home ? 'Dom' : 'Wyjazd' }}</span>
                            @if($game->kind !== \App\Models\Game::KIND_LEAGUE)
                                <span class="rounded bg-violet-50 px-1.5 py-0.5 text-[10px] font-bold tracking-wide text-violet-700 uppercase">{{ \App\Models\Game::KINDS[$game->kind] ?? $game->kind }}</span>
                            @endif
                        </span>
                        <span class="mt-1 block font-medium text-zinc-900">{{ $game->opponentDisplayName() }}</span>
                        @isset($duplicateIds[$game->id])
                            <a href="{{ route('admin.duplicate-games.index') }}" class="ml-1 inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/30 ring-inset hover:bg-amber-100" title="Ta drużyna ma tego dnia drugi mecz z tym rywalem. Sprawdź w: Do zatwierdzenia → Możliwe duble meczów">⚠ możliwy dubel</a>
                        @endisset
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if(!is_null($game->home_score) && !is_null($game->away_score))
                            @php $outcome = $game->outcome(); @endphp
                            <div class="flex items-center gap-1.5">
                                @if($outcome)
                                    <span @class(['inline-flex size-5 items-center justify-center rounded text-[11px] font-bold text-white', 'bg-emerald-600' => $outcome === 'win', 'bg-red-500' => $outcome === 'loss', 'bg-zinc-400' => ! in_array($outcome, ['win', 'loss'])]) title="{{ $outcome === 'win' ? 'Wygrana' : ($outcome === 'loss' ? 'Porażka' : '') }}">{{ $outcome === 'win' ? 'W' : ($outcome === 'loss' ? 'P' : '–') }}</span>
                                @endif
                                <span class="font-display text-base font-semibold tabular-nums text-zinc-900">{{ $game->home_score }}:{{ $game->away_score }}</span>
                                @if($game->result_source === \App\Models\Game::RESULT_MANUAL)
                                    <span class="ml-1 rounded bg-amber-50 px-1.5 py-0.5 text-xs font-medium text-amber-800" title="Wpisany ręcznie, import ze związku go nie zmienia">ręcznie</span>
                                @elseif($game->result_source)
                                    <span class="ml-1 rounded bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-800" title="Pobrany ze strony związku">ze związku</span>
                                @endif
                            </div>
                            @if(is_array($game->sets_score) && count($game->sets_score) > 0)
                                <div class="text-xs text-gray-400 mt-0.5">
                                    @foreach($game->sets_score as $set)
                                        @if(is_array($set) && count($set) === 2)
                                            {{ (int) $set[0] }}:{{ (int) $set[1] }}@if(! $loop->last), @endif
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php
                            $statusColors = [
                                'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                'live' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                                'finished' => 'bg-green-50 text-green-700 ring-green-600/20',
                                'cancelled' => 'bg-red-50 text-red-700 ring-red-600/20',
                            ];
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColors[$game->status] ?? '' }}">
                            {{ ['scheduled' => 'Zaplanowany', 'live' => 'Na żywo', 'finished' => 'Zakończony', 'cancelled' => 'Odwołany'][$game->status] ?? $game->status }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php
                            $materials = array_filter([
                                ['newspaper', 'Wiadomość', $game->articles_count > 0],
                                ['video', 'Wideo', $game->hasStreams()],
                                ['photo', 'Galeria', $game->galleries_count > 0],
                                ['pencil', 'Relacja', filled($game->match_report)],
                            ], fn (array $material) => $material[2]);
                        @endphp
                        @forelse($materials as [$icon, $label])
                            <span class="mr-1 inline-flex size-7 items-center justify-center rounded-md bg-sky-50 text-sky-700 ring-1 ring-sky-100" title="{{ $label }}" aria-label="{{ $label }}"><x-admin-icon :name="$icon" class="size-4" /></span>
                        @empty
                            <span class="text-xs text-gray-400">brak</span>
                        @endforelse
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $game->id }})" class="text-sky-700 hover:text-sky-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $game->id }})" wire:confirm="Czy na pewno chcesz usunąć ten mecz?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-3 py-4 text-sm text-gray-500 text-center">Brak meczów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <div class="mt-4 px-4 pb-4">
        {{ $games->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="admin-sheet flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj mecz' : 'Dodaj mecz' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                                    <select wire:model="team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">Wybierz</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sezon</label>
                                    <select wire:model="season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">Wybierz</option>
                                        @foreach($seasons as $season)
                                            <option value="{{ $season->id }}">{{ $season->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('season_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Drużyna rywala (z bazy)</label>
                                <select wire:model="opponent_team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">Brak (wpisz nazwę ręcznie poniżej)</option>
                                    @foreach($rivalTeams as $rivalTeam)
                                        <option value="{{ $rivalTeam->id }}">{{ $rivalTeam->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('opponent_team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Przeciwnik (nazwa)</label>
                                <input type="text" wire:model="opponent" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('opponent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Rodzaj meczu</label>
                                <select wire:model.live="kind" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @foreach(\App\Models\Game::KINDS as $kindValue => $kindLabel)
                                        <option value="{{ $kindValue }}">{{ $kindLabel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($kind === 'tournament')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Turniej</label>
                                    <select wire:model="tournament_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">— wybierz —</option>
                                        @foreach($tournaments as $tournamentOption)
                                            <option value="{{ $tournamentOption->id }}">{{ $tournamentOption->fullName() }} ({{ $tournamentOption->dateRangeLabel() }})</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Nowy turniej dodasz w zakładce „Turnieje”.</p>
                                    @error('tournament_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <div @class(['hidden' => $kind !== 'league'])>
                                <label class="block text-sm font-medium text-gray-700">Rozgrywki (liga)</label>
                                <select wire:model="competition_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">Brak / inne</option>
                                    @foreach($competitions as $competition)
                                        <option value="{{ $competition->id }}">{{ $competition->name }} ({{ $competition->level }})</option>
                                    @endforeach
                                </select>
                                @error('competition_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data i godzina</label>
                                    <x-datetime-input model="game_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500" />
                                    @error('game_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="scheduled">Zaplanowany</option>
                                        <option value="live">Na żywo</option>
                                        <option value="finished">Zakończony</option>
                                        <option value="cancelled">Odwołany</option>
                                    </select>
                                    @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_home" id="is_home" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                <label for="is_home" class="ml-2 text-sm text-gray-700">Mecz u siebie (gospodarze)</label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Miejsce</label>
                                    <input type="text" wire:model="venue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Liga</label>
                                    <input type="text" wire:model="league" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>

                            @if($editingId && $resultSource === \App\Models\Game::RESULT_MANUAL && $competition_id)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                                    <span>Wynik wpisany ręcznie. Import ze związku go nie nadpisze.</span>
                                    <button type="button" wire:click="restoreOfficialResult" wire:confirm="Wrócić do wyniku ze związku? Ręczny wynik zostanie zastąpiony oficjalnym, gdy związek go poda." class="rounded-md bg-white px-3 py-1.5 font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100">Wróć do wyniku ze związku</button>
                                </div>
                            @elseif($editingId && $resultSource)
                                <p class="rounded-md bg-green-50 p-3 text-sm text-green-900">Wynik pobrany ze strony związku. Jeśli go zmienisz, import przestanie go aktualizować.</p>
                            @endif

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wynik gospodarzy (sety)</label>
                                    <input type="number" wire:model="home_score" min="0" max="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wynik gości (sety)</label>
                                    <input type="number" wire:model="away_score" min="0" max="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Wyniki setów</label>
                                <input type="text" wire:model="sets_input" placeholder="np. 25:23, 22:25, 25:20, 25:18" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('sets_input') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                <p class="mt-1 text-xs text-gray-500">Po wpisaniu setów system automatycznie wyliczy wynik meczu i małe punkty oraz ustawi status na „Zakończony”.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Wyróżnienie meczu <span class="font-normal text-gray-400">(opcjonalnie)</span></label>
                                <input type="text" wire:model="mvp" maxlength="120" placeholder="np. MVP: Jan Kowalski" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('mvp') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty gospodarzy</label>
                                    <input type="number" wire:model="home_points" min="0" placeholder="opcjonalnie" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty gości</label>
                                    <input type="number" wire:model="away_points" min="0" placeholder="opcjonalnie" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 -mt-2">Ustawienie wyniku i statusu "Zakończony" automatycznie przelicza tabelę ligową dla wybranych rozgrywek.</p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Relacja z meczu</label>
                                <x-rich-text-editor model="match_report" placeholder="Relacja meczowa..." height="min-h-[180px]" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Link do wideo</label>
                                <input type="url" wire:model="video_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('video_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
