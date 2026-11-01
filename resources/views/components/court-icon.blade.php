@props(['court' => null, 'category' => null, 'icon' => null])
{{-- Ikona boiska meczu: sektor hali klubowej albo część innej hali turnieju (podzielonej na tyle części, ile ma boisk) --}}
@php $icon ??= \App\Support\TournamentHalls::icon($court, $category); @endphp
@if($icon)
    <x-venue-icon :venue="$icon['venue'] ?? null" :parts="$icon['parts'] ?? null" :index="$icon['index'] ?? null" small {{ $attributes }} />
@endif
