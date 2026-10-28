@props(['field', 'current' => '', 'direction' => 'asc'])
{{-- Nagłówek kolumny do sortowania w panelu (trait SortsTable): kliknięcie sortuje, drugie odwraca, trzecie wyłącza --}}
@php $active = $current === $field; @endphp
<button type="button" data-sort-button wire:click="sortBy('{{ $field }}')" {{ $attributes->merge(['class' => 'group inline-flex items-center gap-1 rounded text-left font-semibold whitespace-nowrap hover:text-sky-700 focus-visible:outline-2 focus-visible:outline-sky-500'.($active ? ' text-sky-800' : '')]) }} title="Sortuj" aria-label="{{ trim(strip_tags($slot)) }}: sortuj{{ $active ? ($direction === 'asc' ? ' (teraz rosnąco)' : ' (teraz malejąco)') : '' }}">
    <span>{{ $slot }}</span>
    <svg @class(['h-3.5 w-3.5 shrink-0', 'text-sky-700' => $active, 'text-gray-300 group-hover:text-gray-500' => ! $active]) viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
        @if(! $active || $direction === 'asc')<path d="M6 1.5 9.5 5h-7z" @if($active) opacity="1" @endif />@endif
        @if(! $active || $direction === 'desc')<path d="M6 10.5 2.5 7h7z" />@endif
    </svg>
</button>
