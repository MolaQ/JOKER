@extends('layouts.public')

@section('title', 'Terminarz - Joker Piła')

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Terminarz Meczów</h1>
        <p class="text-xl text-blue-100">
            Wszystkie mecze {{ $currentSeason?->name ?? 'aktualnego sezonu' }}
        </p>
    </div>
</div>

<!-- Navigation Tabs -->
<div class="bg-white border-b sticky top-0 z-10 shadow-sm">
    <div class="container mx-auto px-4">
        <nav class="flex space-x-8">
            <a href="{{ route('schedule.index') }}" class="py-4 px-2 border-b-2 border-blue-900 font-semibold text-blue-900">
                Terminarz
            </a>
            <a href="{{ route('standings') }}" class="py-4 px-2 border-b-2 border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-900">
                Tabela
            </a>
        </nav>
    </div>
</div>

<!-- Upcoming Games -->
<section class="container mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold text-gray-900 mb-8">Nadchodzące Mecze</h2>

    @if($upcoming_games->count() > 0)
        <div class="space-y-4">
            @foreach($upcoming_games as $game)
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <!-- Date & Time -->
                        <div class="flex-shrink-0 text-center md:text-left">
                            <div class="text-sm text-gray-500 mb-1">
                                {{ $game->game_date?->format('d.m.Y') }}
                            </div>
                            <div class="text-2xl font-bold text-blue-900">
                                {{ $game->game_date?->format('H:i') }}
                            </div>
                        </div>

                        <div class="h-16 w-px bg-gray-200 hidden md:block"></div>

                        <!-- Teams -->
                        <div class="flex items-center space-x-8 flex-1 justify-center">
                            <div class="text-center flex-1">
                                <a href="{{ route('teams.show', $game->team) }}" class="font-bold text-lg text-gray-900 hover:text-blue-900 transition">
                                    {{ $game->is_home ? $game->team->name : $game->opponentDisplayName() }}
                                </a>
                                <div class="text-sm text-gray-500 mt-1">Gospodarze</div>
                            </div>

                            <div class="text-3xl font-bold text-gray-400 px-4">vs</div>

                            <div class="text-center flex-1">
                                <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->opponentDisplayName() : $game->team->name }}</div>
                                <div class="text-sm text-gray-500 mt-1">Goście</div>
                            </div>
                        </div>

                        <div class="h-16 w-px bg-gray-200 hidden md:block"></div>

                        <!-- Location -->
                        <div class="flex-shrink-0 text-center md:text-right">
                            @if($game->venue)
                                <div class="text-sm text-gray-500 mb-1">Miejsce</div>
                                <div class="font-semibold text-gray-900">{{ $game->venue }}</div>
                            @else
                                <div class="text-sm text-gray-400">Brak lokalizacji</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-gray-100 rounded-lg p-12 text-center">
            <p class="text-gray-600 text-lg">Brak zaplanowanych meczów.</p>
        </div>
    @endif
</section>

<!-- Recent Games -->
@if($recent_games->count() > 0)
    <section class="bg-gray-50 py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-gray-900 mb-8">Ostatnie Wyniki</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($recent_games as $game)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="text-sm text-gray-500 mb-4">
                            {{ $game->game_date?->format('d.m.Y H:i') }}
                        </div>

                        <div class="flex items-center justify-between">
                            <div class="text-center flex-1">
                                <a href="{{ route('teams.show', $game->team) }}" class="font-bold text-lg text-gray-900 hover:text-blue-900 transition">
                                    {{ $game->is_home ? $game->team->name : $game->opponentDisplayName() }}
                                </a>
                                <div class="text-4xl font-bold mt-3 {{ $game->home_score > $game->away_score ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ $game->home_score }}
                                </div>
                            </div>

                            <div class="text-3xl font-bold text-gray-400 px-6">:</div>

                            <div class="text-center flex-1">
                                <div class="font-bold text-lg text-gray-900">{{ $game->is_home ? $game->opponentDisplayName() : $game->team->name }}</div>
                                <div class="text-4xl font-bold mt-3 {{ $game->away_score > $game->home_score ? 'text-green-600' : 'text-gray-600' }}">
                                    {{ $game->away_score }}
                                </div>
                            </div>
                        </div>

                        @if($game->venue)
                            <div class="text-center mt-4 pt-4 border-t text-sm text-gray-600">
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
