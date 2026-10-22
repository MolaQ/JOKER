<div class="bg-white">
    <div class="flex items-center justify-end border-b border-gray-200 p-4">
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
            + Dodaj podstronę
        </button>
    </div>

    <ul class="divide-y divide-gray-200">
        @forelse($pages as $page)
            <li class="flex flex-wrap items-center gap-3 px-4 py-4 sm:px-6" wire:key="page-{{ $page->id }}">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $page->title }}
                        @if($page->hasEnglish())
                            <x-english-badge class="ml-1" />
                        @endif
                        @unless($page->is_published)
                            <span class="ml-2 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Ukryta</span>
                        @endunless
                    </p>
                    <a href="{{ route('pages.show', $page) }}" target="_blank" class="font-mono text-xs text-sky-700 hover:underline">/strona/{{ $page->slug }} ↗</a>
                </div>
                <div class="flex gap-4 text-sm font-medium">
                    <button type="button" wire:click="openEditModal({{ $page->id }})" class="text-sky-700 hover:text-sky-900">Edytuj</button>
                    <button type="button" wire:click="delete({{ $page->id }})" wire:confirm="Usunąć podstronę „{{ $page->title }}”? Linki do niej w menu przestaną działać." class="text-red-600 hover:text-red-800">Usuń</button>
                </div>
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-gray-500">Nie ma jeszcze podstron.</li>
        @endforelse
    </ul>

    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-3xl rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/5">
                    <form wire:submit="save">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj podstronę' : 'Nowa podstrona' }}</h3>
                        </div>

                        <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="page-title">Tytuł</label>
                                <input type="text" id="page-title" wire:model="title" class="mt-1 block w-full">
                                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="page-slug">Adres podstrony</label>
                                <div class="mt-1 flex items-center gap-1">
                                    <span class="font-mono text-sm text-gray-500">/strona/</span>
                                    <input type="text" id="page-slug" wire:model="slug" placeholder="utworzy się z tytułu" class="block w-full font-mono text-sm">
                                </div>
                                @error('slug') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="page-lead">Wstęp (wyróżniony tekst pod tytułem)</label>
                                <textarea id="page-lead" wire:model="lead" rows="2" class="mt-1 block w-full"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Treść</label>
                                <x-rich-text-editor model="content" placeholder="Treść podstrony..." height="min-h-[240px]" />
                            </div>

                            {{-- Wersja angielska: pokazywana odwiedzającym, którzy wybrali flagę brytyjską --}}
                            <div x-data="{ open: @js(filled($title_en) || filled($content_en)) }" class="rounded-lg border border-gray-200">
                                <button type="button" @click="open = ! open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-medium text-gray-700">
                                    <span class="inline-flex items-center gap-2"><x-flag country="gb" class="h-3 w-[1.125rem] rounded-[2px] ring-1 ring-black/15" /> Wersja angielska (opcjonalnie)</span>
                                    <span class="text-gray-400 transition" :class="open && 'rotate-180'" aria-hidden="true">▾</span>
                                </button>
                                <div x-show="open" x-cloak class="space-y-4 border-t border-gray-200 px-4 py-4">
                                    <p class="text-xs text-gray-500">Gdy uzupełnisz angielski tytuł, podstrona dostanie brytyjską flagę, a osoby oglądające stronę po angielsku zobaczą tę wersję. Puste pola zostaną zastąpione polskimi.</p>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" for="page-title-en">Tytuł (EN)</label>
                                        <input type="text" id="page-title-en" wire:model="title_en" class="mt-1 block w-full">
                                        @error('title_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700" for="page-lead-en">Wstęp (EN)</label>
                                        <textarea id="page-lead-en" wire:model="lead_en" rows="2" class="mt-1 block w-full"></textarea>
                                        @error('lead_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Treść (EN)</label>
                                        <x-rich-text-editor model="content_en" placeholder="Page content..." height="min-h-[240px]" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zdjęcie u góry (opcjonalne)</label>
                                <div class="mt-1 flex flex-wrap items-center gap-4">
                                    @if($image)
                                        <img src="{{ $image->temporaryUrl() }}" class="h-16 w-24 rounded object-cover" alt="Podgląd">
                                    @elseif($existingImage)
                                        <img src="{{ \App\Support\Media::url($existingImage) }}" onerror="this.style.visibility='hidden'" class="h-16 w-24 rounded object-cover" alt="">
                                        <button type="button" wire:click="removeImage" class="text-xs font-semibold text-red-600">Usuń zdjęcie</button>
                                    @endif
                                    <input type="file" wire:model="image" accept="image/*" class="block text-sm text-gray-700">
                                </div>
                                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex flex-wrap items-center gap-6">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="is_published" class="rounded border-gray-300 text-sky-600">
                                    Widoczna na stronie
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    Kolejność
                                    <input type="number" wire:model="display_order" min="0" class="w-20">
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 rounded-b-lg bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">Anuluj</button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                                <span wire:loading.remove wire:target="save">Zapisz</span>
                                <span wire:loading wire:target="save">Zapisywanie…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
