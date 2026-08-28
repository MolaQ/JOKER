@extends('layouts.public')

@section('title', 'Drużyny - Joker Piła')

@section('content')
<!-- Page Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Nasze Drużyny</h1>
        <p class="text-xl text-blue-100">
            Poznaj wszystkie drużyny Joker Piła
        </p>
    </div>
</div>

<!-- Teams Grid -->
<section class="container mx-auto px-4 py-16">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        @foreach($teams as $team)
            <article class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition">
                <div class="bg-gradient-to-r from-blue-900 to-blue-700 h-48 flex items-center justify-center">
                    <div class="text-center text-white">
                        <div class="text-6xl font-bold mb-2">{{ Str::substr($team->name, 0, 1) }}</div>
                        <div class="text-sm opacity-80">{{ Str::limit(strip_tags((string) $team->description), 120) }}</div>
                    </div>
                </div>

                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        {{ $team->name }}
                    </h2>

                    @if($team->allTrainers()->isNotEmpty())
                        <div class="mb-4 pb-4 border-b">
                            <div class="text-sm text-gray-500 mb-1">{{ $team->allTrainers()->count() > 1 ? 'Trenerzy' : 'Trener' }}</div>
                            <div class="font-semibold text-gray-900">
                                @foreach($team->allTrainers() as $trainer)
                                    <a href="{{ route('trainers.show', $trainer) }}" class="text-blue-700 hover:underline">{{ $trainer->name }}</a>@if(! $loop->last), @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div>
                            <div class="text-sm text-gray-500">Kategoria</div>
                            <div class="font-semibold text-gray-900">{{ $team->category }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Liczba zawodników</div>
                            <div class="font-semibold text-gray-900">{{ $team->players_count ?? $team->players()->count() }}</div>
                        </div>
                    </div>

                    <a href="{{ route('teams.show', $team) }}" class="block w-full bg-blue-900 text-white text-center py-3 rounded-lg hover:bg-blue-800 transition font-semibold">
                        Zobacz Drużynę
                    </a>
                </div>
            </article>
        @endforeach
    </div>
</section>
@endsection
