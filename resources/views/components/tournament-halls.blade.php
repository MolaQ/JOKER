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
    <div {{ $attributes->merge(['class' => 'grid gap-3 sm:grid-cols-2 lg:grid-cols-3']) }}>
        @foreach($legend as $facility)
            <div class="rounded-2xl border-t-4 bg-white p-4 ring-1 ring-zinc-200" style="border-top-color: {{ $facility['color'] ?? 'var(--color-sky-club, #45a0e0)' }}">
                <p class="font-display text-lg font-bold text-zinc-900 uppercase">{{ $facility['name'] }}</p>
                @if($facility['address'])
                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($facility['address']) }}" target="_blank" rel="noopener" class="mt-0.5 inline-flex items-center gap-1 text-sm text-sky-club-dark hover:underline">
                        <svg class="size-3.5 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd"/></svg>{{ $facility['address'] }}
                    </a>
                @endif
                @foreach($facility['rooms'] as $room)
                    @if($room['name'] !== '' && count($room['courts']) > 1)<p class="mt-3 text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $room['name'] }}</p>@endif
                    <ul @class(['flex flex-wrap gap-x-4 gap-y-2 text-sm text-zinc-600', 'mt-3' => $room['name'] === '' || count($room['courts']) === 1, 'mt-1' => $room['name'] !== '' && count($room['courts']) > 1])>
                        @foreach($room['courts'] as $court)
                            <li class="inline-flex items-center gap-2"><x-court-icon :icon="$court['icon']" />{{ $court['label'] }}</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        @endforeach
    </div>
@endif
