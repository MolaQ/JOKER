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
                placeholder="Szukaj drużyny..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
        </div>
        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
        >
            + Dodaj drużynę
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nazwa</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Kategoria</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Trener</th>
                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zawodnicy</th>
                <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">Akcje</span>
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($teams as $team)
                <tr wire:key="team-{{ $team->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                        {{ $team->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                            {{ $team->category }}{{ $team->birth_year ? ' '.$team->birth_year : '' }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $team->trainer?->name ?? 'Brak' }}
                        @if($team->assistantTrainers->isNotEmpty())
                            <span class="block text-xs text-gray-400">+ {{ $team->assistantTrainers->pluck('name')->join(', ') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $team->players_count }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $team->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button
                            type="button"
                            wire:click="delete({{ $team->id }})"
                            wire:confirm="Czy na pewno chcesz usunąć drużynę {{ $team->name }}?"
                            class="text-red-600 hover:text-red-900"
                        >Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">
                        Brak drużyn do wyświetlenia
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $teams->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-init="$el.querySelector('input')?.focus()">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj drużynę' : 'Dodaj drużynę' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Opis</label>
                                <x-rich-text-editor model="description" placeholder="Opis drużyny..." height="min-h-[150px]" />
                                @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kategoria</label>
                                <select wire:model="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="senior">Seniorzy</option>
                                    <option value="junior">Juniorzy</option>
                                    <option value="junior_mlodszy">Juniorzy Młodsi</option>
                                    <option value="mlodzik">Młodzicy</option>
                                </select>
                                @error('category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Rocznik (puste dla Seniorów)</label>
                                <input type="number" wire:model="birth_year" min="1990" max="2020" placeholder="np. 2010" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('birth_year') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Trener główny</label>
                                <select wire:model="trainer_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Brak</option>
                                    @foreach($trainers as $trainer)
                                        <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                    @endforeach
                                </select>
                                @error('trainer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trenerzy pomocniczy</label>
                                <p class="text-xs text-gray-500 mb-2">Drużyna może mieć więcej niż jednego trenera.</p>
                                <div class="grid grid-cols-2 gap-2 max-h-32 overflow-y-auto border border-gray-200 rounded-md p-2">
                                    @foreach($trainers as $trainer)
                                        <label class="flex items-center text-sm text-gray-700">
                                            <input type="checkbox" wire:model="assistant_trainer_ids" value="{{ $trainer->id }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                            {{ $trainer->name }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('assistant_trainer_ids') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kolejność wyświetlania</label>
                                <input type="number" wire:model="display_order" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('display_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo drużyny</label>
                                @if($existingLogo && !$logo)
                                    <img src="{{ Storage::url($existingLogo) }}" class="h-16 w-16 object-cover rounded mb-2" alt="Logo">
                                @endif
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 object-cover rounded mb-2" alt="Podgląd">
                                @endif
                                <input type="file" wire:model="logo" accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
                                <div wire:loading wire:target="logo" class="text-xs text-gray-500 mt-1">Wczytywanie...</div>
                                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
