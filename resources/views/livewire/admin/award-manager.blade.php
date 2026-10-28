<div>
    <div class="mb-4 flex justify-end">
        <button type="button" wire:click="openCreateModal" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">+ Dodaj wyróżnienie</button>
    </div>

    <ul class="admin-card divide-y divide-gray-100">
        @forelse($awards as $award)
            <li class="flex flex-wrap items-center gap-4 p-4" wire:key="award-{{ $award->id }}">
                <div class="flex h-16 w-32 shrink-0 items-center justify-center rounded-lg bg-white ring-1 ring-gray-200">
                    @if($award->logo_path)
                        <img src="{{ \App\Support\Media::url($award->logo_path) }}" alt="" class="max-h-14 max-w-28 object-contain">
                    @else
                        <span class="text-xs text-gray-400">bez logo</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1 text-sm">
                    <p class="font-semibold text-gray-900">{{ $award->name }}</p>
                    @if($award->note)<p class="text-gray-500">{{ $award->note }}</p>@endif
                    @if($award->url)<a href="{{ $award->url }}" target="_blank" rel="noopener" class="text-xs break-all text-sky-700 hover:underline">{{ $award->url }}</a>@endif
                    @unless($award->is_active)<span class="mt-1 inline-block rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">ukryte</span>@endunless
                </div>
                <div class="flex items-center gap-1 text-sm">
                    <button type="button" wire:click="move({{ $award->id }}, -1)" @disabled($loop->first) class="rounded p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" title="Wyżej" aria-label="Wyżej">↑</button>
                    <button type="button" wire:click="move({{ $award->id }}, 1)" @disabled($loop->last) class="rounded p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-30" title="Niżej" aria-label="Niżej">↓</button>
                    <button type="button" wire:click="openEditModal({{ $award->id }})" class="ml-2 font-medium text-sky-700 hover:text-sky-900">Edytuj</button>
                    <button type="button" wire:click="delete({{ $award->id }})" wire:confirm="Usunąć „{{ $award->name }}”?" class="ml-3 font-medium text-red-600 hover:text-red-900">Usuń</button>
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500">Nie ma jeszcze wyróżnień.</li>
        @endforelse
    </ul>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/5" @click.stop>
                    <form wire:submit="save">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj wyróżnienie' : 'Dodaj wyróżnienie' }}</h3>
                        </div>
                        <div class="space-y-4 px-6 py-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="award-name">Nazwa</label>
                                <input id="award-name" type="text" wire:model="name" placeholder="np. Brązowy Ośrodek PZPS 2026/2027" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="award-note">Dopisek mniejszym pismem (opcjonalnie)</label>
                                <input id="award-note" type="text" wire:model="note" placeholder="np. kto przyznał i w którym roku" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('note') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="award-url">Link (opcjonalnie)</label>
                                <input id="award-url" type="url" wire:model="url" placeholder="https://..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-700">Logo (opcjonalnie)</span>
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="mt-2 h-16 max-w-48 rounded object-contain ring-1 ring-gray-200" alt="Podgląd">
                                @elseif($existingLogo && ! $removeLogo)
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ \App\Support\Media::url($existingLogo) }}" class="h-16 max-w-48 rounded object-contain ring-1 ring-gray-200" alt="Logo">
                                        <button type="button" wire:click="$set('removeLogo', true)" class="text-sm text-red-600 hover:text-red-800">Usuń logo</button>
                                    </div>
                                @endif
                                <x-photo-cropper model="logo" class="mt-2" :preview="false" aspect="free" format="png" :width="800" label="Wybierz logo" />
                                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-sky-700"> Pokazuj na stronie
                            </label>
                        </div>
                        <div class="sticky bottom-0 z-10 flex justify-end gap-3 rounded-b-2xl border-t border-gray-200 bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Anuluj</button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Zapisz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
