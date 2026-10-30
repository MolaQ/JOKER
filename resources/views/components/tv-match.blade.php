@props(['match', 'bracket' => null, 'compact' => false])
{{-- Mecz na ekranie TV: godzina i sektor z lewej, drużyny z herbami i wynik; ten sam wygląd na każdej planszy --}}
@php
    $winnerId = $match->winner()?->id;
    $title = $match->label ?: ($match->stage === \App\Models\TournamentMatch::STAGE_GROUP ? $match->group?->name : ($match->placement ? __('o :place. miejsce', ['place' => $match->placement]) : null));
@endphp
<div @class(['flex min-h-0 items-center rounded-3xl ring-1', 'gap-6 px-8 py-4' => ! $compact, 'gap-4 px-5 py-2' => $compact, 'bg-red-500/15 ring-red-400/60' => $match->is_live, 'bg-white/[0.06] ring-white/10' => ! $match->is_live])>
    <div @class(['shrink-0 text-center', 'w-36' => ! $compact, 'w-24' => $compact])>
        @if($match->is_live)
            <p @class(['inline-flex items-center gap-2 rounded-full bg-red-500 px-4 py-1 font-display font-bold uppercase', 'text-2xl' => ! $compact, 'text-lg' => $compact])><span class="h-3 w-3 animate-pulse rounded-full bg-white"></span>{{ __('Trwa') }}</p>
        @else
            <p @class(['font-display font-bold tabular-nums', 'text-5xl' => ! $compact, 'text-3xl' => $compact])>{{ $match->starts_at?->format('H:i') ?? '—' }}</p>
            @unless($compact)<p class="text-xl text-white/60">{{ $match->starts_at?->translatedFormat('D d.m') }}</p>@endunless
        @endif
        @if($match->court)<p @class(['mt-1 font-semibold text-sky-300', 'text-xl' => ! $compact, 'text-base' => $compact])>{{ \App\Models\TrainingSession::courtLabel($match->court) }}</p>@endif
    </div>
    <div @class(['min-w-0 flex-1', 'space-y-2' => ! $compact, 'space-y-1' => $compact])>
        @if($title || $match->number)
            <p @class(['flex gap-3 font-semibold tracking-wider text-sky-300 uppercase', 'text-lg' => ! $compact, 'text-sm' => $compact])>
                <span class="truncate">{{ $title }}</span>
                @if($match->number)<span class="ml-auto shrink-0 text-white/40">#{{ $match->number }}</span>@endif
            </p>
        @endif
        @foreach(['home', 'away'] as $key)
            @php($side = $bracket ? $bracket->side($match, $key) : ['participant' => $match->{$key}, 'text' => $match->{$key}?->name() ?? '—'])
            <div @class(['flex items-center', 'gap-4' => ! $compact, 'gap-3' => $compact, 'text-white/50' => $winnerId && $side['participant']?->id !== $winnerId])>
                @if($side['participant'])
                    <x-avatar logo :photo="$side['participant']->logoPath()" :name="$side['text']" @class(['shrink-0 rounded-full', 'h-11 w-11 text-sm' => ! $compact, 'h-8 w-8 text-xs' => $compact]) />
                @else
                    <span @class(['shrink-0 rounded-full bg-white/10', 'h-11 w-11' => ! $compact, 'h-8 w-8' => $compact])></span>
                @endif
                <span @class(['min-w-0 flex-1 truncate', 'text-3xl' => ! $compact, 'text-2xl' => $compact, 'font-bold' => $winnerId && $side['participant']?->id === $winnerId, 'italic text-white/50' => ! $side['participant']])>{{ $side['text'] }}</span>
                <span @class(['text-right font-display font-bold tabular-nums', 'w-10 text-5xl' => ! $compact, 'w-8 text-3xl' => $compact])>{{ $match->{$key.'_sets'} ?? '' }}</span>
            </div>
        @endforeach
        @if($match->setsLabel() && ! $compact)<p class="text-right text-xl text-white/50 tabular-nums">({{ $match->setsLabel() }})</p>@endif
    </div>
</div>
