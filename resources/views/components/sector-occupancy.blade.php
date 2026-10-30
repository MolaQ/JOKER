@props(['tournament'])
{{-- Obłożenie sektorów: oś czasu dnia, kolumny sektorów jak w hali, mecze wszystkich kategorii w kolorach kategorii --}}
@php
    $days = \App\Support\TournamentSchedule::occupancy($tournament);
    $palette = ['bg-sky-100 ring-sky-300 text-sky-900', 'bg-amber-100 ring-amber-300 text-amber-900', 'bg-emerald-100 ring-emerald-300 text-emerald-900', 'bg-violet-100 ring-violet-300 text-violet-900', 'bg-rose-100 ring-rose-300 text-rose-900', 'bg-lime-100 ring-lime-300 text-lime-900'];
    $colors = $tournament->categories()->pluck('id')->values()->flip()->map(fn ($index) => $palette[$index % count($palette)]);
    $scale = 1.1; // piksele na minutę
@endphp
<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @if($days->isEmpty())
        <p class="text-sm text-gray-500">Żaden mecz nie ma jeszcze godziny i sektora.</p>
    @else
        <div class="flex flex-wrap gap-2 text-xs">
            @foreach($tournament->categories as $category)
                <span class="rounded-full px-2 py-0.5 font-semibold ring-1 {{ $colors[$category->id] ?? $palette[0] }}">{{ $category->name }}</span>
            @endforeach
            <span class="rounded-full bg-white px-2 py-0.5 font-semibold text-red-700 ring-2 ring-red-500">nakłada się</span>
        </div>
    @endif
    @foreach($days as $date => $day)
        @php
            $minutes = (int) $day['start']->diffInMinutes($day['end']) + 15;
            $height = (int) round($minutes * $scale);
        @endphp
        <section>
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
                                <div @class(['absolute inset-x-1 overflow-hidden rounded px-1.5 py-1 text-[11px] leading-tight ring-1', $colors[$match->tournament_category_id] ?? $palette[0], '!ring-2 !ring-red-500' => $block['conflict']])
                                    style="top: {{ (int) round($day['start']->diffInMinutes($block['starts']) * $scale) }}px; height: {{ max(18, (int) round($block['starts']->diffInMinutes($block['ends']) * $scale) - 2) }}px"
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
