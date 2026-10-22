@props(['match', 'showTitle' => true, 'showDate' => true, 'bracket' => null])
{{-- Mecz turnieju: drużyny z logo, wynik w setach i małe punkty; zwycięzca pogrubiony --}}
@php
    $winnerId = $match->winner()?->id;
    $sides = [
        // Z drabinką nazwy uwzględniają reguły („1. miejsce – Grupa A”, „Zwycięzca meczu #7”).
        [$match->home, $bracket ? $bracket->side($match, 'home')['text'] : $match->homeName(), $match->home_sets, $match->home_participant_id],
        [$match->away, $bracket ? $bracket->side($match, 'away')['text'] : $match->awayName(), $match->away_sets, $match->away_participant_id],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white p-3 ring-1 ring-zinc-200']) }}>
    @if($showTitle || $match->starts_at || $match->court)
        <p class="mb-2 flex flex-wrap items-center gap-x-2 text-[11px] font-semibold tracking-wide text-zinc-500 uppercase">
            @if($match->number)<span class="rounded bg-zinc-100 px-1 text-zinc-600">#{{ $match->number }}</span>@endif
            @if($showTitle)<span class="text-sky-club-dark">{{ $match->title() }}</span>@endif
            @if($match->starts_at)<span class="tabular-nums">@if($showDate){{ $match->starts_at->translatedFormat('D d.m') }}, @endif{{ $match->starts_at->format('H:i') }}</span>@endif
            @if($match->court)<span>· {{ $match->court }}</span>@endif
        </p>
    @endif
    @foreach($sides as [$participant, $name, $sets, $participantId])
        <div @class(['flex items-center gap-2 py-0.5', 'font-bold text-zinc-900' => $winnerId && $winnerId === $participantId, 'text-zinc-500' => $winnerId && $winnerId !== $participantId, 'text-zinc-800' => ! $winnerId])>
            @if($participant)
                <x-avatar :photo="$participant->logoPath()" :name="$name" class="h-6 w-6 shrink-0 rounded-full text-[9px]" />
            @else
                <span class="h-6 w-6 shrink-0 rounded-full bg-zinc-100"></span>
            @endif
            <span @class(['min-w-0 flex-1 truncate', 'italic text-zinc-400' => ! $participant])>{{ $name }}</span>
            @if($participant?->isOwnTeam())<span class="rounded bg-sky-club px-1 text-[9px] font-bold text-white">JOKER</span>@endif
            <span class="w-5 text-right font-display text-lg tabular-nums">{{ $sets ?? '' }}</span>
        </div>
    @endforeach
    @if($match->setsLabel())
        <p class="mt-1 text-right text-[11px] text-zinc-400 tabular-nums">({{ $match->setsLabel() }})</p>
    @endif
    <x-tournament-match-media :match="$match" />
</div>
