@extends('layouts.public')

@section('title', $team->name)
@section('description', 'Skład, sztab szkoleniowy, terminarz i tabela drużyny '.$team->name.' - '.$site['club_name'].'.')

@php
    use App\PlayerPosition;
    use App\Support\Media;
@endphp

@section('content')
{{-- NAGŁÓWEK DRUŻYNY --}}
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <x-photo :src="Media::teamPhoto($team)" :alt="'Drużyna '.$team->name" eager width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-60" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/70 to-ink/30"></div>

    <div class="container-site pt-24 pb-14 lg:pt-36 lg:pb-20">
        <a href="{{ route('teams.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← Wszystkie drużyny</a>
        <p class="eyebrow mt-6">{{ $team->birth_year ? 'Rocznik '.$team->birth_year : 'Pierwsza drużyna' }}@if($currentSeason) · Sezon {{ $currentSeason->name }}@endif</p>
        <h1 class="heading-xl mt-3">{{ $team->name }}</h1>
        @if($team->description)
            <div class="mt-5 max-w-2xl text-lg text-zinc-300">{!! strip_tags($team->description, '<p><br><strong><em><b><i>') !!}</div>
        @endif

        <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
            <div><dt class="text-xs text-zinc-400 uppercase">Zawodnicy</dt><dd class="font-display text-4xl font-bold">{{ $roster->count() }}</dd></div>
            <div><dt class="text-xs text-zinc-400 uppercase">Sztab</dt><dd class="font-display text-4xl font-bold">{{ $staff->count() }}</dd></div>
            @if($upcoming_games->first())
                <div><dt class="text-xs text-zinc-400 uppercase">Najbliższy mecz</dt><dd class="font-display text-2xl font-semibold uppercase">{{ $upcoming_games->first()->game_date?->translatedFormat('d M') }} · {{ $upcoming_games->first()->opponentShortName() }}</dd></div>
            @endif
        </dl>
    </div>
</section>

{{-- ZAKŁADKI --}}
<nav class="sticky top-16 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur lg:top-20" aria-label="Sekcje drużyny">
    <div class="container-site scrollbar-none flex gap-6 overflow-x-auto text-sm font-semibold">
        <a href="#kadra" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">Kadra</a>
        <a href="#sztab" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">Sztab</a>
        <a href="#mecze" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">Mecze</a>
        @if($standingsByCompetition->isNotEmpty())
            <a href="#tabela" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">Tabela</a>
        @endif
    </div>
</nav>

{{-- KADRA --}}
<section id="kadra" class="container-site scroll-mt-32 py-16 lg:py-20">
    <p class="eyebrow">Kadra</p>
    <h2 class="heading-lg mt-3">Zawodnicy</h2>

    @forelse($rosterByPosition as $position => $players)
        <div class="mt-10" data-reveal>
            <h3 class="flex items-center gap-3 text-sm font-semibold tracking-widest text-zinc-500 uppercase">
                {{ PlayerPosition::tryFrom((string) $position)?->label() ?? 'Pozostali' }}
                <span class="h-px flex-1 bg-zinc-200"></span>
            </h3>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($players as $player)
                    @php $number = $player->pivot?->jersey_number ?? $player->jersey_number; @endphp
                    <a href="{{ route('players.show', $player) }}" class="card card-hover group overflow-hidden">
                        <x-avatar :photo="$player->photo_path" :name="$player->full_name" :label="$number ?: null" class="aspect-[4/5] w-full text-6xl" />
                        <div class="p-4">
                            <p class="font-semibold leading-tight group-hover:text-sky-club-dark">{{ $player->full_name }}</p>
                            <p class="mt-1 flex items-center gap-2 text-xs text-zinc-500">
                                @if($number)<span class="font-bold text-zinc-900">#{{ $number }}</span>@endif
                                @if($player->pivot?->is_captain)<span class="rounded bg-sky-club px-1.5 py-0.5 font-bold text-white" title="Kapitan">C</span>@endif
                                @if($player->team_id !== $team->id)<span title="Gra też w swojej drużynie macierzystej">↑ z młodszego rocznika</span>@endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">Skład drużyny zostanie wkrótce uzupełniony.</p>
    @endforelse
</section>

{{-- SZTAB --}}
<section id="sztab" class="scroll-mt-32 bg-white py-16 lg:py-20">
    <div class="container-site" data-reveal>
        <p class="eyebrow">Sztab</p>
        <h2 class="heading-lg mt-3">Trenerzy i sztab</h2>

        @if($staff->isNotEmpty())
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($staff as $member)
                    <a href="{{ route('trainers.show', $member['user']) }}" class="card card-hover group flex items-center gap-5 p-4">
                        <x-avatar :photo="$member['user']->avatar_path" :name="$member['user']->name" class="h-20 w-20 shrink-0 rounded-2xl text-2xl" />
                        <div>
                            <p class="text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ $member['role'] }}</p>
                            <p class="mt-1 text-lg font-semibold group-hover:text-sky-club-dark">{{ $member['user']->name }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">Sztab drużyny zostanie wkrótce uzupełniony.</p>
        @endif
    </div>
</section>

{{-- MECZE --}}
<section id="mecze" class="container-site scroll-mt-32 py-16 lg:py-20" data-reveal>
    <p class="eyebrow">Mecze</p>
    <h2 class="heading-lg mt-3">Terminarz</h2>

    @if($upcoming_games->isNotEmpty())
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($upcoming_games as $game)
                <x-match-card :game="$game" />
            @endforeach
        </div>
    @else
        <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">Brak zaplanowanych meczów.</p>
    @endif

    @if($recent_games->isNotEmpty())
        <h3 class="heading-md mt-14">Ostatnie wyniki</h3>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($recent_games as $game)
                <x-match-card :game="$game" />
            @endforeach
        </div>
    @endif
</section>

{{-- TABELA --}}
@if($standingsByCompetition->isNotEmpty())
<section id="tabela" class="container-site scroll-mt-32 pb-8" data-reveal>
    <p class="eyebrow">Rozgrywki</p>
    <h2 class="heading-lg mt-3">Tabela</h2>

    @foreach($standingsByCompetition as $competitionName => $competitionStandings)
        <div class="card mt-8 overflow-hidden">
            <p class="border-b border-zinc-100 px-6 py-4 font-semibold">{{ $competitionName }}</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-xs text-zinc-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-left">Drużyna</th>
                            <th class="px-3 py-3" title="Mecze">M</th>
                            <th class="px-3 py-3" title="Wygrane">W</th>
                            <th class="px-3 py-3" title="Porażki">P</th>
                            <th class="hidden px-3 py-3 sm:table-cell">Sety</th>
                            <th class="px-6 py-3 text-right">Pkt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach($competitionStandings as $standing)
                            @php $ours = $standing->isOwnTeam() && $standing->competitor?->is($team); @endphp
                            <tr @class(['bg-sky-club/10 font-semibold' => $ours])>
                                <td class="px-6 py-3">
                                    <span class="mr-3 inline-flex h-7 w-7 items-center justify-center rounded-full {{ $ours ? 'bg-sky-club text-white' : 'bg-zinc-100' }} text-xs font-bold">{{ $standing->position }}</span>
                                    @if($standing->isOwnTeam() && $standing->competitor)
                                        <a href="{{ route('teams.show', $standing->competitor) }}" class="hover:text-sky-club-dark">{{ $standing->competitorName() }}</a>
                                    @else
                                        {{ $standing->competitorName() }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-center">{{ $standing->played }}</td>
                                <td class="px-3 py-3 text-center">{{ $standing->won }}</td>
                                <td class="px-3 py-3 text-center">{{ $standing->lost }}</td>
                                <td class="hidden px-3 py-3 text-center sm:table-cell">{{ $standing->setsRatio() }}</td>
                                <td class="px-6 py-3 text-right font-display text-lg font-bold">{{ $standing->points }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</section>
@endif
@endsection
