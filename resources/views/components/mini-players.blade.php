@props(['group', 'size' => 'md'])
{{-- Liczba graczy na boisku jako ikonki zawodników w kolorze grupy (kolor ustawia się w panelu przy grupie). --}}
@php
    $box = ['xs' => 'size-3.5', 'sm' => 'size-5', 'md' => 'size-6', 'lg' => 'size-9'][$size] ?? 'size-6';
    $gap = ['xs' => 'gap-0', 'sm' => 'gap-0.5', 'md' => 'gap-1', 'lg' => 'gap-1.5'][$size] ?? 'gap-1';
@endphp
<span {{ $attributes->merge(['class' => "inline-flex items-end {$gap}"]) }} style="color: {{ $group->iconColor() }}" role="img" aria-label="{{ trans_choice(':count gracz na boisku|:count graczy na boisku|:count graczy na boisku', $group->players_on_court) }}">
    @for($i = 0; $i < $group->players_on_court; $i++)
        <svg viewBox="0 0 24 24" class="{{ $box }} shrink-0 drop-shadow-sm" fill="currentColor" aria-hidden="true"><circle cx="12" cy="7.2" r="4.6"/><path d="M3.5 22c0-5 3.8-8.6 8.5-8.6s8.5 3.6 8.5 8.6z"/></svg>
    @endfor
</span>
