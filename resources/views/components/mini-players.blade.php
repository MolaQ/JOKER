@props(['group', 'size' => 'md'])
{{-- Znak grupy mini: tyle kółek z sylwetką zawodnika, ilu graczy jest na boisku (czwórki = 4), w kolorze grupy,
     zachodzące na siebie jak awatary drużyny (kolor ustawia się w panelu przy grupie). --}}
@php
    $box = ['xs' => 'size-4', 'sm' => 'size-5', 'md' => 'size-7', 'lg' => 'size-11'][$size] ?? 'size-7';
    $overlap = ['xs' => '-ml-1', 'sm' => '-ml-1.5', 'md' => '-ml-2', 'lg' => '-ml-3'][$size] ?? '-ml-2';
    $ring = ['xs' => 'ring-1', 'sm' => 'ring-[1.5px]', 'md' => 'ring-2', 'lg' => 'ring-[3px]'][$size] ?? 'ring-2';
    $color = $group->iconColor();
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex shrink-0 items-center']) }} role="img" aria-label="{{ trans_choice(':count gracz na boisku|:count graczy na boisku|:count graczy na boisku', $group->players_on_court) }}">
    @for($i = 0; $i < max(1, (int) $group->players_on_court); $i++)
        <span @class(["{$box} {$ring} relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full ring-white shadow-sm", $overlap => $i > 0]) style="background: linear-gradient(145deg, color-mix(in srgb, {{ $color }} 75%, white), {{ $color }} 55%, color-mix(in srgb, {{ $color }} 70%, black)); z-index: {{ 10 - $i }}" aria-hidden="true">
            <svg viewBox="0 0 24 24" class="absolute inset-x-0 bottom-0 mx-auto h-[82%] w-[82%] text-white/95" fill="currentColor"><circle cx="12" cy="8.3" r="4.4"/><path d="M3.2 24c0-5.2 3.9-9 8.8-9s8.8 3.8 8.8 9z"/></svg>
        </span>
    @endfor
</span>
