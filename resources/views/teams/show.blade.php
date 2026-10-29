@extends('layouts.public')

@section('title', $team->name)
@section('description', __('Skład, sztab szkoleniowy, terminarz i tabela drużyny :team - :club.', ['team' => $team->name, 'club' => $site['club_name']]))

@php
    use App\PlayerPosition;
    use App\Support\Media;
@endphp

@section('og_image', \App\Support\Media::teamPhoto($team))
@push('structured-data')
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::team($team)) !!}
@endpush

@section('content')
{{-- NAGŁÓWEK DRUŻYNY --}}
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <x-photo :src="Media::teamPhoto($team)" :alt="__('Drużyna').' '.$team->name" eager width="1800" height="1000" class="absolute inset-0 -z-20 h-full w-full opacity-60" />
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/70 to-ink/30"></div>

    <div class="container-site pt-24 pb-14 lg:pt-36 lg:pb-20">
        <a href="{{ route('teams.index') }}" class="text-sm text-zinc-400 transition hover:text-white">← {{ __('Wszystkie drużyny') }}</a>
        @if($team->birthYearsLabel() || $currentSeason)
            <p class="eyebrow mt-6">{{ collect([$team->birthYearsLabel(), $currentSeason ? __('Sezon').' '.$currentSeason->name : null])->filter()->implode(' · ') }}</p>
        @endif
        <h1 class="heading-xl mt-3">{{ $team->name }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-4">
            <x-view-count :model="$team" class="text-white/70" />
            @livewire('like-button', ['type' => 'team', 'id' => $team->id, 'dark' => true], key('team-like-'.$team->id))
        </div>
        @if($team->description)
            <div class="mt-5 max-w-2xl text-lg text-zinc-300">{!! strip_tags($team->description, '<p><br><strong><em><b><i>') !!}</div>
        @endif

        <dl class="mt-10 flex flex-wrap gap-x-10 gap-y-4">
            <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Zawodnicy') }}</dt><dd class="font-display text-4xl font-bold">{{ $roster->count() }}</dd></div>
            <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Sztab') }}</dt><dd class="font-display text-4xl font-bold">{{ $staff->count() }}</dd></div>
            @if($upcoming_games->first())
                <div><dt class="text-xs text-zinc-400 uppercase">{{ __('Najbliższy mecz') }}</dt><dd class="font-display text-2xl font-semibold uppercase">{{ $upcoming_games->first()->game_date?->translatedFormat('d M') }} · {{ $upcoming_games->first()->opponentShortName() }}</dd></div>
            @endif
        </dl>
    </div>
</section>

{{-- ZAKŁADKI --}}
<nav class="sticky top-16 z-40 border-b border-zinc-200 bg-white/90 backdrop-blur lg:top-20" aria-label="{{ __('Sekcje drużyny') }}">
    <div class="container-site scrollbar-none flex gap-6 overflow-x-auto text-sm font-semibold">
        <a href="#kadra" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">{{ __('Kadra') }}</a>
        <a href="#sztab" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">{{ __('Sztab') }}</a>
        @if($team->trainingSessions->isNotEmpty())
            <a href="#treningi" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">{{ __('Treningi') }}</a>
        @endif
        <a href="#mecze" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">{{ __('Mecze') }}</a>
        @if($seasonCompetitions->isNotEmpty())
            <a href="#tabela" class="border-b-2 border-transparent py-4 whitespace-nowrap hover:border-sky-club">{{ __('Rozgrywki') }}</a>
        @endif
    </div>
</nav>

{{-- KADRA --}}
<section id="kadra" class="container-site scroll-mt-32 py-16 lg:py-20">
    <p class="eyebrow">{{ __('Kadra') }}</p>
    <h2 class="heading-lg mt-3">{{ __('Zawodnicy') }}</h2>

    @forelse($rosterByPosition as $position => $players)
        <div class="mt-10" data-reveal>
            <h3 class="flex items-center gap-3 text-sm font-semibold tracking-widest text-zinc-500 uppercase">
                {{ PlayerPosition::tryFrom((string) $position)?->label() ?? __('Pozostali') }}
                <span class="h-px flex-1 bg-zinc-200"></span>
            </h3>
            <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach($players as $player)
                    @php $number = $player->pivot?->jersey_number ?? $player->jersey_number; @endphp
                    <a href="{{ route('players.show', $player) }}" class="card card-hover group overflow-hidden">
                        <x-avatar :photo="$player->photo_path" :name="$player->full_name" :label="$number ?: null" class="aspect-[4/5] w-full text-6xl" />
                        <div class="p-4">
                            <p class="font-semibold leading-tight group-hover:text-sky-club-dark">{{ $player->full_name }}</p>
                            @if(count($player->positions()) > 1)
                                <p class="mt-0.5 text-xs text-zinc-500">{{ __('też') }}: {{ collect($player->positions())->skip(1)->map(fn ($extraPosition) => mb_strtolower($extraPosition->label()))->implode(', ') }}</p>
                            @endif
                            <p class="mt-1 flex items-center gap-2 text-xs text-zinc-500">
                                @if($number)<span class="font-bold text-zinc-900">#{{ $number }}</span>@endif
                                @if($player->pivot?->is_captain)<span class="rounded bg-sky-club px-1.5 py-0.5 font-bold text-white" title="{{ __('Kapitan') }}">C</span>@endif
                                @if($player->team_id !== $team->id && $team->birth_year)<span title="{{ __('Gra też w swojej drużynie macierzystej') }}">↑ {{ __('z młodszego rocznika') }}</span>@endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Skład drużyny zostanie wkrótce uzupełniony.') }}</p>
    @endforelse
</section>

{{-- SZTAB --}}
<section id="sztab" class="scroll-mt-32 bg-white py-16 lg:py-20">
    <div class="container-site" data-reveal>
        <p class="eyebrow">{{ __('Sztab') }}</p>
        <h2 class="heading-lg mt-3">{{ __('Trenerzy i sztab') }}</h2>

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
            <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Sztab drużyny zostanie wkrótce uzupełniony.') }}</p>
        @endif
    </div>
</section>

@if($team->trainingSessions->isNotEmpty())
{{-- TRENINGI --}}
<section id="treningi" class="container-site scroll-mt-32 pt-16 lg:pt-20" data-reveal>
    <p class="eyebrow">{{ __('Treningi') }}</p>
    <h2 class="heading-lg mt-3">{{ __('Plan treningów') }}</h2>
    @php
        $trainingDays = $team->trainingSessions->groupBy('weekday');
        $dayColumns = match ($trainingDays->count()) { 5 => 'lg:grid-cols-5', 6 => 'lg:grid-cols-3 xl:grid-cols-6', 7 => 'lg:grid-cols-4 xl:grid-cols-7', default => 'lg:grid-cols-4' };
    @endphp
    {{-- Cały tydzień obok siebie; w dniu treningi jeden pod drugim z symbolem miejsca (która część sali albo siłownia) --}}
    <ul class="mt-8 grid gap-3 sm:grid-cols-2 {{ $dayColumns }}">
        @foreach($trainingDays as $daySessions)
            <li class="card p-4">
                <p class="border-b border-zinc-100 pb-2 text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ $daySessions->first()->weekdayLabel() }}</p>
                @foreach($daySessions as $session)
                    <div class="mt-3 flex items-center gap-3">
                        <x-venue-icon :venue="$session->venue" />
                        <div class="min-w-0">
                            <p class="font-semibold tabular-nums">{{ $session->timeRange() }}</p>
                            <p class="text-sm text-zinc-600">{{ $session->venueLabel() }}@if($session->note) · {{ $session->note }}@endif</p>
                        </div>
                    </div>
                @endforeach
            </li>
        @endforeach
    </ul>
    <a href="{{ route('trainings.index') }}" class="mt-6 inline-block text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Plan wszystkich drużyn') }} →</a>
</section>
@endif

{{-- MECZE --}}
<section id="mecze" class="container-site scroll-mt-32 py-16 lg:py-20" data-reveal>
    <p class="eyebrow">{{ __('Mecze') }}</p>
    <h2 class="heading-lg mt-3">{{ __('Terminarz') }}</h2>

    @if($upcoming_games->isNotEmpty())
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($upcoming_games as $game)
                <x-match-card :game="$game" :badge="false" />
            @endforeach
        </div>
    @else
        <p class="mt-8 rounded-3xl bg-zinc-100 p-8 text-zinc-600">{{ __('Brak zaplanowanych meczów.') }}</p>
    @endif

    @if($recent_games->isNotEmpty())
        <h3 class="heading-md mt-14">{{ __('Ostatnie wyniki') }}</h3>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($recent_games as $game)
                <x-match-card :game="$game" :badge="false" />
            @endforeach
        </div>
    @endif
</section>

{{-- ROZGRYWKI: nazwa w rozgrywkach, droga przez etapy, tabela bieżącej grupy --}}
@if($seasonCompetitions->isNotEmpty())
<section id="tabela" class="container-site scroll-mt-32 pb-8" data-reveal>
    <p class="eyebrow">{{ __('Sezon') }} {{ $currentSeason?->name }}</p>
    <h2 class="heading-lg mt-3">{{ __('Rozgrywki') }}</h2>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        @foreach($seasonCompetitions as $entry)
            <div class="card overflow-hidden">
                <div class="border-b border-zinc-100 px-6 py-4">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-display text-xl font-semibold uppercase">{{ $entry['competition']->name }}</p>
                        <a href="{{ route('standings', ['competition' => $entry['competition']->getRouteKey()]) }}" class="text-sm font-semibold text-sky-club-dark hover:underline">{{ __('Wszystkie tabele') }} →</a>
                    </div>
                    @if($entry['names'])
                        <p class="mt-1 text-sm text-zinc-500">{{ __('Gra jako') }}: <span class="font-medium text-zinc-700">{{ implode(' / ', $entry['names']) }}</span></p>
                    @endif

                    @if($entry['steps']->contains(fn ($step) => $step['stage'] || $step['group']))
                        <ol class="mt-4 flex flex-wrap items-center gap-2 text-sm" aria-label="{{ __('Droga w rozgrywkach') }}">
                            @foreach($entry['steps'] as $step)
                                <li class="flex items-center gap-2">
                                    <span @class([
                                        'rounded-xl px-3 py-1.5 leading-tight',
                                        'bg-ink text-white' => $loop->last,
                                        'bg-zinc-100 text-zinc-700' => ! $loop->last,
                                    ])>
                                        <span class="block text-[11px] font-semibold tracking-wide uppercase opacity-70">{{ collect([$step['stage'], $step['group']])->filter()->implode(' · ') }}</span>
                                        <span class="font-semibold">{{ __(':position. miejsce', ['position' => $step['position']]) }}</span><span class="opacity-70"> {{ __('z :teams', ['teams' => $step['teams']]) }}</span>
                                    </span>
                                    @unless($loop->last)
                                        <span class="text-zinc-400" aria-hidden="true">→</span>
                                    @endunless
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>

                @if($entry['table']->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table data-sortable class="w-full text-sm">
                            <thead class="bg-zinc-50 text-xs text-zinc-500 uppercase">
                                <tr>
                                    <th class="px-6 py-3 text-left">{{ __('Drużyna') }}</th>
                                    <th class="px-3 py-3" title="{{ __('Mecze') }}">{{ __('M') }}</th>
                                    <th class="px-3 py-3" title="{{ __('Wygrane') }}">{{ __('W') }}</th>
                                    <th class="px-3 py-3" title="{{ __('Porażki') }}">{{ __('P') }}</th>
                                    <th class="hidden px-3 py-3 sm:table-cell">{{ __('Sety') }}</th>
                                    <th class="px-6 py-3 text-right">{{ __('Pkt') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100">
                                @foreach($entry['table'] as $standing)
                                    @php $ours = $standing->isOwnTeam() && $standing->competitor_id === $team->id; $ownTeams = $entry['table']->filter(fn ($row) => $row->isOwnTeam())->count(); @endphp
                                    <tr @class(['bg-sky-club/10 font-semibold' => $ours])>
                                        <td class="px-6 py-3">
                                            <span class="mr-3 inline-flex h-7 w-7 items-center justify-center rounded-full {{ $ours ? 'bg-sky-club text-white' : 'bg-zinc-100' }} text-xs font-bold">{{ $standing->position }}</span>
                                            @if($standing->isOwnTeam() && $standing->competitor)
                                                <a href="{{ route('teams.show', $standing->competitor) }}" class="hover:text-sky-club-dark"><x-team-label :label="$standing->competitorLabel($ownTeams > 1)" /></a>
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
                @else
                    <p class="px-6 py-5 text-sm text-zinc-500">{{ __('Tabela pojawi się po pierwszych meczach.') }}</p>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif
@endsection
