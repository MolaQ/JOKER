@props(['label'])
{{-- Nazwa naszej drużyny z rozgrywek + dopisek mniejszą kursywą, np. LOMS Joker Piła (kadeci) --}}
<span {{ $attributes }}>{{ $label['name'] }}@if($label['suffix'])<em class="ml-1 text-[0.8em] font-normal text-current/70 normal-case">{{ $label['suffix'] }}</em>@endif</span>
