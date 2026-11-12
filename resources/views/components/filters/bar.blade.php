@props([
    // Liczba znalezionych pozycji i jej odmiana, np. [1 => 'mecz', 2 => 'mecze', 5 => 'meczów'].
    'total' => null,
    'nouns' => ['pozycja', 'pozycje', 'pozycji'],
    'active' => 0,
    'placeholder' => 'Szukaj…',
    'searchModel' => 'search',
])
@php
    $plural = function (int $n) use ($nouns): string {
        if ($n === 1) {
            return $nouns[0];
        }
        $last = $n % 10;
        $lastTwo = $n % 100;

        return $last >= 2 && $last <= 4 && ($lastTwo < 12 || $lastTwo > 14) ? $nouns[1] : $nouns[2];
    };
@endphp
{{--
    Pasek filtrów listy w panelu (trait FiltersList): wyszukiwarka, przyciski filtrów w grupach, zakres dat.
    Na telefonie filtry chowają się pod przyciskiem „Filtry” (otwarte, gdy któryś działa).
--}}
<div {{ $attributes->merge(['class' => 'admin-filters @container border-b border-zinc-200 bg-white']) }} x-data="{ open: {{ $active > 0 ? 'true' : 'false' }} }">
    <div class="flex flex-wrap items-center gap-2 p-4 pb-3">
        <label class="relative min-w-0 flex-1 basis-56">
            <span class="sr-only">{{ $placeholder }}</span>
            <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
            <input type="search" wire:model.live.debounce.300ms="{{ $searchModel }}" placeholder="{{ $placeholder }}" class="w-full rounded-xl border-zinc-200 bg-zinc-50 py-2.5 pl-9 text-sm focus:bg-white" data-filter-search>
        </label>
        <button type="button" x-on:click="open = ! open" :aria-expanded="open" class="inline-flex items-center gap-2 rounded-xl border border-zinc-200 px-3 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 md:hidden">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
            Filtry
            @if($active)<span class="rounded-full bg-sky-600 px-1.5 text-xs text-white">{{ $active }}</span>@endif
        </button>
        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    {{-- Na szerokiej karcie krótkie grupy stoją w dwóch kolumnach; długie (drużyny, daty) dostają @4xl:col-span-2. --}}
    <div class="grid gap-x-8 gap-y-2.5 px-4 pb-4 md:grid! @4xl:grid-cols-2" x-show="open" x-cloak>
        {{ $slot }}
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-100 bg-zinc-50/70 px-4 py-2 text-sm text-zinc-600">
        <span data-filter-total>
            @if(! is_null($total))
                Znaleziono <strong class="font-semibold text-zinc-900 tabular-nums">{{ number_format($total, 0, ',', ' ') }}</strong> {{ $plural((int) $total) }}
            @endif
        </span>
        @if($active)
            <button type="button" wire:click="clearFilters" class="inline-flex items-center gap-1 font-semibold text-sky-700 hover:text-sky-900" data-filter-clear>
                <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                Wyczyść filtry ({{ $active }})
            </button>
        @endif
    </div>
</div>
