<div class="space-y-6">
    <div class="flex justify-end">
        <button type="button" wire:click="create" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">+ Dodaj wychowanka</button>
    </div>

    @if($showForm)
        <form wire:submit="save" class="space-y-4 rounded-lg bg-white p-5 shadow ring-1 ring-black/5">
            <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj wychowanka' : 'Nowy wychowanek' }}</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="alumnus-name" class="block text-sm font-medium text-gray-700">Imię i nazwisko</label>
                    <input id="alumnus-name" type="text" wire:model="name" class="mt-1 block w-full">
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="alumnus-level" class="block text-sm font-medium text-gray-700">Poziom</label>
                    <input id="alumnus-level" type="text" wire:model="level" list="alumni-levels" placeholder="np. PlusLiga" class="mt-1 block w-full">
                    <datalist id="alumni-levels">@foreach(\App\Models\Alumnus::LEVELS as $levelName)<option value="{{ $levelName }}"></option>@endforeach</datalist>
                </div>
                <div>
                    <label for="alumnus-club" class="block text-sm font-medium text-gray-700">Obecny klub</label>
                    <input id="alumnus-club" type="text" wire:model="current_club" class="mt-1 block w-full">
                </div>
                <div>
                    <label for="alumnus-position" class="block text-sm font-medium text-gray-700">Pozycja</label>
                    <input id="alumnus-position" type="text" wire:model="position" placeholder="np. atakujący" class="mt-1 block w-full">
                </div>
                <div>
                    <label for="alumnus-years" class="block text-sm font-medium text-gray-700">Lata w Jokerze</label>
                    <input id="alumnus-years" type="text" wire:model="years_in_club" placeholder="np. 2008–2014" class="mt-1 block w-full">
                </div>
                <div>
                    <label for="alumnus-player" class="block text-sm font-medium text-gray-700">Profil zawodnika na stronie (opcjonalnie)</label>
                    <select id="alumnus-player" wire:model="player_id" class="mt-1 block w-full">
                        <option value="">Brak</option>
                        @foreach($players as $player)
                            <option value="{{ $player->id }}">{{ $player->last_name }} {{ $player->first_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label for="alumnus-url" class="block text-sm font-medium text-gray-700">Link do profilu poza stroną (np. PlusLiga), gdy nie ma profilu zawodnika</label>
                    <input id="alumnus-url" type="url" wire:model="profile_url" placeholder="https://..." class="mt-1 block w-full">
                    @error('profile_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="alumnus-achievements" class="block text-sm font-medium text-gray-700">Osiągnięcia (każde w osobnej linii)</label>
                    <textarea id="alumnus-achievements" wire:model="achievements" rows="4" class="mt-1 block w-full"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Zdjęcie</label>
                    @if($photo)
                        <img src="{{ $photo->temporaryUrl() }}" alt="" class="mt-1 h-24 w-24 rounded object-cover">
                    @elseif($existingPhoto)
                        <img src="{{ \App\Support\Media::url($existingPhoto) }}" alt="" class="mt-1 h-24 w-24 rounded object-cover">
                    @endif
                    <input type="file" wire:model="photo" accept="image/*" class="mt-2 block text-sm">
                    @error('photo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured"> Wyróżniony (na górze listy)</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_published"> Widoczny na stronie</label>
                    <label class="block text-sm text-gray-700">Kolejność <input type="number" wire:model="display_order" min="0" class="ml-2 w-20"></label>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" wire:click="resetForm" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Anuluj</button>
                <button type="submit" class="rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Zapisz</button>
            </div>
        </form>
    @endif

    <div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-black/5">
        <ul class="divide-y divide-gray-200">
            @forelse($alumni as $alumnus)
                <li class="flex items-center gap-4 p-4" wire:key="alumnus-{{ $alumnus->id }}">
                    @if($alumnus->photoUrl())
                        <img src="{{ $alumnus->photoUrl() }}" alt="" class="h-12 w-12 rounded object-cover">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded bg-gray-100 text-lg">🏐</span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-900">{{ $alumnus->name }} @if($alumnus->is_featured)<span class="text-amber-500">★</span>@endif @unless($alumnus->is_published)<span class="text-xs font-normal text-gray-400">(ukryty)</span>@endunless</p>
                        <p class="text-sm text-gray-500">{{ collect([$alumnus->level, $alumnus->current_club, $alumnus->years_in_club])->filter()->implode(' · ') ?: 'Uzupełnij dane' }}</p>
                    </div>
                    <button type="button" wire:click="edit({{ $alumnus->id }})" class="text-sm font-semibold text-sky-700 hover:text-sky-900">Edytuj</button>
                    <button type="button" wire:click="delete({{ $alumnus->id }})" wire:confirm="Usunąć {{ $alumnus->name }} z listy wychowanków?" class="text-sm font-semibold text-red-600 hover:text-red-800">Usuń</button>
                </li>
            @empty
                <li class="p-6 text-sm text-gray-500">Nie ma jeszcze wychowanków.</li>
            @endforelse
        </ul>
    </div>
</div>
