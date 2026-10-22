<div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj zawodnika..."
                class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
            >
            <select wire:model.live="teamFilter" class="w-full sm:w-56 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                <option value="">Wszystkie drużyny</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
        </div>
        <button
            type="button"
            wire:click="openCreateModal"
            class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700"
        >
            + Dodaj zawodnika
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">#</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Imię i nazwisko</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Pozycja</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Drużyna</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($players as $player)
                <tr wire:key="player-{{ $player->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-bold text-gray-900 sm:pl-6">
                        {{ $player->jersey_number }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                        {{ $player->full_name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $player->position->label() }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $player->team->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($player->is_active)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktywny</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nieaktywny</span>
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

    <div class="mt-4 px-4 pb-4">
        {{ $players->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj zawodnika' : 'Dodaj zawodnika' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
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
                                    <input type="number" wire:model="jersey_number" min="0" max="99" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    @error('jersey_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Pozycja główna</label>
                                    <select wire:model="position" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
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
                                <select wire:model="team_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <option value="">Wybierz drużynę</option>
                                    @foreach($teams as $team)
                                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                                    @endforeach
                                </select>
                                @error('team_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Konto zawodnika</label>
                                    <select wire:model="user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">— brak —</option>
                                        @foreach($playerAccounts as $account)
                                            <option value="{{ $account->id }}">{{ $account->name }} ({{ $account->email }})</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Konta z rolą „Zawodnik”. Zawodnik może proponować zmiany profilu i zdjęcie.</p>
                                    @error('user_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Rodzice</label>
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
                                    <p class="mt-1 text-xs text-gray-500">Rodzic może proponować zmiany i zdjęcie dziecka.</p>
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
                                <label class="block text-sm font-medium text-gray-700">Biografia</label>
                                <x-rich-text-editor model="bio" placeholder="Biografia zawodnika..." height="min-h-[170px]" />
                                @error('bio') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

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

                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
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
