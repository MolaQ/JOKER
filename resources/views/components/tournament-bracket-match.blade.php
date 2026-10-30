@props(['match', 'bracket'])
{{-- Mecz drabinki: belka z numerem, drużyny z plakietką „skąd” (prognoza na szaro) i dyskretne „dokąd” w rogu. --}}
@php
    use App\Support\TournamentBracket;

    $winnerId = $match->winner()?->id;
    $destinations = $bracket->destinations($match);
@endphp
<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-zinc-200']) }} id="mecz-{{ $match->number }}">
    {{-- Nagłówek: numer i pełna nazwa meczu, pod spodem termin z lewej i sektor z prawej --}}
    <header class="bg-(--tm-bar-bg) px-3 py-2 text-(--tm-bar-text)">
        <p class="flex items-center gap-2">
            <span class="rounded-md bg-(--tm-number-bg) px-1.5 text-sm font-bold text-(--tm-number-text) tabular-nums">#{{ $match->number }}</span>
            <span class="min-w-0 font-semibold leading-tight">{{ $match->title() }}</span>
            @if($match->is_live)
                <span class="ml-auto inline-flex shrink-0 items-center gap-1 rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white uppercase"><span class="size-1.5 animate-pulse rounded-full bg-white"></span>{{ __('Trwa') }}</span>
            @endif
        </p>
        {{-- Druga linia zawsze, żeby kafle w drabince miały równą wysokość --}}
        <p class="mt-1.5 flex min-h-5 items-center justify-between gap-3">
                @if($match->starts_at)
                    <span class="inline-flex items-baseline gap-1.5 text-xs opacity-80"><span>{{ $match->starts_at->translatedFormat('D d.m') }}</span><span class="font-display text-lg leading-none font-semibold tabular-nums opacity-100">{{ $match->starts_at->format('H:i') }}</span><x-match-delay :match="$match" class="self-center" /></span>
                @else
                    <span class="text-xs opacity-60">{{ __('Godzina do ustalenia') }}</span>
                @endif
                @if($match->court)
                    @php $venue = \App\Models\TrainingSession::venueForCourt($match->court); @endphp
                    <span class="inline-flex min-w-0 items-center gap-1.5 text-xs opacity-90">@if($venue)<x-venue-icon :venue="$venue" small class="rounded-[3px] bg-white" />@endif<span class="truncate">{{ \App\Models\TrainingSession::courtLabel($match->court) }}</span></span>
                @endif
            </p>
    </header>
    @foreach(['home', 'away'] as $side)
        @php
            $info = $bracket->side($match, $side);
            $participant = $info['participant'];
            $sets = $match->{$side.'_sets'};
            $isWinner = $winnerId && $participant && $winnerId === $participant->id;
        @endphp
        <div @class(['flex items-center gap-2 border-t border-zinc-100 px-3 py-2'])>
            @if($info['chip'])
                <span @class(['min-w-8 rounded-md px-1 text-center text-[11px] font-bold', 'bg-sky-club/15 text-sky-club-dark' => $info['state'] !== TournamentBracket::PENDING, 'bg-zinc-100 text-zinc-500' => $info['state'] === TournamentBracket::PENDING])>{{ $info['chip'] }}</span>
            @endif
            @if($info['state'] === TournamentBracket::CONFIRMED && $participant)
                <x-avatar logo :photo="$participant->logoPath()" :name="$participant->name()" class="h-6 w-6 shrink-0 rounded-full text-[9px]" />
            @endif
            <span @class([
                'min-w-0 flex-1 truncate',
                'font-bold text-zinc-900' => $info['state'] === TournamentBracket::CONFIRMED && ($isWinner || ! $winnerId),
                'text-zinc-500' => $info['state'] === TournamentBracket::CONFIRMED && $winnerId && ! $isWinner,
                'italic text-zinc-400' => $info['state'] !== TournamentBracket::CONFIRMED,
            ])>{{ $info['text'] }}</span>
            @if($info['state'] === TournamentBracket::PROJECTED)
                <span class="shrink-0 rounded-full border border-dashed border-zinc-300 px-1.5 text-[10px] text-zinc-400" title="{{ __('Grupa jeszcze trwa, kolejność może się zmienić') }}">{{ __('prognoza') }}</span>
            @endif
            @if($sets !== null)<span class="w-5 text-right font-display text-lg tabular-nums">{{ $sets }}</span>@endif
        </div>
    @endforeach
    @if($match->setsLabel() || $destinations['winner'] || $destinations['loser'])
        <footer class="flex flex-wrap items-center justify-end gap-x-3 gap-y-0.5 px-3 pb-2 text-[11px] text-zinc-400">
            @if($match->setsLabel())<span class="mr-auto tabular-nums">({{ $match->setsLabel() }})</span>@endif
            @if($destinations['winner'])<span>{{ __('zwycięzca') }} → {{ $destinations['winner'] }}</span>@endif
            @if($destinations['loser'])<span>{{ __('przegrany') }} → {{ $destinations['loser'] }}</span>@endif
        </footer>
    @endif
    <div class="px-3 empty:hidden"><x-tournament-match-media :match="$match" /></div>
</article>
