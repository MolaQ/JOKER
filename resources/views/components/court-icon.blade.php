@props(['court' => null, 'category' => null, 'icon' => null])
{{-- Ikona boiska meczu: sektor hali klubowej albo boisko sali innego obiektu (w kolorze obiektu) --}}
@php $icon ??= \App\Support\TournamentHalls::icon($court, $category); @endphp
@if($icon)
    <x-venue-icon :venue="$icon['venue'] ?? null" :parts="$icon['parts'] ?? null" :index="$icon['index'] ?? null" :whole="$icon['whole'] ?? false" :color="$icon['color'] ?? null" :off="$icon['off'] ?? []" :separate="$icon['separate'] ?? false" small {{ $attributes }} />
@endif
