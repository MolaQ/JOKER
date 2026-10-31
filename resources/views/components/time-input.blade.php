@props([
    'model',
    // true: wartość idzie do Livewire od razu po wyjściu z pola (jak wire:model.blur.live).
    'live' => false,
    'disabled' => false,
])
{{-- Godzina w formacie 24-godzinnym „GG:MM” niezależnie od języka systemu (resources/js/time-input.js). --}}
<div x-data="time24($wire.$entangle(@js($model), @js((bool) $live)))" class="relative">
    <input type="text" x-ref="time" x-model="time" x-on:input="mask($event)" x-on:blur="commit()" x-on:keydown.enter.prevent="commit()"
        inputmode="numeric" maxlength="5" placeholder="GG:MM" autocomplete="off" @disabled($disabled)
        :class="invalid ? 'border-red-500 ring-1 ring-red-500' : ''"
        {{ $attributes->merge(['class' => 'tabular-nums']) }}>
    <p x-show="invalid" x-cloak class="mt-1 text-xs text-red-600">{{ __('Wpisz godzinę jak 18:30.') }}</p>
</div>
