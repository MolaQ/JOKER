@props(['streams' => []])
{{-- Transmisje meczu: YouTube osadzony po kliknięciu (lekka zaślepka), Facebook i inne jako przyciski --}}
@if(count($streams))
    <div {{ $attributes->merge(['class' => 'space-y-4']) }}>
        @foreach($streams as $stream)
            @if($stream['embed_url'])
                <figure>
                    <div x-data="{ playing: false }" class="relative aspect-video overflow-hidden rounded-3xl bg-ink ring-1 ring-zinc-200/70">
                        <template x-if="playing">
                            <iframe src="{{ $stream['embed_url'] }}?autoplay=1&rel=0" title="{{ $stream['label'] }}" class="absolute inset-0 h-full w-full" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                        </template>
                        <button type="button" x-show="! playing" x-on:click="playing = true" class="group absolute inset-0 flex h-full w-full items-center justify-center" aria-label="{{ __('Odtwórz') }}: {{ $stream['label'] }}">
                            <img src="{{ $stream['thumbnail_url'] }}" alt="" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover opacity-80 transition group-hover:opacity-100" onerror="this.remove()">
                            <span class="absolute inset-0 bg-gradient-to-t from-ink/80 via-ink/10 to-transparent"></span>
                            <span class="relative flex h-16 w-24 items-center justify-center rounded-2xl bg-red-600 text-white shadow-xl transition group-hover:scale-110" aria-hidden="true">
                                <svg class="ml-1 h-8 w-8" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                            </span>
                            <span class="absolute bottom-4 left-5 right-5 text-left font-semibold text-white">{{ $stream['label'] }}</span>
                        </button>
                    </div>
                    <figcaption class="mt-2 text-sm text-zinc-500">
                        <a href="{{ $stream['url'] }}" target="_blank" rel="noopener" class="font-semibold text-sky-club-dark hover:underline">{{ __('Otwórz w serwisie :platform', ['platform' => $stream['platform_name']]) }} ↗</a>
                    </figcaption>
                </figure>
            @endif
        @endforeach

        @php $links = collect($streams)->whereNull('embed_url'); @endphp
        @if($links->isNotEmpty())
            <div class="flex flex-wrap gap-3">
                @foreach($links as $stream)
                    <a href="{{ $stream['url'] }}" target="_blank" rel="noopener"
                       @class([
                           'inline-flex min-h-11 items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:-translate-y-0.5',
                           'bg-[#1877f2] hover:bg-[#0f63d1]' => $stream['platform'] === 'facebook',
                           'bg-red-600 hover:bg-red-700' => $stream['platform'] === 'youtube',
                           'bg-ink hover:bg-graphite' => $stream['platform'] === 'other',
                       ])>
                        <span aria-hidden="true">▶</span>
                        {{ $stream['label'] }}
                        @if($stream['label'] !== $stream['platform_name'])<span class="font-normal opacity-80">· {{ $stream['platform_name'] }}</span>@endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>
@endif
