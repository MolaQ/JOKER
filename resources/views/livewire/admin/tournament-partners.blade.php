<div class="space-y-6">
    <p class="text-sm text-gray-600">Logotypy partnerów są na dole każdej strony turnieju (nad klubowym blokiem „Wspierają nas”) i jako pierwsze na pasku ekranu TV. „★ Partner główny” dostaje duży, wyróżniony kafelek. Kolejność zmienisz strzałkami ▲▼ przy partnerze. Podpis (np. „Patronat honorowy”) pokazuje się nad logo.</p>

    <ul class="divide-y divide-gray-200 rounded-lg ring-1 ring-gray-200">
        @forelse($partners as $partner)
            <li wire:key="partner-{{ $partner->id }}" class="flex flex-wrap items-center gap-3 px-3 py-2.5">
                <div class="flex flex-col">
                    <button type="button" wire:click="move({{ $partner->id }}, -1)" @disabled($loop->first) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Wyżej">▲</button>
                    <button type="button" wire:click="move({{ $partner->id }}, 1)" @disabled($loop->last) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Niżej">▼</button>
                </div>
                <span class="flex h-12 w-24 shrink-0 items-center justify-center rounded-md p-1 ring-1 ring-gray-200" style="{{ $partner->tile_color ? 'background: '.$partner->tile_color : '' }}">
                    @if($partner->logo_path)<img src="{{ \App\Support\Media::url($partner->logo_path) }}" alt="" class="max-h-full max-w-full object-contain">@else<span class="text-[10px] text-gray-400">brak logo</span>@endif
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-gray-900">{{ $partner->name }}
                        @if($partner->pivot->is_main)<span class="ml-1 rounded-md bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-800">partner główny</span>@endif
                        @unless($partner->is_active)<span class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">ukryty</span>@endunless
                    </p>
                    <p class="text-xs text-gray-500">{{ $partner->sponsor_group_id ? 'także sponsor klubu' : 'tylko turnieje' }}</p>
                    <input type="text" value="{{ $partner->pivot->role }}" wire:change="setRole({{ $partner->id }}, $event.target.value)" maxlength="60" list="partner-roles" placeholder="Podpis, np. Patronat honorowy" aria-label="Podpis nad logo" class="mt-1 block w-64 max-w-full py-1 text-xs">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Rodzaj kafelka: zaznaczona opcja jest wyróżniona --}}
                    <span class="inline-flex overflow-hidden rounded-md text-xs font-semibold ring-1 ring-gray-300" role="group" aria-label="Rodzaj partnera">
                        <button type="button" wire:click="setMain({{ $partner->id }}, false)" aria-pressed="{{ $partner->pivot->is_main ? 'false' : 'true' }}" @class(['px-2.5 py-1.5', 'bg-sky-600 text-white' => ! $partner->pivot->is_main, 'bg-white text-gray-600 hover:bg-gray-50' => $partner->pivot->is_main])>Zwykły kafelek</button>
                        <button type="button" wire:click="setMain({{ $partner->id }}, true)" aria-pressed="{{ $partner->pivot->is_main ? 'true' : 'false' }}" @class(['border-l border-gray-300 px-2.5 py-1.5', 'bg-amber-400 text-gray-900' => $partner->pivot->is_main, 'bg-white text-gray-600 hover:bg-gray-50' => ! $partner->pivot->is_main])>★ Partner główny</button>
                    </span>
                    <button type="button" wire:click="edit({{ $partner->id }})" class="admin-action">Edytuj</button>
                    <button type="button" wire:click="remove({{ $partner->id }})" wire:confirm="Usunąć {{ $partner->name }} z partnerów turnieju?" class="admin-action admin-action-danger">Usuń</button>
                </div>
                @if($editingId === $partner->id)
                    {{-- Edycja partnera: nazwa, link, logo i tło kafelka --}}
                    <form wire:submit="saveEdit" class="grid w-full gap-3 rounded-md bg-gray-50 p-3 ring-1 ring-gray-200 sm:grid-cols-2">
                        <label class="block text-xs text-gray-600">Nazwa
                            <input type="text" wire:model="editName" class="mt-1 block w-full">
                            @error('editName') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <label class="block text-xs text-gray-600">Link
                            <input type="text" wire:model="editUrl" placeholder="https://firma.pl" class="mt-1 block w-full">
                            @error('editUrl') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <div class="sm:col-span-2">
                            <p class="text-xs text-gray-600">Logo</p>
                            <div class="mt-1 flex flex-wrap items-start gap-3">
                                @if($editLogo && ! $errors->has('editLogo'))
                                    {{-- Nowe logo wyraźnie oznaczone: zapisze się dopiero po „Zapisz” --}}
                                    <span class="relative flex h-20 w-48 shrink-0 items-center justify-center rounded-md p-2 ring-2 ring-emerald-500" style="background: {{ $editColor ?: '#fff' }}">
                                        <span class="absolute -top-2.5 left-2 rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold tracking-wide text-white uppercase">Nowe logo</span>
                                        <img src="{{ $editLogo->temporaryUrl() }}" alt="Nowe logo" class="max-h-full max-w-full object-contain">
                                    </span>
                                    @if($partner->logo_path)
                                        <span class="relative flex h-12 w-28 shrink-0 items-center justify-center self-center rounded p-1 opacity-50 ring-1 ring-gray-200 grayscale" title="Obecne logo zostanie zastąpione">
                                            <span class="absolute -top-2 left-1 rounded bg-gray-200 px-1 text-[9px] font-semibold text-gray-600 uppercase">było</span>
                                            <img src="{{ \App\Support\Media::url($partner->logo_path) }}" alt="Obecne logo" class="max-h-full max-w-full object-contain">
                                        </span>
                                    @endif
                                    <p class="w-full text-xs font-semibold text-emerald-700">Nowe logo zapisze się po kliknięciu „Zapisz”.</p>
                                @else
                                    <span class="flex h-16 w-40 shrink-0 items-center justify-center rounded p-1 ring-1 ring-gray-200" style="background: {{ $editColor ?: '#fff' }}">
                                        @if($partner->logo_path)
                                            <img src="{{ \App\Support\Media::url($partner->logo_path) }}" alt="Obecne logo" class="max-h-full max-w-full object-contain">
                                        @else
                                            <span class="text-xs text-gray-400">brak logo</span>
                                        @endif
                                    </span>
                                @endif
                                <x-photo-cropper model="editLogo" class="min-w-0 flex-1" :preview="false" aspect="free" format="png" :width="1600" :label="$partner->logo_path || $editLogo ? 'Zmień logo' : 'Dodaj logo'" />
                            </div>
                            @error('editLogo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="block text-xs text-gray-600">Tło kafelka (dla białego logo; puste = białe)
                            <input type="text" wire:model.live.debounce.500ms="editColor" placeholder="#RRGGBB" class="mt-1 block w-40 font-mono text-sm">
                            @error('editColor') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>
                        <div class="flex items-end justify-end gap-2">
                            @if($partner->sponsor_group_id)<span class="mr-auto text-xs text-amber-700">Zmiany dotyczą też sponsora klubu.</span>@endif
                            <button type="button" wire:click="cancelEdit" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300">Anuluj</button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Zapisz</button>
                        </div>
                    </form>
                @endif
            </li>
        @empty
            <li class="px-4 py-6 text-center text-sm text-gray-500">Turniej nie ma jeszcze partnerów.</li>
        @endforelse
    </ul>
    <datalist id="partner-roles">
        @foreach(['Patronat honorowy', 'Partner główny', 'Partner strategiczny', 'Partner medialny', 'Sponsor nagród', 'Współorganizator'] as $role)
            <option value="{{ $role }}"></option>
        @endforeach
    </datalist>

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
            @if($logo && ! $errors->has('logo'))
                {{-- Przycięte logo czeka na zapis: zapisze się dopiero po „Dodaj partnera” --}}
                <div class="flex items-center gap-3 rounded-md bg-emerald-50 p-2 ring-1 ring-emerald-200">
                    <span class="flex h-16 w-40 shrink-0 items-center justify-center rounded p-1 ring-1 ring-gray-200" style="background: {{ $tile_color ?: '#fff' }}"><img src="{{ $logo->temporaryUrl() }}" alt="Podgląd logo" class="max-h-full max-w-full object-contain"></span>
                    <p class="text-xs text-emerald-900">Logo gotowe. Wpisz nazwę i kliknij „Dodaj partnera”.</p>
                </div>
            @endif
            <x-photo-cropper model="logo" :preview="false" aspect="free" format="png" :width="1600" :label="$logo ? 'Zmień logo' : 'Wybierz logo'" />
            @error('logo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <label class="block text-xs text-gray-600">Tło kafelka (dla białego logo, np. #1e2a8a; puste = białe)
                <input type="text" wire:model="tile_color" placeholder="#RRGGBB" class="mt-1 block w-40 font-mono text-sm">
            </label>
            @error('tile_color') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Dodaj partnera</button>
        </form>
    </div>
</div>
