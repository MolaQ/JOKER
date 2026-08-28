<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Sezony rozgrywkowe</h2>
            <p class="text-xs text-gray-500">Sezon oznaczony jako "aktualny" jest domyślnie wybierany w innych panelach.</p>
        </div>
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj sezon
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Nazwa</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Początek</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Koniec</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Aktualny</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($seasons as $season)
                <tr wire:key="season-{{ $season->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">{{ $season->name }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $season->start_date?->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $season->end_date?->format('d.m.Y') }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($season->is_current)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktualny</span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $season->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $season->id }})" wire:confirm="Czy na pewno chcesz usunąć ten sezon?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">Brak sezonów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj sezon' : 'Dodaj sezon' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                                <input type="text" wire:model="name" placeholder="np. 2025/2026" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data rozpoczęcia</label>
                                    <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('start_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data zakończenia</label>
                                    <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('end_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="flex items-center text-sm font-medium text-gray-700">
                                    <input type="checkbox" wire:model="is_current" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2">
                                    Ustaw jako aktualny sezon
                                </label>
                                <p class="mt-1 text-xs text-gray-500">Ustawienie tego sezonu jako aktualnego odznaczy poprzedni aktualny sezon.</p>
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
