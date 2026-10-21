<div class="space-y-6">
    {{-- WYBÓR MECZU --}}
    <div class="rounded-lg bg-white p-4 shadow ring-1 ring-black/5 sm:p-6">
        <h2 class="text-lg font-semibold text-gray-900">1. Wybierz mecz</h2>
        <p class="mt-1 text-sm text-gray-500">Wpisz datę, drużynę, rywala albo nazwę turnieju, np. „21.10 kadeci kalisz”. Najnowsze mecze są na górze.</p>

        <div class="mt-4 grid gap-3 lg:grid-cols-[18rem_1fr]">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj meczu…"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
            >
            <select wire:model.live="selected" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" aria-label="Mecz">
                <option value="">— wybierz mecz ({{ collect($this->matchOptions)->flatten()->count() }} na liście) —</option>
                @foreach($this->matchOptions as $group => $options)
                    <optgroup label="{{ $group }}">
                        @foreach($options as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        @if($search !== '' && $this->matchOptions === [])
            <p class="mt-2 text-sm text-amber-700">Nie znaleziono meczu pasującego do „{{ $search }}”.</p>
        @endif
    </div>

    @if($match)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-blue-50 px-4 py-3 text-sm ring-1 ring-blue-100">
            <p class="font-semibold text-blue-900">{{ $match->mediaLabel() }}</p>
            @if($publicUrl)
                <a href="{{ $publicUrl }}" target="_blank" class="font-semibold text-blue-700 hover:underline">Zobacz na stronie ↗</a>
            @endif
        </div>

        {{-- TRANSMISJE --}}
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-black/5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-900">2. Transmisje</h2>
            <p class="mt-1 text-sm text-gray-500">Link do YouTube zostanie osadzony na stronie meczu, Facebook i inne serwisy pojawią się jako przycisk. Opis jest opcjonalny, np. „Nagranie rodziców”, „Transmisja klubu”.</p>

            <form wire:submit="saveStreams" class="mt-4 space-y-3">
                @forelse($streams as $index => $stream)
                    <div class="grid gap-2 sm:grid-cols-[1fr_14rem_auto] sm:items-start" wire:key="stream-{{ $index }}">
                        <div>
                            <input type="url" wire:model.blur="streams.{{ $index }}.url" placeholder="https://www.youtube.com/watch?v=…" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @if($platform = $this->platformOf($stream['url'] ?? ''))
                                <p class="mt-1 text-xs text-gray-500">Rozpoznano: <span class="font-semibold">{{ $platform }}</span></p>
                            @endif
                            @error('streams.'.$index.'.url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <input type="text" wire:model="streams.{{ $index }}.label" placeholder="Opis (opcjonalnie)" class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <button type="button" wire:click="removeStream({{ $index }})" class="rounded-md px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">Usuń</button>
                    </div>
                @empty
                    <p class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-500">Ten mecz nie ma jeszcze linków do transmisji.</p>
                @endforelse

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <button type="button" wire:click="addStream" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">+ Dodaj link</button>
                    <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                        <span wire:loading.remove wire:target="saveStreams">Zapisz transmisje</span>
                        <span wire:loading wire:target="saveStreams">Zapisywanie…</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- GALERIE --}}
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-black/5 sm:p-6">
            <h2 class="text-lg font-semibold text-gray-900">3. Galerie zdjęć</h2>

            <div class="mt-4 space-y-4">
                @forelse($galleries as $gallery)
                    <div class="rounded-lg border border-gray-200" wire:key="gallery-{{ $gallery->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-3 p-4">
                            <div class="flex min-w-0 items-center gap-3">
                                @if($gallery->photos->first())
                                    <img src="{{ $gallery->photos->first()->url() }}" alt="" class="h-12 w-16 shrink-0 rounded object-cover" onerror="this.style.visibility='hidden'">
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ $gallery->title }}</p>
                                    <p class="text-xs text-gray-500">{{ $gallery->photos->count() }} zdj. · dodano {{ $gallery->created_at?->format('d.m.Y') }}</p>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <button type="button" wire:click="togglePublished({{ $gallery->id }})" @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-green-50 text-green-700 ring-green-600/20' => $gallery->is_published,
                                    'bg-gray-50 text-gray-600 ring-gray-500/10' => ! $gallery->is_published,
                                ]) title="Kliknij, aby zmienić">
                                    {{ $gallery->is_published ? 'Opublikowana' : 'Ukryta' }}
                                </button>
                                @if($editingGalleryId === $gallery->id)
                                    <button type="button" wire:click="cancelEdit" class="font-semibold text-gray-600 hover:text-gray-900">Zwiń</button>
                                @else
                                    <button type="button" wire:click="editGallery({{ $gallery->id }})" class="font-semibold text-blue-600 hover:text-blue-900">Edytuj</button>
                                @endif
                                <button type="button" wire:click="deleteGallery({{ $gallery->id }})" wire:confirm="Usunąć galerię „{{ $gallery->title }}” razem ze wszystkimi zdjęciami?" class="font-semibold text-red-600 hover:text-red-900">Usuń</button>
                            </div>
                        </div>

                        @if($editingGalleryId === $gallery->id)
                            <form wire:submit="saveGallery" class="space-y-4 border-t border-gray-200 bg-gray-50 p-4">
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tytuł</label>
                                        <input type="text" wire:model="editTitle" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @error('editTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Opis (opcjonalnie)</label>
                                        <input type="text" wire:model="editDescription" placeholder="np. Zdjęcia: Anna Kowalska" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>

                                @if($gallery->photos->isNotEmpty())
                                    <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach($gallery->photos as $photo)
                                            <li class="flex gap-3 rounded-md bg-white p-2 ring-1 ring-gray-200" wire:key="photo-{{ $photo->id }}">
                                                <img src="{{ $photo->url() }}" alt="" class="h-20 w-24 shrink-0 rounded object-cover" onerror="this.style.visibility='hidden'">
                                                <div class="flex min-w-0 flex-1 flex-col gap-2">
                                                    <input type="text" wire:model="captions.{{ $photo->id }}" placeholder="Podpis (opcjonalnie)" class="block w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                    <div class="flex items-center gap-1 text-xs">
                                                        <button type="button" wire:click="movePhoto({{ $photo->id }}, -1)" @disabled($loop->first) class="rounded px-2 py-1 font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-30" title="Przesuń wcześniej">↑</button>
                                                        <button type="button" wire:click="movePhoto({{ $photo->id }}, 1)" @disabled($loop->last) class="rounded px-2 py-1 font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-30" title="Przesuń dalej">↓</button>
                                                        <button type="button" wire:click="deletePhoto({{ $photo->id }})" wire:confirm="Usunąć to zdjęcie?" class="ml-auto rounded px-2 py-1 font-semibold text-red-600 hover:bg-red-50">Usuń</button>
                                                    </div>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Dodaj zdjęcia</label>
                                    <input type="file" wire:model="morePhotos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
                                    <p wire:loading wire:target="morePhotos" class="mt-1 text-xs text-gray-500">Wgrywanie…</p>
                                    @error('morePhotos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    @if($morePhotos)
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach($morePhotos as $file)
                                                @if(method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="h-14 w-14 rounded object-cover">
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                <div class="flex justify-end gap-3">
                                    <button type="button" wire:click="cancelEdit" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50">Anuluj</button>
                                    <button type="submit" wire:loading.attr="disabled" wire:target="saveGallery,morePhotos" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50">Zapisz galerię</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-500">Ten mecz nie ma jeszcze galerii.</p>
                @endforelse
            </div>

            {{-- NOWA GALERIA --}}
            <form wire:submit="createGallery" class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                <h3 class="font-semibold text-gray-900">Nowa galeria</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tytuł</label>
                        <input type="text" wire:model="galleryTitle" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('galleryTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Opis (opcjonalnie)</label>
                        <input type="text" wire:model="galleryDescription" placeholder="np. Zdjęcia: Anna Kowalska" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Zdjęcia (można zaznaczyć wiele naraz)</label>
                    <input type="file" wire:model="galleryPhotos" multiple accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
                    <p wire:loading wire:target="galleryPhotos" class="mt-1 text-xs text-gray-500">Wgrywanie…</p>
                    @error('galleryPhotos') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @error('galleryPhotos.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @if($galleryPhotos)
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($galleryPhotos as $file)
                                @if(method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                    <img src="{{ $file->temporaryUrl() }}" alt="" class="h-14 w-14 rounded object-cover">
                                @endif
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ count($galleryPhotos) }} zdj. do wgrania. Podpisy i kolejność ustawisz po utworzeniu galerii.</p>
                    @endif
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="galleryPublished" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Opublikuj od razu
                    </label>
                    <button type="submit" wire:loading.attr="disabled" wire:target="createGallery,galleryPhotos" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50">
                        <span wire:loading.remove wire:target="createGallery">Utwórz galerię</span>
                        <span wire:loading wire:target="createGallery">Zapisywanie…</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
