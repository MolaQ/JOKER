@props(['match', 'streams' => true])
{{-- Transmisje i zdjęcia z meczu turnieju (liczba zdjęć pobierana raz dla całej kategorii). --}}
@php
    $streams = $streams ? $match->normalizedStreams() : [];
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
