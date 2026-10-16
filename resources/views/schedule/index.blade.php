@extends('layouts.public')

@section('title', 'Terminarz - Joker Piła')

@section('content')
<section class="container-site pt-14 pb-6 lg:pt-20">
    <p class="eyebrow">Wszystkie mecze {{ $currentSeason?->name ?? 'aktualnego sezonu' }}</p>
    <h1 class="heading-xl mt-4">Terminarz Meczów</h1>
</section>

<!-- Navigation Tabs -->
<div class="bg-white border-b sticky top-16 z-40 lg:top-20 shadow-sm">
    <div class="container-site">
        <nav class="flex space-x-8">
            <a href="{{ route('schedule.index') }}" class="py-4 px-2 border-b-2 border-sky-club font-semibold text-sky-club-dark">
                Terminarz
            </a>
            <a href="{{ route('standings') }}" class="py-4 px-2 border-b-2 border-transparent hover:border-zinc-300 text-zinc-600 hover:text-zinc-900">
                Tabela
            </a>
        </nav>
    </div>
</div>

<!-- Upcoming Games -->
<section class="container-site py-12">
    <h2 class="text-3xl font-bold text-zinc-900 mb-8">Nadchodzące Mecze</h2>

    @if($upcoming_games->count() > 0)
        <div class="space-y-4">
            @foreach($upcoming_games as $game)
                <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-6 hover:shadow-lg transition">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <!-- Date & Time -->
                        <div class="flex-shrink-0 text-center md:text-left">
                            <div class="text-sm text-zinc-500 mb-1">
                                {{ $game->game_date?->format('d.m.Y') }}
                            </div>
                            <div class="text-2xl font-bold text-sky-club-dark">
                                {{ $game->game_date?->format('H:i') }}
                            </div>
                        </div>

                        <div class="h-16 w-px bg-zinc-200 hidden md:block"></div>

                        <!-- Teams -->
                        <div class="flex items-center space-x-8 flex-1 justify-center">
                            <div class="text-center flex-1">
                                <a href="{{ route('teams.show', $game->team) }}" class="font-bold text-lg text-zinc-900 hover:text-sky-club-dark transition">
                                    {{ $game->is_home ? $game->team->name : $game->opponentDisplayName() }}
                                </a>
                                <div class="text-sm text-zinc-500 mt-1">Gospodarze</div>
                            </div>

                            <div class="text-3xl font-bold text-zinc-400 px-4">vs</div>

                            <div class="text-center flex-1">
                                <div class="font-bold text-lg text-zinc-900">{{ $game->is_home ? $game->opponentDisplayName() : $game->team->name }}</div>
                                <div class="text-sm text-zinc-500 mt-1">Goście</div>
                            </div>
                        </div>

                        <div class="h-16 w-px bg-zinc-200 hidden md:block"></div>

                        <!-- Location -->
                        <div class="flex-shrink-0 text-center md:text-right">
                            @if($game->venue)
                                <div class="text-sm text-zinc-500 mb-1">Miejsce</div>
                                <div class="font-semibold text-zinc-900">{{ $game->venue }}</div>
                            @else
                                <div class="text-sm text-zinc-400">Brak lokalizacji</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-zinc-100 rounded-lg p-12 text-center">
            <p class="text-zinc-600 text-lg">Brak zaplanowanych meczów.</p>
        </div>
    @endif
</section>

<!-- Recent Games -->
@if($recent_games->count() > 0)
    <section class="bg-zinc-50 py-12">
        <div class="container-site">
            <h2 class="text-3xl font-bold text-zinc-900 mb-8">Ostatnie Wyniki</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($recent_games as $game)
                    <div class="bg-white rounded-3xl ring-1 ring-zinc-200/70 p-6">
                        <div class="text-sm text-zinc-500 mb-4">
                            {{ $game->game_date?->format('d.m.Y H:i') }}
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-center flex-1">
                                <a href="{{ route('teams.show', $game->team) }}" class="font-bold text-lg text-zinc-900 hover:text-sky-club-dark transition">
                                    {{ $game->is_home ? $game->team->name : $game->opponentDisplayName() }}
                                </a>
                                <div class="text-4xl font-bold mt-3 {{ $game->home_score > $game->away_score ? 'text-green-600' : 'text-zinc-600' }}">
                                    {{ $game->home_score }}
                                </div>
                            </div>

                            <div class="text-3xl font-bold text-zinc-400 px-6">:</div>

                            <div class="text-center flex-1">
                                <div class="font-bold text-lg text-zinc-900">{{ $game->is_home ? $game->opponentDisplayName() : $game->team->name }}</div>
                                <div class="text-4xl font-bold mt-3 {{ $game->away_score > $game->home_score ? 'text-green-600' : 'text-zinc-600' }}">
                                    {{ $game->away_score }}
                                </div>
                            </div>
                        </div>

                        @if($game->venue)
                            <div class="text-center mt-4 pt-4 border-t text-sm text-zinc-600">
                                📍 {{ $game->venue }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
