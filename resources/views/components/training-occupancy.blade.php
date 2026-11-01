@props(['sessions', 'highlight' => null])
{{-- Obłożenie sali w planie treningów: dla każdego dnia najpierw sektory (jak w hali: III, II, I; główne boisko
     na całą szerokość), obok siłownia. Kolory kategorii wiekowych są wspólne z turniejem (Ustawienia → Moduły). --}}
@php
    use App\Models\TrainingSession;
    use App\Support\AgeColors;
    use App\Support\TrainingOccupancy;

    $days = TrainingOccupancy::days($sessions);
    $names = TrainingSession::venueNames();
    $today = now()->isoWeekday();
    $firstDay = $days->has($today) ? $today : $days->keys()->first();
    $scale = 1.2; // piksele na minutę
    $groups = $sessions->map(fn ($session) => $session->team?->category && isset(AgeColors::GROUPS[$session->team->category]) ? $session->team->category : (AgeColors::groupForName($session->team?->name) ?? 'senior'))->unique();
@endphp
@if($days->isNotEmpty())
<div {{ $attributes->merge(['class' => 'space-y-4']) }} x-data="{ day: {{ (int) $firstDay }} }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{-- Dni tygodnia --}}
        <div class="inline-flex flex-wrap gap-1 rounded-lg bg-zinc-100 p-1" role="tablist" aria-label="{{ __('Dzień tygodnia') }}">
            @foreach($days as $weekday => $day)
                <button type="button" role="tab" x-on:click="day = {{ $weekday }}" :aria-selected="day === {{ $weekday }}"
                    :class="day === {{ $weekday }} ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-md px-3 py-1.5 text-sm font-semibold">{{ mb_substr(__(TrainingSession::WEEKDAYS[$weekday]), 0, 3) }}@if($weekday === $today)<span class="ml-1 text-xs font-normal text-sky-club-dark">{{ __('dziś') }}</span>@endif</button>
            @endforeach
        </div>
        {{-- Legenda kolorów kategorii --}}
        <div class="flex flex-wrap gap-1.5 text-xs">
            @foreach(AgeColors::GROUPS as $group => [$groupLabel])
                @continue(! $groups->contains($group))
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white px-2 py-0.5 font-semibold text-zinc-800 ring-1 ring-zinc-200"><span class="size-2.5 rounded-full" style="background: {{ AgeColors::color($group) }}"></span>{{ __($groupLabel) }}</span>
            @endforeach
            <span class="rounded-full bg-white px-2 py-0.5 font-semibold text-red-700 ring-2 ring-red-500">{{ __('nakłada się') }}</span>
        </div>
    </div>

    @foreach($days as $weekday => $day)
        @php $height = (int) round(($day['end'] - $day['start']) * $scale); @endphp
        <section x-show="day === {{ $weekday }}" @if($weekday !== $firstDay) x-cloak @endif aria-label="{{ __(TrainingSession::WEEKDAYS[$weekday]) }}">
            <div class="overflow-x-auto pb-1">
                {{-- Na telefonie wszystko mieści się na szerokość ekranu: węższa oś godzin i siłownia, krótsze nagłówki. --}}
                <div class="ytto-grid grid min-w-[21rem] gap-x-1.5 gap-y-2 sm:gap-x-3">
                    {{-- Nagłówki: sektory w kolejności jak w hali, potem siłownia --}}
                    <div></div>
                    <div class="grid grid-cols-3 gap-1">
                        @foreach(TrainingOccupancy::SECTORS as $sector)
                            <p class="flex items-center justify-center gap-1.5 truncate text-[10px] font-semibold tracking-wide text-zinc-500 uppercase sm:text-xs"><x-venue-icon :venue="$sector" small class="hidden sm:inline-flex" />{{ $names[$sector] }}</p>
                        @endforeach
                    </div>
                    <p class="flex items-center justify-center gap-1.5 truncate text-[10px] font-semibold tracking-wide text-zinc-500 uppercase sm:text-xs"><x-venue-icon venue="gym" small class="hidden sm:inline-flex" />{{ $names['gym'] }}</p>

                    {{-- Oś godzin --}}
                    <div class="relative" style="height: {{ $height }}px">
                        @for($minute = $day['start']; $minute <= $day['end']; $minute += 60)
                            <span class="absolute right-0.5 -translate-y-1/2 text-[10px] text-zinc-400 tabular-nums sm:right-1 sm:text-[11px]" style="top: {{ (int) round(($minute - $day['start']) * $scale) }}px">{{ TrainingOccupancy::clock($minute) }}</span>
                        @endfor
                    </div>

                    @foreach(['courts' => $day['courts'], 'gym' => $day['gym']] as $part => $blocks)
                        <div class="relative rounded-md bg-zinc-50 ring-1 ring-zinc-200" style="height: {{ $height }}px; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent {{ 60 * $scale - 1 }}px, rgb(228 228 231) {{ 60 * $scale - 1 }}px, rgb(228 228 231) {{ 60 * $scale }}px){{ $part === 'courts' ? ', linear-gradient(to right, transparent calc(33.333% - 1px), rgb(212 212 216) calc(33.333% - 1px), rgb(212 212 216) 33.333%, transparent 33.333%, transparent calc(66.666% - 1px), rgb(212 212 216) calc(66.666% - 1px), rgb(212 212 216) 66.666%, transparent 66.666%)' : '' }}">
                            @foreach($blocks as $block)
                                @php $session = $block['session']; $dim = $highlight && $session->team_id !== (int) $highlight; @endphp
                                <div @class(['absolute overflow-hidden rounded border-l-4 px-1 py-1 text-[10px] leading-tight sm:px-1.5 sm:text-[11px] text-zinc-900', 'ring-1' => ! $block['conflict'], 'ring-2 ring-red-500' => $block['conflict'], 'opacity-35' => $dim])
                                    style="top: {{ (int) round(($block['from'] - $day['start']) * $scale) }}px; height: {{ max(18, (int) round(($block['to'] - $block['from']) * $scale) - 2) }}px; left: calc({{ round($block['left'], 3) }}% + 2px); width: calc({{ round($block['width'], 3) }}% - 4px); background: color-mix(in srgb, {{ $block['color'] }} 20%, white); border-left-color: {{ $block['color'] }}; @if(! $block['conflict']) --tw-ring-color: color-mix(in srgb, {{ $block['color'] }} 45%, white); @endif"
                                    title="{{ $session->timeRange() }} · {{ $session->team?->name }} · {{ $session->venueLabel() }}@if($session->note) · {{ $session->note }}@endif">
                                    <p class="font-semibold tabular-nums">{{ $session->timeRange() }}</p>
                                    <p class="truncate font-medium">{{ $session->team?->name }}</p>
                                    @if($session->venue === 'main')<p class="truncate opacity-75">{{ $names['main'] }}</p>@endif
                                    @if($session->note)<p class="truncate opacity-75">{{ $session->note }}</p>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach
</div>
@endif
