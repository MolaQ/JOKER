<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj przeciwnika..."
                class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
            <select wire:model.live="statusFilter" class="w-full sm:w-48 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">Wszystkie statusy</option>
                <option value="scheduled">Zaplanowany</option>
                <option value="live">Na żywo</option>
                <option value="finished">Zakończony</option>
                <option value="cancelled">Odwołany</option>
            </select>
        </div>
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj mecz
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Data</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Drużyna</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Przeciwnik</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Wynik</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($games as $game)
                <tr wire:key="game-{{ $game->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                        {{ $game->game_date?->format('d.m.Y H:i') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                        {{ $game->team->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $game->is_home ? 'vs ' : '@ ' }}{{ $game->opponentDisplayName() }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        @if(!is_null($game->home_score) && !is_null($game->away_score))
                            {{ $game->home_score }} : {{ $game->away_score }}
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
                            {{ $game->status }}
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $game->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $game->id }})" wire:confirm="Czy na pewno chcesz usunąć ten mecz?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">Brak meczów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $games->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj mecz' : 'Dodaj mecz' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                                    <select wire:model="team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Wybierz</option>
                                        @foreach($teams as $team)
                                            <option value="{{ $team->id }}">{{ $team->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Sezon</label>
                                    <select wire:model="season_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                <select wire:model="opponent_team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Brak (wpisz nazwę ręcznie poniżej)</option>
                                    @foreach($rivalTeams as $rivalTeam)
                                        <option value="{{ $rivalTeam->id }}">{{ $rivalTeam->full_name }}</option>
                                    @endforeach
                                </select>
                                @error('opponent_team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Przeciwnik (nazwa)</label>
                                <input type="text" wire:model="opponent" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('opponent') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Rozgrywki (liga)</label>
                                <select wire:model="competition_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
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
                                    <input type="datetime-local" wire:model="game_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('game_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="scheduled">Zaplanowany</option>
                                        <option value="live">Na żywo</option>
                                        <option value="finished">Zakończony</option>
                                        <option value="cancelled">Odwołany</option>
                                    </select>
                                    @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_home" id="is_home" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="is_home" class="ml-2 text-sm text-gray-700">Mecz u siebie (gospodarze)</label>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Miejsce</label>
                                    <input type="text" wire:model="venue" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Liga</label>
                                    <input type="text" wire:model="league" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wynik gospodarzy (sety)</label>
                                    <input type="number" wire:model="home_score" min="0" max="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Wynik gości (sety)</label>
                                    <input type="number" wire:model="away_score" min="0" max="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty gospodarzy</label>
                                    <input type="number" wire:model="home_points" min="0" placeholder="opcjonalnie" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Małe punkty gości</label>
                                    <input type="number" wire:model="away_points" min="0" placeholder="opcjonalnie" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 -mt-2">Ustawienie wyniku i statusu "Zakończony" automatycznie przelicza tabelę ligową dla wybranych rozgrywek.</p>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Relacja z meczu</label>
                                <x-rich-text-editor model="match_report" placeholder="Relacja meczowa..." height="min-h-[180px]" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Link do wideo</label>
                                <input type="url" wire:model="video_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('video_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
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
