@props(['label', 'badge' => true])
{{-- Nazwa naszej drużyny z rozgrywek + plakietka drużyny, np. LOMS Joker Piła [KADECI II] --}}
<span {{ $attributes }}>{{ $label['name'] }}@if($badge && ($label['badge'] ?? null)) <x-team-chip :name="$label['badge']" class="ml-1" />@endif</span>
