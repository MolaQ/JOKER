<div class="space-y-6">
    <p class="text-sm text-gray-600">Logotypy partnerów są na dole każdej strony turnieju (nad klubowym blokiem „Wspierają nas”) i jako pierwsze na pasku ekranu TV. Partner główny dostaje duży, wyróżniony kafelek.</p>

    <ul class="divide-y divide-gray-200 rounded-lg ring-1 ring-gray-200">
        @forelse($partners as $partner)
            <li wire:key="partner-{{ $partner->id }}" class="flex flex-wrap items-center gap-3 px-3 py-2.5">
                <div class="flex flex-col">
                    <button type="button" wire:click="move({{ $partner->id }}, -1)" @disabled($loop->first) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Wyżej">▲</button>
                    <button type="button" wire:click="move({{ $partner->id }}, 1)" @disabled($loop->last) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Niżej">▼</button>
                </div>
                <span class="flex h-12 w-20 shrink-0 items-center justify-center rounded-md p-1 ring-1 ring-gray-200" style="{{ $partner->tile_color ? 'background: '.$partner->tile_color : '' }}">
                    @if($partner->logo_path)<img src="{{ \App\Support\Media::url($partner->logo_path) }}" alt="" class="max-h-full max-w-full object-contain">@endif
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $partner->name }}
                        @if($partner->pivot->is_main)<span class="ml-1 rounded-md bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">partner główny</span>@endif
                        @unless($partner->is_active)<span class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">ukryty</span>@endunless
                    </p>
                    <p class="text-xs text-gray-500">{{ $partner->sponsor_group_id ? 'także sponsor klubu' : 'tylko turnieje' }}</p>
                </div>
                <div class="flex gap-1.5">
                    <button type="button" wire:click="toggleMain({{ $partner->id }})" class="admin-action">{{ $partner->pivot->is_main ? 'Zwykły partner' : 'Partner główny' }}</button>
                    <button type="button" wire:click="remove({{ $partner->id }})" class="admin-action admin-action-danger">Usuń</button>
                </div>
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-500">Turniej nie ma jeszcze partnerów.</li>
        @endforelse
    </ul>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-lg border border-gray-200 p-4">
            <p class="text-sm font-semibold text-gray-900">Dodaj z listy sponsorów</p>
            <p class="mt-0.5 text-xs text-gray-500">Sponsorzy klubu i partnerzy poprzednich turniejów.</p>
            <select wire:model.live="existingId" class="mt-3 block w-full">
                <option value="">— wybierz —</option>
                @foreach($available as $sponsor)
                    <option value="{{ $sponsor->id }}">{{ $sponsor->name }}</option>
                @endforeach
            </select>
        </div>

        <form wire:submit="addNew" class="space-y-3 rounded-lg border border-gray-200 p-4">
            <p class="text-sm font-semibold text-gray-900">Nowy partner tylko tego turnieju</p>
            <input type="text" wire:model="name" placeholder="Nazwa" class="block w-full">
            @error('name') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <input type="text" wire:model="website_url" placeholder="Link, np. https://firma.pl" class="block w-full">
            @error('website_url') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <x-photo-cropper model="logo" :preview="false" aspect="free" format="png" :width="800" label="Wybierz logo" />
            @error('logo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <label class="block text-xs text-gray-600">Tło kafelka (dla białego logo, np. #1e2a8a; puste = białe)
                <input type="text" wire:model="tile_color" placeholder="#RRGGBB" class="mt-1 block w-40 font-mono text-sm">
            </label>
            @error('tile_color') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Dodaj partnera</button>
        </form>
    </div>
</div>
