@extends('layouts.public')

@section('title', $team->name . ' - Joker Piła')

@section('content')
<!-- Team Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <div class="flex items-center space-x-6">
            <x-placeholder-image :label="$team->name" height="h-24" class="w-24 rounded-full" />
            <div>
                <h1 class="text-4xl md:text-5xl font-bold mb-2">{{ $team->name }}</h1>
                @if($team->allTrainers()->isNotEmpty())
                    <p class="text-xl text-blue-100">
                        {{ $team->allTrainers()->count() > 1 ? 'Trenerzy' : 'Trener' }}:
                        @foreach($team->allTrainers() as $trainer)
                            <a href="{{ route('trainers.show', $trainer) }}" class="text-white underline hover:no-underline">{{ $trainer->name }}</a>@if(! $loop->last), @endif
                        @endforeach
                    </p>
                @endif
            </div>
        </div>
        <div class="mt-4">
            @livewire('like-button', ['type' => 'team', 'id' => $team->id], key('team-page-like-'.$team->id))
        </div>
    </div>
</div>

<!-- Team Navigation -->
<div class="bg-white border-b sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto px-4">
        <nav class="flex space-x-8 overflow-x-auto">
            <a href="#kadra" class="py-4 px-2 border-b-2 border-blue-900 font-semibold text-blue-900 whitespace-nowrap">
                Kadra
            </a>
            <a href="#mecze" class="py-4 px-2 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap">
                Mecze
            </a>
            <a href="#tabela" class="py-4 px-2 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900 whitespace-nowrap">
                Tabela
            </a>
        </nav>
    </div>
</div>

<div class="container mx-auto px-4 py-12">
    <!-- Team Squad -->
    <section id="kadra" class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Kadra Zawodników</h2>

        @if($roster->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($roster as $player)
                    <a href="{{ route('players.show', $player) }}" class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition group">
                        <div class="flex items-start space-x-4">
                            <div class="w-16 h-16 bg-blue-900 text-white rounded-lg flex items-center justify-center text-2xl font-bold flex-shrink-0">
                                {{ $player->jersey_number }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-blue-900 transition">
                                    {{ $player->full_name }}
                                </h3>
                                <div class="text-sm text-gray-600 mb-2">
                                    {{ $player->position->label() }}
                                </div>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    @if($player->height)
                                        <span>{{ $player->height }} cm</span>
                                    @endif
                                    @if($player->birth_date)
                                        <span>{{ $player->age() }} lat</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="bg-gray-100 rounded-lg p-8 text-center">
                <p class="text-gray-600">Brak zawodników w tej drużynie.</p>
            </div>
        @endif
    </section>

    <!-- Upcoming Games -->
    <section id="mecze" class="mb-16">
        <h2 class="text-3xl font-bold text-gray-900 mb-8">Najbliższe Mecze</h2>

        @if($upcoming_games->count() > 0)
            <div class="space-y-4">
                @foreach($upcoming_games as $game)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div class="text-sm text-gray-500">
                                {{ $game->game_date?->format('d.m.Y H:i') }}
                            </div>

                            <div class="flex items-center space-x-8 flex-1 justify-center">
                                <div class="text-center">
                                    <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->team->name : $game->opponent }}</div>
                                    <div class="text-xs text-gray-500">Gospodarze</div>
                                </div>

                                <div class="text-2xl font-bold text-gray-400">vs</div>

                                <div class="text-center">
                                    <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->opponent : $game->team->name }}</div>
                                    <div class="text-xs text-gray-500">Goście</div>
                                </div>
                            </div>

                            @if($game->venue)
                                <div class="text-sm text-gray-600">
                                    📍 {{ $game->venue }}
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="bg-gray-100 rounded-lg p-8 text-center">
                <p class="text-gray-600">Brak zaplanowanych meczów.</p>
            </div>
        @endif

        @if($recent_games->count() > 0)
            <div class="mt-12">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">Ostatnie Mecze</h3>

                <div class="space-y-4">
                    @foreach($recent_games as $game)
                        <div class="bg-white rounded-lg shadow-md p-6">
                            <div class="flex items-center justify-between flex-wrap gap-4">
                                <div class="text-sm text-gray-500">
                                    {{ $game->game_date?->format('d.m.Y') }}
                                </div>

                                <div class="flex items-center space-x-8 flex-1 justify-center">
                                    <div class="text-center">
                                        <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->team->name : $game->opponent }}</div>
                                        <div class="text-3xl font-bold text-blue-900 mt-2">{{ $game->home_score }}</div>
                                    </div>

                                    <div class="text-2xl font-bold text-gray-400">:</div>

                                    <div class="text-center">
                                        <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->opponent : $game->team->name }}</div>
                                        <div class="text-3xl font-bold text-gray-600 mt-2">{{ $game->away_score }}</div>
                                    </div>
                                </div>

                                @if($game->venue)
                                    <div class="text-sm text-gray-600">
                                        📍 {{ $game->venue }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </section>

    <!-- League Standing -->
    @if(isset($standingsByCompetition) && $standingsByCompetition->isNotEmpty())
        <section id="tabela" class="mb-16">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Tabela Ligowa</h2>

            <div class="space-y-8">
                @foreach($standingsByCompetition as $competitionName => $competitionStandings)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-3 bg-blue-50 text-sm font-semibold text-blue-900 border-b">
                            {{ $competitionName }}
                        </div>

                        <div class="grid grid-cols-3 md:grid-cols-8 gap-4 p-4 bg-gray-50 border-b font-semibold text-xs uppercase text-gray-500">
                            <div class="col-span-2 md:col-span-3">Drużyna</div>
                            <div class="text-center">M</div>
                            <div class="text-center">W</div>
                            <div class="text-center">P</div>
                            <div class="text-center hidden md:block">Sety</div>
                            <div class="text-center">Pkt</div>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @foreach($competitionStandings as $standing)
                                <div class="grid grid-cols-3 md:grid-cols-8 gap-4 p-4 items-center {{ $standing->competitor?->is($team) ? 'bg-blue-50' : '' }}">
                                    <div class="col-span-2 md:col-span-3 flex items-center space-x-3">
                                        <div class="w-8 h-8 bg-blue-900 text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                                            {{ $standing->position }}
                                        </div>
                                        @if($standing->competitorLogoPath())
                                            <img src="{{ Storage::url($standing->competitorLogoPath()) }}" class="h-6 w-6 object-cover rounded-full flex-shrink-0" alt="">
                                        @endif
                                        @if($standing->isOwnTeam() && $standing->competitor)
                                            <a href="{{ route('teams.show', $standing->competitor) }}" class="font-semibold text-gray-900 hover:text-blue-900 transition">{{ $standing->competitorName() }}</a>
                                        @else
                                            <span class="font-semibold text-gray-900">{{ $standing->competitorName() }}</span>
                                        @endif
                                    </div>
                                    <div class="text-center">{{ $standing->played }}</div>
                                    <div class="text-center text-green-600 font-semibold">{{ $standing->won }}</div>
                                    <div class="text-center text-red-600 font-semibold">{{ $standing->lost }}</div>
                                    <div class="text-center hidden md:block">{{ $standing->sets_won }}:{{ $standing->sets_lost }}</div>
                                    <div class="text-center font-bold text-blue-900 text-lg">{{ $standing->points }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
