@props([
    'model',
    'live' => false,
    'disabled' => false,
])
{{-- Data i godzina w formacie 24-godzinnym; do Livewire trafia „2026-11-15T10:45” (resources/js/time-input.js). --}}
<div x-data="datetime24($wire.$entangle(@js($model), @js((bool) $live)))" class="grid grid-cols-[1fr_6rem] gap-2">
    <input type="date" x-model="date" x-on:change="commit()" @disabled($disabled) {{ $attributes->merge(['class' => '']) }}>
    <input type="text" x-ref="time" x-model="time" x-on:input="mask($event)" x-on:blur="commit()" x-on:keydown.enter.prevent="commit()"
        inputmode="numeric" maxlength="5" placeholder="GG:MM" autocomplete="off" aria-label="{{ __('Godzina') }}" @disabled($disabled)
        :class="invalid ? 'border-red-500 ring-1 ring-red-500' : ''"
        {{ $attributes->merge(['class' => 'tabular-nums']) }}>
    <p x-show="invalid" x-cloak class="col-span-2 text-xs text-red-600">{{ __('Wpisz datę i godzinę jak 18:30.') }}</p>
</div>
