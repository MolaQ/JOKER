@props([
    'model',
    // [['value' => ..., 'label' => ..., 'group' => ...], ...]; group = nagłówek jak <optgroup>.
    'options' => [],
    // Napis, gdy nic nie wybrano (pusta wartość), np. „— nowa drużyna (wpisz nazwę) —”.
    'empty' => '— wybierz —',
    'placeholder' => 'Wpisz, aby wyszukać…',
])
{{-- Wybór z długiej listy z wyszukiwaniem (resources/js/search-select.js). --}}
{{-- Nowy klucz przy zmianie listy: Livewire podmienia element i wyszukiwarka dostaje aktualne pozycje. --}}
<div wire:key="search-select-{{ $model }}-{{ md5(json_encode($options)) }}" x-data="searchSelect($wire.$entangle(@js($model)), @js(array_values($options)), @js($empty))" x-on:click.outside="open = false" x-on:keydown.escape.stop="open = false" {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="relative">
        <input type="text" x-ref="input" x-model="query" x-on:focus="show()" x-on:input="show()" x-on:keydown.down.prevent="move(1)" x-on:keydown.up.prevent="move(-1)" x-on:keydown.enter.prevent="pick()"
            :placeholder="selected ? selected.label : @js($placeholder)"
            :class="selected && ! open ? 'placeholder:text-zinc-900' : ''"
            class="block w-full rounded-lg border-zinc-300 pr-16 text-sm shadow-xs focus:border-sky-500 focus:ring-sky-500" autocomplete="off" role="combobox" :aria-expanded="open">
        <div class="absolute inset-y-0 right-2 flex items-center gap-1">
            <button type="button" x-show="selected" x-cloak x-on:click="choose(null); $refs.input.focus()" class="rounded p-1 text-zinc-400 hover:text-zinc-700" title="{{ __('Wyczyść') }}" aria-label="{{ __('Wyczyść') }}">✕</button>
            <svg class="pointer-events-none h-4 w-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.3-4.3M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"/></svg>
        </div>
    </div>
    <ul x-show="open" x-cloak x-ref="list" role="listbox" class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto overscroll-contain rounded-lg bg-white py-1 text-sm shadow-lg ring-1 ring-zinc-200">
        <li>
            <button type="button" x-on:click="choose(null)" class="block w-full px-3 py-2 text-left text-zinc-500 hover:bg-zinc-50" x-text="emptyLabel"></button>
        </li>
        <template x-for="(option, index) in matches" :key="option.value">
            <li>
                <p x-show="groupStarts(index)" class="px-3 pt-2 pb-1 text-xs font-semibold tracking-wide text-zinc-400 uppercase" x-text="option.group"></p>
                <button type="button" x-on:click="choose(option)" x-on:mouseenter="active = index" :data-active="active === index"
                    :class="active === index ? 'bg-sky-50 text-sky-800' : 'text-zinc-800'"
                    class="block w-full px-3 py-2 text-left" x-text="option.label"></button>
            </li>
        </template>
        <li x-show="! matches.length" class="px-3 py-2 text-zinc-500">{{ __('Nic nie pasuje.') }}</li>
    </ul>
</div>
