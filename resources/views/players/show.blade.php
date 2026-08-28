@extends('layouts.public')

@section('title', $player->full_name . ' - Joker Piła')

@section('content')
<!-- Player Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center space-y-6 md:space-y-0 md:space-x-8">
            <!-- Player Number -->
            <div class="w-32 h-32 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                <span class="text-blue-900 font-bold text-6xl">{{ $player->jersey_number }}</span>
            </div>

            <!-- Player Info -->
            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-5xl font-bold mb-3">
                    {{ $player->full_name }}
                </h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-lg text-blue-100">
                    <span>{{ $player->position->label() }}</span>
                    <span>•</span>
                    <a href="{{ route('teams.show', $player->team) }}" class="hover:text-white transition">
                        {{ $player->team->name }}
                    </a>
                </div>
                <div class="mt-4 flex justify-center md:justify-start">
                    @livewire('like-button', ['type' => 'player', 'id' => $player->id], key('player-like-'.$player->id))
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Player Details -->
<div class="container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Stats Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6 sticky top-4">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Statystyki</h2>

                <div class="space-y-4">
                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Numer</span>
                        <span class="font-bold text-2xl text-blue-900">{{ $player->jersey_number }}</span>
                    </div>

                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Pozycja</span>
                        <span class="font-semibold text-gray-900">{{ $player->position->label() }}</span>
                    </div>

                    @if($player->height)
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Wzrost</span>
                            <span class="font-semibold text-gray-900">{{ $player->height }} cm</span>
                        </div>
                    @endif

                    @if($player->weight)
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Waga</span>
                            <span class="font-semibold text-gray-900">{{ $player->weight }} kg</span>
                        </div>
                    @endif

                    @if($player->reach)
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Zasięg</span>
                            <span class="font-semibold text-gray-900">{{ $player->reach }} cm</span>
                        </div>
                    @endif

                    @if($player->spike_reach)
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Zasięg w ataku</span>
                            <span class="font-semibold text-gray-900">{{ $player->spike_reach }} cm</span>
                        </div>
                    @endif

                    @if($player->birth_date)
                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Wiek</span>
                            <span class="font-semibold text-gray-900">{{ $player->age() }} lat</span>
                        </div>

                        <div class="flex justify-between items-center pb-3 border-b">
                            <span class="text-gray-600">Data urodzenia</span>
                            <span class="font-semibold text-gray-900">{{ $player->birth_date->format('d.m.Y') }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between items-center pb-3 border-b">
                        <span class="text-gray-600">Status</span>
                        @if($player->is_active)
                            <span class="bg-green-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full">
                                Aktywny
                            </span>
                        @else
                            <span class="bg-gray-100 text-gray-800 text-sm font-semibold px-3 py-1 rounded-full">
                                Nieaktywny
                            </span>
                        @endif
                    </div>

                    @if(isset($seasonTeams) && $seasonTeams->count() > 1)
                        <div class="pt-3">
                            <div class="mb-2 text-gray-600">Gra również w:</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($seasonTeams as $seasonTeam)
                                    @if($seasonTeam->id !== $player->team_id)
                                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800">{{ $seasonTeam->name }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    <a href="{{ route('teams.show', $player->team) }}" class="block w-full bg-blue-900 text-white text-center py-3 rounded-lg hover:bg-blue-800 transition font-semibold">
                        Zobacz drużynę
                    </a>
                </div>
            </div>
        </div>

        <!-- Bio & Description -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">O zawodniku</h2>

                @if($player->bio)
                    <div class="prose max-w-none text-gray-700 leading-relaxed">
                        {!! $player->bio !!}
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-8 text-center">
                        <p class="text-gray-500">Brak informacji o zawodniku.</p>
                    </div>
                @endif
            </div>

            @if($player->additional_info || $player->instagram)
                <div class="bg-white rounded-lg shadow-lg p-8 mt-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Dodatkowe informacje</h2>

                    @if($player->instagram)
                        <p class="mb-3 text-sm text-gray-700">
                            Instagram:
                            <a href="{{ $player->instagram }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 hover:underline">{{ $player->instagram }}</a>
                        </p>
                    @endif

                    @if($player->additional_info)
                        <div class="text-gray-700 leading-relaxed">{!! $player->additional_info !!}</div>
                    @endif
                </div>
            @endif

            <!-- Position Description -->
            <div class="bg-blue-50 rounded-lg p-6 mt-6">
                <h3 class="text-lg font-bold text-blue-900 mb-3">{{ $player->position->label() }}</h3>
                <p class="text-gray-700 leading-relaxed">
                    @switch($player->position->value)
                        @case('libero')
                            Libero to zawodnik specjalizujący się w obronie i przyjęciu zagrywki. Nie ma prawa do ataku i gry w polu ataku.
                            @break
                        @case('setter')
                            Rozgrywający to strategiczny zawodnik zespołu, odpowiedzialny za ustawienie piłki do ataku.
                            @break
                        @case('outside_hitter')
                            Przyjmujący to wszechstronny zawodnik grający w pierwszej linii, kluczowy w ataku i przyjęciu.
                            @break
                        @case('middle_blocker')
                            Środkowy to zawodnik odpowiedzialny za blok i szybkie ataki w centrum siatki.
                            @break
                        @case('opposite')
                            Atakujący to główny snajper zespołu, skupiony przede wszystkim na punktowaniu.
                            @break
                    @endswitch
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Back to Team -->
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4 text-center">
        <a href="{{ route('teams.show', $player->team) }}" class="inline-flex items-center text-blue-900 hover:text-blue-700 font-semibold">
            ← Powrót do drużyny {{ $player->team->name }}
        </a>
    </div>
</div>
@endsection
