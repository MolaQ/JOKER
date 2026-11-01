@props(['model', 'value' => null, 'free' => true, 'halls' => []])
@php
    // Wybór boiska ikoną, ułożony jak w sali: część III z lewej, II w środku, I z prawej, do tego całe (główne) boisko.
    // Inne hale turnieju (np. MOSiR, szkoła) mają swoje boiska jako przyciski z nazwą; mecz można przenieść w każdej chwili.
    $venues = \App\Models\TrainingSession::venueNames();
    $current = \App\Models\TrainingSession::venueForCourt($value);
    $hallValue = fn (string $hall, string $court) => \App\Support\TournamentHalls::value($hall, $court);
@endphp
<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    <div class="flex flex-wrap items-center gap-1" role="group" aria-label="Boisko">
        @if($halls !== [])<span class="mr-1 w-16 shrink-0 text-[10px] leading-tight font-semibold break-words text-gray-500 uppercase" title="{{ \App\Support\TournamentHalls::mainName() }}">{{ \App\Support\TournamentHalls::mainName() }}</span>@endif
        @foreach(['court3', 'court2', 'court1', 'main'] as $venue)
            <button type="button" wire:click="$set('{{ $model }}', '{{ $current === $venue ? '' : $venues[$venue] }}')" title="{{ $venues[$venue] }}" aria-pressed="{{ $current === $venue ? 'true' : 'false' }}"
                @class(['flex flex-col items-center rounded-md p-1 ring-1 transition', 'bg-sky-50 ring-sky-500' => $current === $venue, 'ring-transparent opacity-50 hover:opacity-100 hover:ring-gray-300' => $current !== $venue])>
                <x-venue-icon :venue="$venue" small />
                @if($halls !== [])<span class="mt-0.5 block max-w-14 truncate text-[9px] leading-none font-semibold text-gray-600">{{ $venues[$venue] }}</span>@else<span class="sr-only">{{ $venues[$venue] }}</span>@endif
            </button>
        @endforeach
    </div>
    @foreach($halls as $hall)
        <div class="flex flex-wrap items-center gap-1" role="group" aria-label="{{ $hall['name'] }}">
            <span class="mr-1 w-16 shrink-0 text-[10px] leading-tight font-semibold break-words text-gray-500 uppercase" title="{{ $hall['name'] }}">{{ $hall['name'] }}</span>
            @foreach($hall['courts'] as $courtIndex => $court)
                @php $courtValue = $hallValue($hall['name'], $court); @endphp
                {{-- Ikona hali podzielonej na tyle części, ile ma boisk; podpis to nazwa boiska --}}
                <button type="button" wire:click="$set('{{ $model }}', @js($value === $courtValue ? '' : $courtValue))" title="{{ $hall['name'] }} · {{ $court }}" aria-pressed="{{ $value === $courtValue ? 'true' : 'false' }}"
                    @class(['flex flex-col items-center rounded-md p-1 ring-1 transition', 'bg-sky-50 ring-sky-500' => $value === $courtValue, 'ring-transparent opacity-60 hover:opacity-100 hover:ring-gray-300' => $value !== $courtValue])>
                    <x-venue-icon :parts="count($hall['courts'])" :index="$courtIndex" small />
                    <span class="mt-0.5 max-w-14 truncate text-[9px] leading-none font-semibold text-gray-600">{{ $court }}</span>
                </button>
            @endforeach
        </div>
    @endforeach
    @if($free)
        <input type="text" wire:model.blur.live="{{ $model }}" class="block w-full rounded-md border-gray-300 px-2 py-1 text-xs" placeholder="albo wpisz inną nazwę">
    @elseif(filled($value) && ! $current && ! collect($halls)->contains(fn ($hall) => str_starts_with((string) $value, $hall['name'].\App\Support\TournamentHalls::SEPARATOR)))
        <p class="truncate text-xs text-gray-500" title="{{ $value }}">{{ $value }}</p>
    @endif
</div>
