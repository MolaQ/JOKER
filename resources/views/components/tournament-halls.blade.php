@props(['tournament', 'matches'])
{{-- Legenda pod terminarzem: obiekty z meczami, ich adresy, sale i ikony boisk (ikona przy meczu = to boisko) --}}
@php
    $courts = collect($matches)->pluck('court')->filter()->unique();
    $mainNames = collect(\App\Models\TrainingSession::venueNames())->except('gym');
    $legend = collect(\App\Support\TournamentHalls::legend($tournament))
        ->filter(function (array $facility, int $index) use ($courts, $mainNames) {
            if ($index === 0) {
                return $courts->contains(fn ($court) => $mainNames->contains(\App\Models\TrainingSession::courtLabel($court)));
            }
            $values = collect($facility['rooms'])->flatMap(fn ($room) => collect($room['courts'])->pluck('value'));

            return $courts->contains(fn ($court) => $values->contains($court));
        })
        ->values();
@endphp
@if($legend->isNotEmpty())
    {{-- Zwięźle i dyskretnie: obiekt, adres i tylko boiska, na których są te mecze --}}
    <div {{ $attributes->merge(['class' => 'space-y-1.5 border-t border-zinc-200 pt-3 text-xs text-zinc-500']) }}>
        @foreach($legend as $facility)
            @php
                $used = fn (array $court) => $courts->contains($court['value']) || ($facility['color'] === null && $courts->contains(fn ($value) => \App\Models\TrainingSession::courtLabel($value) === $court['value']));
            @endphp
            @php $title = \App\Support\TournamentHalls::legendTitle($facility); @endphp
            {{-- Kolejność: nazwa obiektu (pełna, np. szkoły), adres, potem sale, sektory i boiska --}}
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="inline-flex items-center gap-1.5 font-semibold text-zinc-700"><span class="size-2 shrink-0 rounded-full" style="background: {{ $facility['color'] ?? 'var(--color-sky-club, #45a0e0)' }}"></span>{{ $title }}@if($title !== $facility['name'])<span class="font-normal text-zinc-400">({{ $facility['name'] }})</span>@endif</span>
                @if($facility['address'])
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($facility['address']) }}" target="_blank" rel="noopener" class="hover:text-sky-club-dark hover:underline">{{ $facility['address'] }}</a>
                @endif
                @foreach($facility['rooms'] as $room)
                    @php $roomCourts = array_filter($room['courts'], $used); @endphp
                    @if($roomCourts !== [])
                        <span class="inline-flex flex-wrap items-center gap-x-2.5 gap-y-1">
                            @if($room['name'] !== '' && $room['name'] !== $title && count($room['courts']) > 1)<span class="text-zinc-400">{{ $room['name'] }}:</span>@endif
                            @foreach($roomCourts as $court)
                                <span class="inline-flex items-center gap-1"><x-court-icon :icon="$court['icon']" class="!h-3 !w-5 opacity-80" />@if($court['label'] !== $title){{ $court['label'] }}@endif</span>
                            @endforeach
                        </span>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
@endif
