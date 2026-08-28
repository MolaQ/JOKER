<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="w-full sm:w-72">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj rozgrywek..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
        </div>
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj rozgrywki
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nazwa</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Poziom</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Sezon</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Drużyny</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($competitions as $competition)
                <tr wire:key="competition-{{ $competition->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $competition->name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $competition->levelName() }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $competition->season?->name }}</td>
                    <td class="px-3 py-4 text-sm text-gray-500">
                        {{ $competition->teams->pluck('name')->join(', ') }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $competition->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $competition->id }})" wire:confirm="Czy na pewno chcesz usunąć te rozgrywki?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">Brak rozgrywek do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $competitions->links() }}
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj rozgrywki' : 'Dodaj rozgrywki' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                                <input type="text" wire:model="name" placeholder="np. Liga Wojewódzka Juniorów" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Poziom rozgrywek</label>
                                    <select wire:model="level_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Brak (wpisz ręcznie poniżej)</option>
                                        @foreach($levels as $lvl)
                                            <option value="{{ $lvl->id }}">{{ $lvl->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" wire:model="level" placeholder="np. 3 liga" class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    @error('level_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                <label class="block text-sm font-medium text-gray-700">Opis</label>
                                <x-rich-text-editor model="description" placeholder="Opis rozgrywek..." height="min-h-[130px]" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Punktacja setowa</label>
                                <p class="text-xs text-gray-500 mb-2">Punkty przyznawane drużynom w tabeli za dany wynik setowy meczu.</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Wygrana 3:0</label>
                                        <input type="number" wire:model="points_win_3_0" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Wygrana 3:1</label>
                                        <input type="number" wire:model="points_win_3_1" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Wygrana 3:2</label>
                                        <input type="number" wire:model="points_win_3_2" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Przegrana 2:3</label>
                                        <input type="number" wire:model="points_loss_2_3" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Przegrana 1:3</label>
                                        <input type="number" wire:model="points_loss_1_3" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500">Przegrana 0:3</label>
                                        <input type="number" wire:model="points_loss_0_3" min="0" max="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kryteria sortowania tabeli</label>
                                <p class="text-xs text-gray-500 mb-2">Kolejność decyduje o priorytecie przy remisie punktowym.</p>
                                <div class="grid grid-cols-3 gap-3">
                                    @foreach([0, 1, 2] as $i)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500">{{ $i + 1 }}. kryterium</label>
                                            <select wire:model="standings_criteria.{{ $i }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                                <option value="points">Punkty</option>
                                                <option value="wins">Liczba zwycięstw</option>
                                                <option value="sets_ratio">Stosunek setów</option>
                                                <option value="sets_diff">Różnica setów</option>
                                                <option value="points_ratio">Stosunek małych punktów</option>
                                                <option value="points_diff">Różnica małych punktów</option>
                                            </select>
                                        </div>
                                    @endforeach
                                </div>
                                @error('standings_criteria') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kolejność</label>
                                <input type="number" wire:model="display_order" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Drużyny biorące udział</label>
                                <p class="text-xs text-gray-500 mb-2">Drużyna może brać udział w kilku rozgrywkach jednocześnie.</p>
                                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                                    @foreach($teams as $team)
                                        <label class="flex items-center text-sm text-gray-700">
                                            <input type="checkbox" wire:model="team_ids" value="{{ $team->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                            {{ $team->name }}
                                        </label>
                                    @endforeach
                                </div>
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
