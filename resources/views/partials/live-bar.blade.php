{{--
    Pasek „Na żywo”: trwający turniej albo mecz (App\Support\LiveNow), nad dolnym menu; nie pokazuje się na stronie, do której prowadzi.
    W turnieju pokazuje wynik trwającego meczu (i stan seta, który trwa) i prowadzi do zakładki „Mecze” jego kategorii;
    kilka meczów zmienia się co 4 sekundy, a co 20 sekund pasek pobiera się na nowo (resources/js/live-bar.js).
--}}
@php
    $liveNow = \App\Support\LiveNow::current();
    $animate ??= true;
@endphp
@if($liveNow && ! str_starts_with($currentUrl, $liveNow['url']))
    @php
        $liveSlides = collect($liveNow['matches'] ?? [])->map(fn (array $match) => [
            'url' => $match['url'],
            'detail' => collect([$match['category'], $match['court']])->filter()->implode(' · '),
            'set' => $match['set'] ?? null,
            'score' => $match['score'],
            'home' => $match['home'],
            'away' => $match['away'],
            'stream' => $match['stream'] ?? null,
        ]);
        if ($liveSlides->isEmpty()) {
            $liveSlides = collect([['url' => $liveNow['url'], 'title' => $liveNow['title'], 'detail' => $liveNow['detail'].' · '.__('wyniki na żywo'), 'stream' => $liveNow['stream'] ?? null]]);
        }
    @endphp
    <div data-live-bar data-count="{{ $liveSlides->count() }}" data-src="{{ route('live-bar', ['from' => '/'.ltrim(parse_url($currentUrl, PHP_URL_PATH) ?? '', '/')]) }}" @class(['fixed inset-x-3 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-40 overflow-hidden rounded-2xl bg-ink/95 text-white shadow-2xl ring-1 ring-white/10 backdrop-blur-xl lg:inset-x-auto lg:right-5 lg:bottom-5 lg:w-96', 'live-bar' => $animate])>
        @foreach($liveSlides as $slide)
            <div @if(! $loop->first) hidden @endif data-live-slide class="flex items-stretch">
                <a href="{{ $slide['url'] }}" class="flex min-w-0 flex-1 items-center gap-3 py-2.5 pl-3 @if(empty($slide['stream'])) pr-4 @else pr-2 @endif">
                    <span class="relative flex size-9 shrink-0 items-center justify-center rounded-xl bg-red-600 text-[10px] font-bold tracking-wider uppercase">
                        <span class="absolute inset-0 animate-ping rounded-xl bg-red-600/60" aria-hidden="true"></span>
                        <span class="relative">{{ __('Live') }}</span>
                    </span>
                    <span class="min-w-0 flex-1 leading-tight">
                        @isset($slide['score'])
                            <span class="flex min-w-0 items-baseline gap-1.5 text-sm font-semibold">
                                <span class="min-w-0 flex-1 truncate text-right">{{ $slide['home'] }}</span>
                                <span data-live-score="{{ $loop->index }}" class="shrink-0 rounded font-display text-base font-bold tabular-nums">{{ $slide['score'] }}</span>
                                <span class="min-w-0 flex-1 truncate">{{ $slide['away'] }}</span>
                            </span>
                        @else
                            <span class="block truncate text-sm font-semibold">{{ $slide['title'] }}</span>
                        @endisset
                        <span @class(['block truncate text-xs text-zinc-400', 'text-center' => isset($slide['score'])])>@if(! empty($slide['set']))<span data-live-score="set-{{ $loop->index }}" class="rounded font-semibold text-red-300 tabular-nums">{{ $slide['set'] }}</span> · @endif{{ $slide['detail'] }}@if($liveSlides->count() > 1) · {{ $loop->iteration }}/{{ $liveSlides->count() }}@endif</span>
                    </span>
                    @if(empty($slide['stream']))<span class="shrink-0 text-sky-club-light" aria-hidden="true">→</span>@endif
                </a>
                @if(! empty($slide['stream']))
                    {{-- Mecz z transmisją: wyraźny przycisk prosto do wideo --}}
                    <a href="{{ $slide['stream'] }}" target="_blank" rel="noopener" data-live-stream class="flex shrink-0 flex-col items-center justify-center gap-0.5 border-l border-white/10 bg-red-600 px-3 text-[10px] font-bold tracking-wide uppercase transition hover:bg-red-700" title="{{ __('Oglądaj transmisję') }}">
                        <svg class="size-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm6 4v6l5-3-5-3Z"/></svg>
                        {{ __('Oglądaj') }}
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
