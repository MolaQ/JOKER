@props([
    'label',
    'field',
    'current' => '',
    // Wartość => etykieta.
    'options' => [],
    // Wartość => kolor (#rrggbb) paska z lewej, np. kolor kategorii drużyny.
    'colors' => [],
    'allLabel' => 'Wszystkie',
])
{{-- Grupa przycisków filtra (jeden wybór); kliknięcie wybranego wyłącza filtr. Na telefonie przewija się w bok. --}}
<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-col gap-1.5 md:flex-row md:items-center md:gap-3']) }} role="group" aria-label="{{ $label }}">
    <span class="w-24 shrink-0 text-[11px] font-bold tracking-wider text-zinc-500 uppercase">{{ $label }}</span>
    <div class="scrollbar-none -mx-4 flex gap-1.5 overflow-x-auto px-4 md:mx-0 md:flex-wrap md:overflow-visible md:px-0">
        @if($allLabel)
            <button type="button" wire:click="setFilter('{{ $field }}', '')" aria-pressed="{{ (string) $current === '' ? 'true' : 'false' }}" class="filter-chip filter-chip-all">{{ $allLabel }}</button>
        @endif
        @foreach($options as $value => $optionLabel)
            @php $color = $colors[$value] ?? null; @endphp
            <button type="button" wire:click="setFilter('{{ $field }}', @js((string) $value))" aria-pressed="{{ (string) $current === (string) $value ? 'true' : 'false' }}" @class(['filter-chip', 'filter-chip-colored' => $color]) @if($color) style="--chip: {{ $color }}" @endif>{{ $optionLabel }}</button>
        @endforeach
    </div>
</div>
