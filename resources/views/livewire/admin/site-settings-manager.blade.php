<div class="bg-white">
    {{-- Zakładki --}}
    <div class="border-b border-gray-200 overflow-x-auto">
        <nav class="flex min-w-max gap-1 px-4 pt-3">
            @foreach($groups as $groupKey => $group)
                <button
                    type="button"
                    wire:click="selectTab('{{ $groupKey }}')"
                    @class([
                        'rounded-t-lg px-4 py-2.5 text-sm font-semibold transition',
                        'bg-sky-50 text-sky-700 border-b-2 border-sky-600' => $tab === $groupKey,
                        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => $tab !== $groupKey,
                    ])
                >
                    <span aria-hidden="true">{{ $group['icon'] }}</span> {{ $group['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    <form wire:submit="save" class="p-4 sm:p-6">
        @if($flashMessage)
            <div class="mb-6 rounded-lg bg-green-50 p-4 text-sm font-medium text-green-800" role="status">✓ {{ $flashMessage }}</div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800">
                Popraw zaznaczone pola i zapisz ponownie.
            </div>
        @endif

        <div class="space-y-6" wire:key="tab-{{ $tab }}">
            @foreach($groups[$tab]['fields'] as $key => $field)
                <div class="grid gap-2 sm:grid-cols-3 sm:gap-6 border-b border-gray-100 pb-6 last:border-0" wire:key="field-{{ $key }}">
                    <div>
                        <label for="field-{{ $key }}" class="block text-sm font-semibold text-gray-900">{{ $field['label'] }}</label>
                        @isset($field['help'])
                            <p class="mt-1 text-xs text-gray-500">{{ $field['help'] }}</p>
                        @endisset
                    </div>

                    <div class="sm:col-span-2">
                        @switch($field['type'])
                            @case('color')
                                <div class="flex items-center gap-3" x-data>
                                    <input type="color" id="field-{{ $key }}" wire:model.live="values.{{ $key }}" class="h-11 w-16 cursor-pointer rounded-md border border-gray-300 bg-white p-1">
                                    <input type="text" wire:model.live.debounce.500ms="values.{{ $key }}" maxlength="7" class="w-32 font-mono uppercase" aria-label="{{ $field['label'] }} (kod koloru)">
                                    <span class="inline-flex h-11 flex-1 items-center rounded-md px-3 text-xs font-semibold" style="background: {{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) $values[$key]) ? $values[$key] : '#ffffff' }}; color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) $values[$key]) && hexdec(substr($values[$key], 1, 2)) * 0.299 + hexdec(substr($values[$key], 3, 2)) * 0.587 + hexdec(substr($values[$key], 5, 2)) * 0.114 < 150 ? '#fff' : '#111' }}">Podgląd</span>
                                </div>
                                @break

                            @case('toggle')
                                <label class="inline-flex cursor-pointer items-center gap-3">
                                    <input type="checkbox" id="field-{{ $key }}" wire:model="values.{{ $key }}" class="peer sr-only">
                                    <span class="relative h-7 w-12 rounded-full bg-gray-300 transition peer-checked:bg-sky-600 after:absolute after:left-1 after:top-1 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5"></span>
                                    <span class="text-sm text-gray-700">{{ $values[$key] ? 'Włączone' : 'Wyłączone' }}</span>
                                </label>
                                @break

                            @case('select')
                                <div class="space-y-2">
                                    @foreach($field['options'] as $optionValue => $optionLabel)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 ring-1 ring-gray-200 has-[:checked]:bg-sky-50 has-[:checked]:ring-sky-500">
                                            <input type="radio" wire:model="values.{{ $key }}" value="{{ $optionValue }}" class="text-sky-600">
                                            <span class="text-sm text-gray-800">{{ $optionLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @break

                            @case('textarea')
                                <textarea id="field-{{ $key }}" wire:model="values.{{ $key }}" rows="3" class="block w-full"></textarea>
                                @break

                            @case('image')
                                <div class="flex flex-wrap items-center gap-4">
                                    @if(isset($uploads[$key]) && $uploads[$key])
                                        <img src="{{ $uploads[$key]->temporaryUrl() }}" alt="Podgląd" class="h-20 w-20 rounded-lg border object-cover">
                                    @elseif($values[$key])
                                        <img src="{{ \App\Support\Media::url($values[$key]) }}" alt="" class="h-20 w-20 rounded-lg border object-cover">
                                    @else
                                        <span class="flex h-20 w-20 items-center justify-center rounded-lg border border-dashed text-xs text-gray-400">brak</span>
                                    @endif
                                    <div class="space-y-2">
                                        <input type="file" id="field-{{ $key }}" wire:model="uploads.{{ $key }}" accept="image/*" class="block text-sm text-gray-700">
                                        @if($values[$key])
                                            <button type="button" wire:click="removeImage('{{ $key }}')" class="text-xs font-semibold text-red-600 hover:text-red-800">Usuń obrazek (wróci domyślny)</button>
                                        @endif
                                    </div>
                                </div>
                                @error('uploads.'.$key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                @break

                            @case('links')
                                <div class="space-y-2">
                                    @foreach($values[$key] as $index => $link)
                                        <div class="flex flex-wrap items-start gap-2 rounded-lg bg-gray-50 p-2 sm:flex-nowrap" wire:key="{{ $key }}-{{ $index }}">
                                            <input type="text" wire:model="values.{{ $key }}.{{ $index }}.label" placeholder="Tekst, np. Dla rodziców" class="min-w-0 flex-1" aria-label="Tekst linku {{ $index + 1 }}">
                                            <input type="text" wire:model="values.{{ $key }}.{{ $index }}.url" placeholder="Adres, np. /strona/faq" class="min-w-0 flex-1 font-mono text-xs" aria-label="Adres linku {{ $index + 1 }}">
                                            <div class="flex shrink-0 gap-1">
                                                <button type="button" wire:click="moveLink('{{ $key }}', {{ $index }}, -1)" @disabled($loop->first) class="rounded-md border border-gray-300 bg-white px-2.5 py-2 text-sm disabled:opacity-30" title="Wyżej">↑</button>
                                                <button type="button" wire:click="moveLink('{{ $key }}', {{ $index }}, 1)" @disabled($loop->last) class="rounded-md border border-gray-300 bg-white px-2.5 py-2 text-sm disabled:opacity-30" title="Niżej">↓</button>
                                                <button type="button" wire:click="removeLink('{{ $key }}', {{ $index }})" class="rounded-md border border-red-200 bg-white px-2.5 py-2 text-sm text-red-600 hover:bg-red-50" title="Usuń">✕</button>
                                            </div>
                                        </div>
                                        @error('values.'.$key.'.'.$index.'.url') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    @endforeach
                                    <button type="button" wire:click="addLink('{{ $key }}')" class="rounded-md border border-dashed border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">+ Dodaj link</button>
                                    <p class="text-xs text-gray-500">Podstrony klubu mają adresy /strona/nazwa (lista w „Podstrony”). Adresy innych serwisów zaczynaj od https://.</p>
                                </div>
                                @break

                            @default
                                <input type="text" id="field-{{ $key }}" wire:model="values.{{ $key }}" class="block w-full" @if($field['type'] === 'url') placeholder="/contact albo https://..." @endif>
                        @endswitch

                        @error('values.'.$key) <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach
        </div>

        <div class="sticky bottom-0 -mx-4 mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 bg-white/95 px-4 py-4 backdrop-blur sm:-mx-6 sm:px-6">
            <button type="button" wire:click="restoreDefaults" wire:confirm="Przywrócić ustawienia domyślne w tej zakładce?" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Przywróć domyślne</button>
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-sky-700 hover:text-sky-900">Zobacz stronę ↗</a>
                <button type="submit" class="rounded-md bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                    <span wire:loading.remove wire:target="save">Zapisz zmiany</span>
                    <span wire:loading wire:target="save">Zapisywanie…</span>
                </button>
            </div>
        </div>
    </form>
</div>
