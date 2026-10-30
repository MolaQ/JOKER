@props(['tournament'])
{{-- Obłożenie sektorów: oś czasu dnia, kolumny sektorów jak w hali, mecze wszystkich kategorii w kolorach kategorii --}}
@php
    $days = \App\Support\TournamentSchedule::occupancy($tournament);
    // Kolor kategorii ustawiasz w zakładce „Kategorie”; kafel ma jasne tło i ramkę w tym kolorze.
    $colors = $tournament->categories->mapWithKeys(fn ($category) => [$category->id => $category->color()]);
    $today = now()->toDateString();
    $firstDay = $days->has($today) ? $today : $days->keys()->first();
    $scale = 1.1; // piksele na minutę
@endphp
<div {{ $attributes->merge(['class' => 'space-y-4']) }} x-data="{ day: @js($firstDay) }">
    @if($days->isEmpty())
        <p class="text-sm text-gray-500">Żaden mecz nie ma jeszcze godziny i sektora.</p>
    @else
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach($tournament->categories as $category)
                <span class="rounded-full px-2 py-0.5 font-semibold text-gray-900 ring-1" style="background: {{ $colors[$category->id] }}26; --tw-ring-color: {{ $colors[$category->id] }}">{{ $category->name }}</span>
            @endforeach
            <span class="rounded-full bg-white px-2 py-0.5 font-semibold text-red-700 ring-2 ring-red-500">nakłada się</span>
        </div>
        @if($days->count() > 1)
            {{-- Przełącznik dni turnieju --}}
            <div class="inline-flex rounded-lg bg-gray-100 p-1" role="tablist">
                @foreach($days as $date => $day)
                    <button type="button" role="tab" x-on:click="day = @js($date)" :aria-selected="day === @js($date)"
                        :class="day === @js($date) ? 'bg-club-navy text-white shadow' : 'text-gray-600 hover:text-gray-900'"
                        class="rounded-md px-3 py-1.5 text-sm font-semibold">{{ \Illuminate\Support\Str::ucfirst($day['start']->translatedFormat('D d.m')) }}@if($date === $today) · dziś @endif</button>
                @endforeach
            </div>
        @endif
    @endif
    @foreach($days as $date => $day)
        @php
            $minutes = (int) $day['start']->diffInMinutes($day['end']) + 15;
            $height = (int) round($minutes * $scale);
        @endphp
        <section x-show="day === @js($date)" @if($date !== $firstDay) x-cloak @endif>
            <h4 class="mb-2 text-sm font-semibold text-gray-900">{{ \Illuminate\Support\Str::ucfirst($day['start']->translatedFormat('l, j F')) }}</h4>
            <div class="overflow-x-auto">
                <div class="grid min-w-[36rem] gap-2" style="grid-template-columns: 3rem repeat({{ $day['sectors']->count() }}, minmax(10rem, 1fr))">
                    <div></div>
                    @foreach($day['sectors'] as $name => $blocks)
                        <p class="flex items-center justify-center gap-1.5 text-xs font-semibold tracking-wide text-gray-500 uppercase">
                            @if($venue = \App\Models\TrainingSession::venueForCourt($name))<x-venue-icon :venue="$venue" small />@endif{{ $name }}
                        </p>
                    @endforeach

                    {{-- Oś godzin --}}
                    <div class="relative" style="height: {{ $height }}px">
                        @for($hour = $day['start']->copy(); $hour->lte($day['end']); $hour->addHour())
                            <span class="absolute right-1 -translate-y-1/2 text-[11px] text-gray-400 tabular-nums" style="top: {{ (int) round($day['start']->diffInMinutes($hour) * $scale) }}px">{{ $hour->format('H:i') }}</span>
                        @endfor
                    </div>
                    @foreach($day['sectors'] as $name => $blocks)
                        <div class="relative rounded-md bg-gray-50 ring-1 ring-gray-200" style="height: {{ $height }}px; background-image: repeating-linear-gradient(to bottom, transparent 0, transparent {{ 60 * $scale - 1 }}px, rgb(229 231 235) {{ 60 * $scale - 1 }}px, rgb(229 231 235) {{ 60 * $scale }}px)">
                            @foreach($blocks as $block)
                                @php $match = $block['match']; @endphp
                                @php $color = $colors[$match->tournament_category_id] ?? '#45a0e0'; @endphp
                                <div @class(['absolute inset-x-1 overflow-hidden rounded border-l-4 px-1.5 py-1 text-[11px] leading-tight text-gray-900', 'ring-1' => ! $block['conflict'], 'ring-2 ring-red-500' => $block['conflict']])
                                    style="top: {{ (int) round($day['start']->diffInMinutes($block['starts']) * $scale) }}px; height: {{ max(18, (int) round($block['starts']->diffInMinutes($block['ends']) * $scale) - 2) }}px; background: color-mix(in srgb, {{ $color }} 18%, white); border-left-color: {{ $color }}; @if(! $block['conflict']) --tw-ring-color: color-mix(in srgb, {{ $color }} 45%, white); @endif"
                                    title="#{{ $match->number }} {{ $match->category->name }} · {{ $match->title() }} · {{ $match->homeName() }} – {{ $match->awayName() }} · {{ $block['starts']->format('H:i') }}–{{ $block['ends']->format('H:i') }}">
                                    <p class="font-semibold"><span class="tabular-nums">{{ $block['starts']->format('H:i') }}</span> #{{ $match->number }} {{ $match->category->name }}</p>
                                    <p class="truncate">{{ $match->group?->name ?? $match->title() }}</p>
                                    <p class="truncate opacity-80">{{ $match->homeName() }} – {{ $match->awayName() }}</p>
                                    @if($delay = $match->delayMinutes())<p class="font-semibold text-amber-700">{{ $delay > 0 ? '+' : '−' }}{{ abs($delay) }} min</p>@endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach
</div>
