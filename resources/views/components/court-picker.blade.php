@props(['model', 'value' => null, 'free' => true, 'halls' => [], 'main' => \App\Support\TournamentHalls::MAIN_VENUES])
@php
    // Boisko meczu: na co dzień widać tylko wybrane boisko (ikona i nazwa w kolorze obiektu), a pełny wybór otwiera „Zmień”.
    // Po otwarciu najpierw obiekt (hala klubowa albo inny obiekt turnieju), potem jego sala i sektor; zmiany są rzadkie.
    $venues = \App\Models\TrainingSession::venueNames();
    $current = \App\Models\TrainingSession::venueForCourt($value);
    $mainName = \App\Support\TournamentHalls::mainName();
    $found = null;
    foreach ($halls as $hallIndex => $hall) {
        foreach ($hall['rooms'] as $room) {
            foreach ($room['courts'] as $court) {
                if ($court['value'] === $value) {
                    $found = ['hall' => $hall, 'room' => $room, 'court' => $court, 'tab' => $hallIndex + 1];
                }
            }
        }
    }
    $tab = $found['tab'] ?? 0;
@endphp
<div x-data="{ open: false, tab: {{ $tab }} }" {{ $attributes->merge(['class' => 'relative']) }}>
    {{-- Wybrane boisko --}}
    <div class="flex items-center gap-2">
        @if($found)
            <span class="flex min-w-0 items-center gap-1.5 rounded-md border-l-4 bg-gray-50 py-1 pr-2 pl-1.5" style="border-color: {{ $found['hall']['color'] }}" title="{{ $value }}">
                <x-venue-icon :parts="$found['room']['sectors']" :index="$found['court']['index']" :whole="$found['court']['whole']" :color="$found['hall']['color']" :off="$found['room']['off']" small />
                <span class="min-w-0 text-xs leading-tight">
                    <span class="block font-semibold break-words" style="color: {{ $found['hall']['color'] }}">{{ $found['hall']['name'] }}@if($found['room']['name'] !== '' && $found['room']['sectors'] > 1) · {{ $found['room']['name'] }}@endif</span>
                    <span class="block truncate text-gray-700">{{ $found['court']['label'] }}</span>
                </span>
            </span>
        @elseif($current)
            <span class="flex min-w-0 items-center gap-1.5 rounded-md border-l-4 border-sky-500 bg-gray-50 py-1 pr-2 pl-1.5" title="{{ $value }}">
                <x-venue-icon :venue="$current" small />
                <span class="min-w-0 text-xs leading-tight">
                    @if($halls !== [])<span class="block truncate font-semibold text-sky-700">{{ $mainName }}</span>@endif
                    <span class="block truncate text-gray-700">{{ $venues[$current] }}</span>
                </span>
            </span>
        @elseif(filled($value))
            <span class="max-w-40 truncate rounded-md bg-gray-50 px-2 py-1 text-xs text-gray-700" title="{{ $value }}">{{ $value }}</span>
        @else
            <span class="text-xs text-gray-400">bez boiska</span>
        @endif
        <button type="button" x-on:click="open = ! open" class="shrink-0 text-xs font-semibold text-sky-600 hover:text-sky-800" x-text="open ? 'Zamknij' : '{{ filled($value) ? 'Zmień' : 'Wybierz' }}'">{{ filled($value) ? 'Zmień' : 'Wybierz' }}</button>
    </div>

    {{-- Pełny wybór: obiekt, potem boisko --}}
    <div x-show="open" x-cloak x-on:click.outside="open = false" class="absolute top-full left-0 z-30 mt-1 w-72 max-w-[90vw] space-y-2 rounded-lg bg-white p-2 shadow-lg ring-1 ring-gray-200">
        @if($halls !== [])
            <div class="flex flex-wrap gap-1" role="tablist" aria-label="Obiekt">
                <button type="button" x-on:click="tab = 0" :class="tab === 0 ? 'bg-sky-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-300'" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ $mainName }}</button>
                @foreach($halls as $hall)
                    <button type="button" x-on:click="tab = {{ $loop->iteration }}" :style="tab === {{ $loop->iteration }} ? 'background: {{ $hall['color'] }}; color: #fff' : 'color: {{ $hall['color'] }}; box-shadow: inset 0 0 0 1px {{ $hall['color'] }}'" class="rounded-full px-2.5 py-1 text-xs font-semibold">{{ $hall['name'] }}</button>
                @endforeach
            </div>
        @endif

        <div x-show="tab === 0" class="flex flex-wrap gap-1" role="group" aria-label="{{ $mainName }}">
            @foreach($main as $venue)
                <button type="button" wire:click="$set('{{ $model }}', '{{ $venues[$venue] }}')" x-on:click="open = false" title="{{ $venues[$venue] }}" aria-pressed="{{ $current === $venue ? 'true' : 'false' }}"
                    @class(['flex w-16 flex-col items-center rounded-md p-1 ring-1 transition', 'bg-sky-50 ring-2 ring-sky-500' => $current === $venue, 'ring-gray-200 hover:ring-gray-400' => $current !== $venue])>
                    <x-venue-icon :venue="$venue" small />
                    <span class="mt-0.5 block max-w-full truncate text-[10px] leading-none font-semibold text-gray-600">{{ $venues[$venue] }}</span>
                </button>
            @endforeach
        </div>

        @foreach($halls as $hall)
            <div x-show="tab === {{ $loop->iteration }}" x-cloak class="space-y-1.5" role="group" aria-label="{{ $hall['name'] }}">
                @foreach($hall['rooms'] as $room)
                    <div>
                        @if($room['name'] !== '' && $room['sectors'] > 1)<p class="text-[10px] font-semibold text-gray-500 uppercase">{{ $room['name'] }}</p>@endif
                        <div class="flex flex-wrap gap-1">
                            @foreach($room['courts'] as $court)
                                <button type="button" wire:click="$set('{{ $model }}', @js($court['value']))" x-on:click="open = false" title="{{ $court['value'] }}" aria-pressed="{{ $value === $court['value'] ? 'true' : 'false' }}"
                                    @class(['flex w-16 flex-col items-center rounded-md p-1 transition', 'ring-2' => $value === $court['value'], 'ring-1 ring-gray-200 hover:ring-gray-400' => $value !== $court['value']])
                                    @if($value === $court['value']) style="--tw-ring-color: {{ $hall['color'] }}; background: {{ $hall['color'] }}14" @endif>
                                    <x-venue-icon :parts="$room['sectors']" :index="$court['index']" :whole="$court['whole']" :color="$hall['color']" :off="$room['off']" small />
                                    <span class="mt-0.5 block max-w-full truncate text-[10px] leading-none font-semibold text-gray-600">{{ $court['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <div class="flex items-center gap-2 border-t border-gray-100 pt-2">
            @if($free)
                <input type="text" wire:model.blur.live="{{ $model }}" class="block min-w-0 flex-1 rounded-md border-gray-300 px-2 py-1 text-xs" placeholder="albo wpisz inną nazwę">
            @endif
            @if(filled($value))
                <button type="button" wire:click="$set('{{ $model }}', '')" x-on:click="open = false" class="ml-auto shrink-0 text-xs text-gray-500 hover:text-red-600">Bez boiska</button>
            @endif
        </div>
    </div>
</div>
