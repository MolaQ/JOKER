@props(['field', 'current' => '', 'direction' => 'asc', 'hint' => null])
{{-- Nagłówek kolumny do sortowania w panelu (trait SortsTable): kliknięcie sortuje, drugie odwraca, trzecie wyłącza --}}
@php $active = $current === $field; @endphp
<button type="button" data-sort-button wire:click="sortBy('{{ $field }}')" {{ $attributes->merge(['class' => 'group -mx-1.5 inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-left font-semibold whitespace-nowrap transition hover:bg-zinc-200/60 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-sky-500'.($active ? ' bg-sky-100 text-sky-800 hover:bg-sky-100 hover:text-sky-900' : '')]) }} title="{{ $hint ? $hint.': sortuj' : 'Kliknij, aby posortować' }}" aria-label="{{ trim(strip_tags($slot)) }}: sortuj{{ $active ? ($direction === 'asc' ? ' (teraz rosnąco)' : ' (teraz malejąco)') : '' }}">
    <span>{{ $slot }}</span>
    <svg @class(['h-3.5 w-3.5 shrink-0', 'text-sky-700' => $active, 'text-zinc-400 group-hover:text-zinc-600' => ! $active]) viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
        @if(! $active || $direction === 'asc')<path d="M6 1.5 9.5 5h-7z" />@endif
        @if(! $active || $direction === 'desc')<path d="M6 10.5 2.5 7h7z" />@endif
    </svg>
</button>
