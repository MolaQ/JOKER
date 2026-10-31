<div class="space-y-6">
    @if($gallery || $creating)
        <div class="rounded-lg bg-white p-4 shadow ring-1 ring-zinc-200 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-gray-900">{{ $gallery ? 'Galeria: '.$gallery->title : 'Nowa galeria' }}</h2>
                <div class="flex gap-3 text-sm">
                    @if($gallery?->is_published)
                        <a href="{{ route('galleries.show', $gallery) }}" target="_blank" class="font-semibold text-sky-700 hover:underline">Zobacz na stronie ↗</a>
                    @endif
                    <button type="button" wire:click="close" class="font-semibold text-gray-600 hover:text-gray-900">← Wszystkie galerie</button>
                </div>
            </div>

            <form wire:submit="save" class="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tytuł</label>
                    <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    @error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data</label>
                        <input type="date" wire:model="event_date" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Drużyna</label>
                        <select wire:model="team_id" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                            <option value="">Cały klub</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->id }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mecz (opcjonalnie)</label>
                    <select wire:model="match" class="mt-1 block w-full rounded-md border-gray-300 text-sm">
                        <option value="">Bez meczu (wydarzenie, obóz, trening)</option>
                        @foreach($this->matchOptions() as $group => $options)
                            <optgroup label="{{ $group }}">
                                @foreach($options as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">Galeria z meczem pokazuje kilka miniatur w szczegółach meczu, a w galerii jest link do meczu.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Opis</label>
                    <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 text-sm"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Dodaj zdjęcia</label>
                    <input type="file" wire:model="uploads" multiple accept="image/*" class="mt-1 block w-full text-sm">
                    <div wire:loading wire:target="uploads" class="mt-1 text-sm text-gray-500">Wysyłanie...</div>
                    @error('uploads.*')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="flex items-end justify-between gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" wire:model="is_published" class="rounded border-gray-300 text-sky-700"> Opublikowana</label>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Zapisz galerię</button>
                </div>
            </form>
        </div>

        @if($gallery)
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($gallery->photos as $photo)
                    <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200" wire:key="photo-{{ $photo->id }}">
                        <img src="{{ $photo->thumbUrl() }}" alt="" class="aspect-[4/3] w-full object-cover" loading="lazy">
                        <div class="space-y-2 p-3 text-sm">
                            <input type="text" wire:model.blur.live="captions.{{ $photo->id }}" placeholder="Opis zdjęcia" class="block w-full rounded-md border-gray-300 text-sm">
                            <div class="flex flex-wrap gap-1">
                                @foreach($photo->tags as $tag)
                                    <span @class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs', 'bg-blue-50 text-blue-800' => $tag->status === 'approved', 'bg-amber-50 text-amber-800' => $tag->status !== 'approved'])>
                                        {{ $tag->label() }}@if($tag->status !== 'approved') (czeka)@endif
                                        @if(! $tag->isVisible())<span title="Brak zgody na wizerunek: oznaczenie jest ukryte" class="text-red-500"><x-admin-icon name="eyeoff" class="inline size-3.5 align-[-2px]" /></span>@endif
                                        <button type="button" wire:click="removeTag({{ $tag->id }})" class="text-gray-400 hover:text-red-600" aria-label="Usuń oznaczenie">×</button>
                                    </span>
                                @endforeach
                                <button type="button" wire:click="startTagging({{ $photo->id }})" class="rounded-full border border-dashed border-gray-300 px-2 py-0.5 text-xs text-gray-600 hover:border-blue-400 hover:text-blue-700">+ oznacz</button>
                            </div>
                            @if($taggingPhotoId === $photo->id)
                                <div class="rounded-md border border-blue-200 bg-blue-50/50 p-2">
                                    <input type="search" wire:model.live.debounce.300ms="tagSearch" placeholder="Drużyna, zawodnik lub trener..." class="block w-full rounded-md border-gray-300 text-sm" autofocus>
                                    @foreach($tagOptions as $key => $label)
                                        <button type="button" wire:click="addTag('{{ $key }}')" class="block w-full rounded px-2 py-1 text-left text-sm hover:bg-white">{{ $label }}</button>
                                    @endforeach
                                </div>
                            @endif
                            <div class="flex justify-between text-xs">
                                <span class="space-x-2">
                                    <button type="button" wire:click="movePhoto({{ $photo->id }}, -1)" class="text-gray-500 hover:text-gray-900" aria-label="Wcześniej">←</button>
                                    <button type="button" wire:click="movePhoto({{ $photo->id }}, 1)" class="text-gray-500 hover:text-gray-900" aria-label="Później">→</button>
                                </span>
                                <button type="button" wire:click="deletePhoto({{ $photo->id }})" wire:confirm="Usunąć to zdjęcie?" class="text-red-600 hover:underline">Usuń zdjęcie</button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="text-right">
                <button type="button" wire:click="deleteGallery({{ $gallery->id }})" wire:confirm="Usunąć całą galerię razem ze zdjęciami?" class="text-sm font-semibold text-red-600 hover:underline">Usuń galerię</button>
            </div>
        @endif
    @else
        <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Szukaj galerii..." class="w-full rounded-md border-gray-300 text-sm sm:w-64">
                <button type="button" wire:click="create" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">+ Nowa galeria</button>
            </div>
            <ul class="divide-y divide-gray-200">
                @forelse($galleries as $item)
                    <li class="flex flex-wrap items-center justify-between gap-3 p-4 text-sm" wire:key="gallery-{{ $item->id }}">
                        <div>
                            <p class="font-semibold text-gray-900">{{ $item->title }} @unless($item->is_published)<span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs font-normal text-gray-600">ukryta</span>@endunless</p>
                            <p class="text-gray-500">{{ $item->displayDate()?->format('d.m.Y') }} · {{ $item->photos_count }} zdj.@if($item->team) · {{ $item->team->name }}@endif @if($item->match) · mecz @endif</p>
                        </div>
                        <button type="button" wire:click="edit({{ $item->id }})" class="admin-action">Edytuj</button>
                    </li>
                @empty
                    <li class="p-6 text-center text-gray-500">Brak galerii.</li>
                @endforelse
            </ul>
            @if(method_exists($galleries, 'links'))<div class="px-4 py-3">{{ $galleries->links() }}</div>@endif
        </div>
    @endif
</div>
