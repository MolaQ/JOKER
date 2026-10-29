@props(['model', 'value' => null, 'free' => true])
@php
    // Wybór boiska ikoną, ułożony jak w sali: Boisko 3 z lewej, 2 w środku, 1 z prawej, do tego całe (główne) boisko.
    // Inną nazwę (np. hala w innym mieście) można wpisać w polu pod ikonami.
    $venues = \App\Models\TrainingSession::VENUES;
    $current = \App\Models\TrainingSession::venueForCourt($value);
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <div class="flex items-center gap-1" role="group" aria-label="Boisko">
        @foreach(['court3', 'court2', 'court1', 'main'] as $venue)
            <button type="button" wire:click="$set('{{ $model }}', '{{ $current === $venue ? '' : $venues[$venue] }}')" title="{{ $venues[$venue] }}" aria-pressed="{{ $current === $venue ? 'true' : 'false' }}"
                @class(['rounded-md p-1 ring-1 transition', 'bg-sky-50 ring-sky-500' => $current === $venue, 'ring-transparent opacity-50 hover:opacity-100 hover:ring-gray-300' => $current !== $venue])>
                <x-venue-icon :venue="$venue" small />
                <span class="sr-only">{{ $venues[$venue] }}</span>
            </button>
        @endforeach
    </div>
    @if($free)
        <input type="text" wire:model.blur.live="{{ $model }}" class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs" placeholder="albo wpisz inną nazwę">
    @elseif(filled($value) && ! $current)
        <p class="truncate text-xs text-gray-500" title="{{ $value }}">{{ $value }}</p>
    @endif
</div>
