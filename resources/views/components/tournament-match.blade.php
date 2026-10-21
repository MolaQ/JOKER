@props(['match', 'showTitle' => true, 'showDate' => true])
{{-- Mecz turnieju: drużyny z logo, wynik w setach i małe punkty; zwycięzca pogrubiony --}}
@php
    $winnerId = $match->winner()?->id;
    $sides = [
        [$match->home, $match->homeName(), $match->home_sets, $match->home_participant_id],
        [$match->away, $match->awayName(), $match->away_sets, $match->away_participant_id],
    ];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-2xl bg-white p-3 ring-1 ring-zinc-200']) }}>
    @if($showTitle || $match->starts_at || $match->court)
        <p class="mb-2 flex flex-wrap items-center gap-x-2 text-[11px] font-semibold tracking-wide text-zinc-500 uppercase">
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
    @php
        // Transmisje i zdjęcia z meczu (liczba zdjęć pobierana raz dla całej kategorii).
        $streams = $match->normalizedStreams();
        $photoCount = (int) \App\Models\Gallery::photoCountsForTournamentCategory($match->tournament_category_id)->get($match->id, 0);
        $matchTournament = $photoCount > 0 ? $match->tournament() : null;
    @endphp
    @if(count($streams) || $matchTournament)
        <p class="mt-1.5 flex flex-wrap items-center justify-end gap-1.5 text-[11px] font-semibold">
            @foreach($streams as $stream)
                <a href="{{ $stream['url'] }}" target="_blank" rel="noopener" @class(['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-white', 'bg-red-600 hover:bg-red-700' => $stream['platform'] === 'youtube', 'bg-[#1877f2] hover:bg-[#0f63d1]' => $stream['platform'] === 'facebook', 'bg-ink hover:bg-graphite' => $stream['platform'] === 'other']) title="{{ __('Transmisja') }}: {{ $stream['label'] }}">▶ {{ $stream['platform'] === 'other' ? __('Wideo') : ($stream['platform'] === 'youtube' ? 'YT' : 'FB') }}</a>
            @endforeach
            @if($matchTournament)
                <a href="{{ route('tournaments.matches.show', [$matchTournament, $match]) }}#galeria" class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-700 hover:bg-sky-club/15" title="{{ trans_choice(':count zdjęcie|:count zdjęcia|:count zdjęć', $photoCount) }}">📷 {{ $photoCount }}</a>
            @endif
        </p>
    @endif
</div>
