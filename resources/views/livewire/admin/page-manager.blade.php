<div class="bg-white">
    <div class="flex items-center justify-end border-b border-gray-200 p-4">
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
            + Dodaj podstronę
        </button>
    </div>

    <p class="border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs text-gray-600 sm:px-6">Kolejność ze strzałek to kolejność w menu działu na stronie (z boku, na telefonie „W tym dziale”). Ukryta podstrona to projekt: widzi ją tylko administrator, szef i osoby z uprawnieniem „Podgląd ukrytych podstron”, razem z linkami w menu i stopce.</p>

    <ul class="divide-y divide-gray-200">
        @forelse($pages as $page)
            @include('livewire.admin.partials.page-row', ['page' => $page, 'first' => $loop->first, 'last' => $loop->last, 'child' => false])
            @foreach($page->children as $child)
                @include('livewire.admin.partials.page-row', ['page' => $child, 'first' => $loop->first, 'last' => $loop->last, 'child' => true])
            @endforeach
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

                        <div class="space-y-4 px-6 py-4">
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
                                <textarea id="page-lead" wire:model="lead" rows="2" maxlength="255" class="mt-1 block w-full"></textarea>
                                @error('lead') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700" for="page-parent">Grupa w menu działu</label>
                                <select id="page-parent" wire:model="parent_id" class="mt-1 block w-full">
                                    <option value="">— brak (pozycja główna) —</option>
                                    @foreach($parents as $parent)
                                        <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Np. „KLUB PRO 2026” w grupie „Programy i projekty”. Strona grupy pokazuje swoje podstrony jako kafelki.</p>
                                @error('parent_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                                    <x-photo-cropper wire:key="page-cropper-{{ $image_is_banner ? 'baner' : 'zdjecie' }}" model="image" :preview="false" :aspect="$image_is_banner ? 'free' : '16/9'" :width="1600" />
                                </div>
                                @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model.live="image_is_banner" class="rounded border-gray-300 text-sky-600">
                                    To baner (np. programu z logotypami): pokaż go w całości, bez przycinania i przyciemniania
                                </label>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <p class="text-sm font-medium text-gray-700">Kolory strony</p>
                                <p class="mt-0.5 text-xs text-gray-500">Kolor główny: tło nagłówka i wyróżnionych liczb. Akcent: paski, liczby, kropka w menu. Puste pola = barwy klubu.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach(\App\Livewire\Admin\PageManager::THEMES as $key => $theme)
                                        <button type="button" wire:click="useTheme('{{ $key }}')" class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">
                                            @if($theme['primary'])<span class="h-3 w-3 rounded-full" style="background: {{ $theme['primary'] }}"></span><span class="-ml-1.5 h-3 w-3 rounded-full" style="background: {{ $theme['accent'] }}"></span>@endif
                                            {{ $theme['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    @foreach(['theme_primary' => 'Kolor główny', 'theme_accent' => 'Akcent'] as $field => $label)
                                        <label class="block text-sm text-gray-700">{{ $label }}
                                            <span class="mt-1 flex items-center gap-2">
                                                <input type="color" wire:model.live="{{ $field }}" value="{{ $this->{$field} ?: '#000000' }}" class="h-9 w-12 cursor-pointer rounded border-gray-300 p-0.5">
                                                <input type="text" wire:model.live.debounce.500ms="{{ $field }}" placeholder="barwy klubu" class="block w-full font-mono text-sm">
                                            </span>
                                            @error($field) <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-lg border border-gray-200 p-4">
                                <p class="text-sm font-medium text-gray-700">Wyróżnione liczby (opcjonalnie, do 4)</p>
                                <p class="mt-0.5 text-xs text-gray-500">Kafelki pod wstępem, np. „110 000 zł” — „dofinansowania w edycji 2026”.</p>
                                <div class="mt-3 space-y-2">
                                    @foreach($highlights as $index => $highlight)
                                        <div wire:key="highlight-{{ $index }}" class="flex flex-wrap items-center gap-2 sm:flex-nowrap">
                                            <input type="text" wire:model="highlights.{{ $index }}.value" placeholder="Liczba, np. 110 000 zł" maxlength="30" class="w-full sm:w-40">
                                            <input type="text" wire:model="highlights.{{ $index }}.label" placeholder="Opis" maxlength="80" class="min-w-0 flex-1">
                                            <button type="button" wire:click="removeHighlight({{ $index }})" class="text-xs font-semibold text-red-600">Usuń</button>
                                        </div>
                                    @endforeach
                                </div>
                                @if(count($highlights) < 4)
                                    <button type="button" wire:click="addHighlight" class="mt-2 text-sm font-semibold text-sky-700">+ Dodaj liczbę</button>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-6">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="is_published" class="rounded border-gray-300 text-sky-600">
                                    Opublikowana (widzą ją wszyscy)
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="in_menu" class="rounded border-gray-300 text-sky-600">
                                    W menu działu
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" wire:model="footer_link" class="rounded border-gray-300 text-sky-600">
                                    Link w stopce
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
