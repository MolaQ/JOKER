@props(['trip', 'showParentInfo' => false, 'showGroup' => true])
{{-- Wyjazd na turniej: data jak kartka z kalendarza, miejsce, drużyny ze składami, wynik i (dla rodziców) szczegóły --}}
<article id="wyjazd-{{ $trip->id }}" {{ $attributes->merge(['class' => 'flex scroll-mt-40 gap-4 rounded-2xl bg-white p-4 ring-1 ring-zinc-200 target:ring-2 target:ring-sky-club']) }} @if($tint = $trip->tintStyle()) style="{{ $tint }}" @endif>
    <div class="flex w-14 shrink-0 flex-col self-start overflow-hidden rounded-xl text-center shadow-sm ring-1 ring-zinc-200">
        <span class="bg-club-navy py-0.5 text-[10px] font-bold tracking-widest text-white uppercase">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($trip->starts_on->translatedFormat('M'), 0, 3)) }}</span>
        <span class="bg-white py-1 font-display text-2xl leading-none font-bold text-zinc-900 tabular-nums">{{ $trip->starts_on->format('j') }}</span>
    </div>
    <div class="min-w-0 flex-1">
        <h3 class="font-semibold text-zinc-900"><a href="{{ $trip->url() }}" class="hover:underline">{{ $trip->title }}</a></h3>
        <p class="text-sm text-zinc-500">{{ collect([$trip->dateLabel(), $trip->city, $trip->venue, $trip->organizer])->filter()->implode(' · ') }}</p>
        @if($trip->description)<p class="mt-1 text-sm text-zinc-600">{{ $trip->description }}</p>@endif
        @foreach($trip->teams as $team)
            <div class="mt-3">
                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-zinc-800">
                    {{ $team->name }}@if($showGroup && $team->group)<a href="{{ $trip->url($team->group) }}" class="inline-flex items-center gap-1 font-normal text-zinc-500 hover:text-zinc-900 hover:underline">· <x-mini-players :group="$team->group" size="xs" aria-hidden="true" />{{ $team->group->name }}</a>@endif
                    @if($team->place)
                        <span @class(['rounded-full px-2 py-0.5 text-xs font-bold', 'bg-amber-100 text-amber-800' => $team->place === 1, 'bg-zinc-200 text-zinc-700' => $team->place === 2, 'bg-orange-100 text-orange-800' => $team->place === 3, 'bg-sky-50 text-sky-800' => $team->place > 3])>{{ $team->place <= 3 ? ['🥇', '🥈', '🥉'][$team->place - 1].' ' : '' }}{{ $team->resultLabel() }}</span>
                    @endif
                </p>
                @if($site['mini_show_lineups'] && $team->players->isNotEmpty())<p class="text-sm text-zinc-600">{{ $team->players->map->displayName()->implode(', ') }}</p>@endif
                @if($team->note)<p class="text-sm text-zinc-500 italic">{{ $team->note }}</p>@endif
                @if($team->photoUrl())<img src="{{ $team->photoUrl() }}" alt="{{ $team->name }}, {{ $trip->title }}" loading="lazy" class="mt-2 max-h-64 rounded-xl object-cover">@endif
            </div>
        @endforeach
        @foreach($trip->articles as $article)
            <a href="{{ route('articles.show', $article) }}" class="mt-2 mr-4 inline-block text-sm font-semibold text-sky-club-dark hover:underline">📰 {{ __('Relacja') }}: {{ $article->title }} →</a>
        @endforeach
        @foreach($trip->publishedGalleries->where('photos_count', '>', 0) as $gallery)
            <a href="{{ route('galleries.show', $gallery) }}" class="mt-2 mr-4 inline-block text-sm font-semibold text-sky-club-dark hover:underline">📷 {{ __('Zdjęcia') }} ({{ $gallery->photos_count }}) →</a>
        @endforeach
        @foreach($trip->normalizedStreams() as $stream)
            <span class="mt-2 mr-4 inline-flex items-center gap-1 text-sm">
                <a href="{{ $stream['url'] }}" target="_blank" rel="noopener" class="font-semibold text-sky-club-dark hover:underline">▶ {{ __('Film') }} · {{ $stream['platform_name'] }} ↗</a>
                <x-link-report :target="\App\Support\LinkReports::keyFor($trip)" :url="$stream['url']" />
            </span>
        @endforeach
        @if($share = \App\Support\ShareCards::miniTrip($trip))
            <x-share-card :card="$share" class="mt-3 mr-2" />
        @endif
        @if($trip->tournament)
            <a href="{{ route('tournaments.show', $trip->tournament) }}" class="mt-2 inline-block text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Pełne wyniki turnieju') }} →</a>
        @endif
        @if($showParentInfo && $trip->parent_info && ! $trip->isPast())
            <p class="mt-3 rounded-xl border border-dashed border-amber-400 bg-amber-50 px-3 py-2 text-sm whitespace-pre-line text-amber-900">🔒 {{ __('Dla rodziców') }}: {{ $trip->parent_info }}</p>
        @endif
    </div>
</article>
