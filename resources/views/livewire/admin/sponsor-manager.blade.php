<div>
    <div class="flex flex-col gap-3 border-b border-gray-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-gray-600">Kategorie i logotypy bloku „Wspierają nas” nad stopką strony, w tej kolejności. Gdzie blok się pokazuje, ustawisz w: Wygląd i ustawienia → Kontakt i stopka.</p>
        <button type="button" wire:click="openCreateModal" class="admin-fab inline-flex shrink-0 items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">+ Dodaj logo</button>
    </div>

    <div class="space-y-6 p-4">
        @foreach($groups as $group)
            <section wire:key="group-{{ $group->id }}" class="rounded-xl ring-1 ring-gray-200">
                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2">
                    <div class="flex flex-col">
                        <button type="button" wire:click="moveGroup({{ $group->id }}, -1)" @disabled($loop->first) class="px-1 text-xs text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Kategoria wyżej">▲</button>
                        <button type="button" wire:click="moveGroup({{ $group->id }}, 1)" @disabled($loop->last) class="px-1 text-xs text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Kategoria niżej">▼</button>
                    </div>
                    <input type="text" wire:model="groupNames.{{ $group->id }}" wire:blur="renameGroup({{ $group->id }})" class="min-w-0 flex-1 border-transparent bg-transparent text-sm font-semibold uppercase tracking-wide focus:border-gray-300 focus:bg-white" aria-label="Nazwa kategorii">
                    <select wire:change="setGroupSize({{ $group->id }}, $event.target.value)" class="text-xs" aria-label="Wielkość logotypów">
                        @foreach(\App\Models\SponsorGroup::SIZES as $size => $sizeLabel)
                            <option value="{{ $size }}" @selected($group->size === $size)>logotypy {{ $sizeLabel }}</option>
                        @endforeach
                    </select>
                    <button type="button" wire:click="openCreateModal({{ $group->id }})" class="admin-action">+ Logo</button>
                    <button type="button" wire:click="deleteGroup({{ $group->id }})" wire:confirm="Usunąć kategorię „{{ $group->name }}”? Logotypy zostaną, trafią do „Bez kategorii”." class="admin-action admin-action-danger">Usuń kategorię</button>
                </div>
                @include('livewire.admin.partials.sponsor-rows', ['sponsors' => $group->sponsors])
            </section>
        @endforeach

        <form wire:submit="addGroup" class="flex flex-wrap items-center gap-2">
            <input type="text" wire:model="newGroup" placeholder="Nowa kategoria, np. Partnerzy medialni" class="min-w-0 flex-1 text-sm">
            <button type="submit" class="admin-action">+ Dodaj kategorię</button>
            @error('newGroup') <p class="w-full text-sm text-red-600">{{ $message }}</p> @enderror
        </form>

        @if($loose->isNotEmpty())
            <section class="rounded-xl ring-1 ring-gray-200">
                <div class="border-b border-gray-200 bg-gray-50 px-4 py-2">
                    <p class="text-sm font-semibold tracking-wide text-gray-700 uppercase">Bez kategorii</p>
                    <p class="text-xs text-gray-500">Nie ma ich w bloku „Wspierają nas”. Tu są np. partnerzy tylko turniejów.</p>
                </div>
                @include('livewire.admin.partials.sponsor-rows', ['sponsors' => $loose])
            </section>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="admin-sheet flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-lg" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj logo' : 'Dodaj logo' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa</label>
                                <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Opis</label>
                                <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500"></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Link po kliknięciu w logo</label>
                                <input type="text" wire:model="website_url" placeholder="https://… albo /page/klub-pro-2026" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                <p class="mt-1 text-xs text-gray-500">Strona partnera albo podstrona naszej strony (zaczyna się od /). Puste = logo bez linku.</p>
                                @error('website_url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Kategoria</label>
                                    <select wire:model="sponsor_group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="">— bez kategorii (np. tylko turnieje) —</option>
                                        @foreach(\App\Models\SponsorGroup::orderBy('display_order')->get() as $groupOption)
                                            <option value="{{ $groupOption->id }}">{{ $groupOption->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Tło kafelka</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input type="color" wire:model.live="tile_color" value="{{ $tile_color ?: '#ffffff' }}" class="h-9 w-12 cursor-pointer rounded border-gray-300 p-0.5">
                                        <input type="text" wire:model.live.debounce.500ms="tile_color" placeholder="białe" class="block w-full rounded-md border-gray-300 font-mono text-sm">
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Dla białego logo (np. KLUB PRO na granacie #1e2a8a).</p>
                                    @error('tile_color') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo</label>
                                @if($existingLogo && !$logo)
                                    <span class="mb-2 inline-flex h-16 w-28 items-center justify-center rounded p-1 ring-1 ring-gray-200" style="{{ $tile_color ? 'background: '.$tile_color : '' }}"><img src="{{ \App\Support\Media::url($existingLogo) }}" onerror="this.style.visibility='hidden'" class="max-h-full max-w-full object-contain" alt="Logo"></span>
                                @endif
                                @if($logo)
                                    <span class="mb-2 inline-flex h-16 w-40 items-center justify-center rounded p-1 ring-1 ring-gray-200" style="{{ $tile_color ? 'background: '.$tile_color : '' }}"><img src="{{ $logo->temporaryUrl() }}" class="max-h-full max-w-full object-contain" alt="Podgląd"></span>
                                @endif
                                <x-photo-cropper model="logo" class="mt-1" :preview="false" aspect="free" format="png" :width="1600" label="Wybierz logo" />
                                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" wire:model="is_active" id="sponsor_is_active" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                <label for="sponsor_is_active" class="ml-2 text-sm text-gray-700">Widoczny na stronie</label>
                            </div>
                        </div>

                        <div class="sticky bottom-0 z-10 px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg border-t border-gray-200">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
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
