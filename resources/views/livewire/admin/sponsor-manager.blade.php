<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Szukaj sponsora..."
            class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj sponsora
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Logo</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Nazwa</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Poziom</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($sponsors as $sponsor)
                <tr wire:key="sponsor-{{ $sponsor->id }}">
                    <td class="py-4 pl-4 pr-3 sm:pl-6">
                        <img src="{{ Storage::url($sponsor->logo_path) }}" class="h-10 w-10 object-contain" alt="{{ $sponsor->name }}">
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-gray-900">
                        {{ $sponsor->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500 capitalize">
                        {{ $sponsor->tier }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($sponsor->is_active)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktywny</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nieaktywny</span>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $sponsor->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $sponsor->id }})" wire:confirm="Czy na pewno chcesz usunąć sponsora {{ $sponsor->name }}?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">Brak sponsorów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $sponsors->links() }}
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
                                {{ $editingId ? 'Edytuj sponsora' : 'Dodaj sponsora' }}
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
                                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Strona internetowa</label>
                                <input type="url" wire:model="website_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Poziom sponsoringu</label>
                                    <select wire:model="tier" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="platinum">Platynowy</option>
                                        <option value="gold">Złoty</option>
                                        <option value="silver">Srebrny</option>
                                        <option value="bronze">Brązowy</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kolejność</label>
                                    <input type="number" wire:model="display_order" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo</label>
                                @if($existingLogo && !$logo)
                                    <img src="{{ Storage::url($existingLogo) }}" class="h-16 w-16 object-contain rounded mb-2" alt="Logo">
                                @endif
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="h-16 w-16 object-contain rounded mb-2" alt="Podgląd">
                                @endif
                                <input type="file" wire:model="logo" accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
                                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="sponsor_is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <label for="sponsor_is_active" class="ml-2 text-sm text-gray-700">Sponsor aktywny</label>
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
