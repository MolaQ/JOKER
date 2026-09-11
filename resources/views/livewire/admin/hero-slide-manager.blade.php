<div>
    @if($flashMessage)
        <div class="p-4 {{ $flashType === 'error' ? 'bg-red-50 text-red-800' : 'bg-green-50 text-green-800' }} text-sm font-medium">
            {{ $flashMessage }}
        </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Szukaj slajdu..."
            class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
        >
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
            + Dodaj slajd
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">↕</th>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Tło</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tytuł</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Kolejność</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white" wire:sort="sortSlide">
            @forelse($slides as $slide)
                <tr wire:key="slide-{{ $slide->id }}" wire:sort:item="{{ $slide->id }}">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-400 sm:pl-6">
                        <button type="button" wire:sort:handle class="cursor-grab active:cursor-grabbing">⋮⋮</button>
                    </td>
                    <td class="py-4 pl-4 pr-3 sm:pl-6">
                        <img src="{{ Storage::url($slide->image_path) }}" alt="{{ $slide->title }}" class="h-12 w-20 rounded object-cover">
                    </td>
                    <td class="px-3 py-4 text-sm text-gray-900">
                        <p class="font-medium">{{ $slide->title }}</p>
                        @if($slide->subtitle)
                            <p class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($slide->subtitle, 70) }}</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">{{ $slide->display_order }}</td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @if($slide->is_active)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Aktywny</span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Nieaktywny</span>
                        @endif

                        @if($slide->publish_from || $slide->publish_to)
                            <p class="mt-1 text-xs text-gray-500">
                                @if($slide->publish_from)
                                    od {{ $slide->publish_from->format('d.m.Y H:i') }}
                                @endif
                                @if($slide->publish_to)
                                    do {{ $slide->publish_to->format('d.m.Y H:i') }}
                                @endif
                            </p>
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6" wire:sort:ignore>
                        <button type="button" wire:click="openEditModal({{ $slide->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $slide->id }})" wire:confirm="Czy na pewno chcesz usunąć ten slajd?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">Brak slajdów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $slides->links() }}
    </div>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-gray-500/75" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj slajd' : 'Dodaj slajd' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tytuł</label>
                                <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Podtytuł</label>
                                <textarea wire:model="subtitle" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                @error('subtitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Napis przycisku (opcjonalnie)</label>
                                    <input type="text" wire:model="cta_label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('cta_label') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Link przycisku (opcjonalnie)</label>
                                    <input type="url" wire:model="cta_url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('cta_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kolejność</label>
                                    <input type="number" wire:model="display_order" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('display_order') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end">
                                    <div class="flex items-center">
                                        <input type="checkbox" wire:model="is_active" id="slide_is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <label for="slide_is_active" class="ml-2 text-sm text-gray-700">Slajd aktywny</label>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Publikuj od (opcjonalnie)</label>
                                    <input type="datetime-local" wire:model="publish_from" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('publish_from') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Publikuj do (opcjonalnie)</label>
                                    <input type="datetime-local" wire:model="publish_to" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    @error('publish_to') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zdjęcie tła</label>
                                @if($existingImage && !$image)
                                    <img src="{{ Storage::url($existingImage) }}" class="h-28 w-full max-w-sm rounded object-cover mb-2" alt="Aktualne zdjęcie">
                                @endif
                                @if($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="h-28 w-full max-w-sm rounded object-cover mb-2" alt="Podgląd zdjęcia">
                                @endif
                                <input type="file" wire:model="image" accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
                                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
