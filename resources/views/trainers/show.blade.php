@extends('layouts.public')

@section('title', $trainer->name . ' - Joker Piła')

@section('content')
<!-- Trainer Header -->
<div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-900 text-white py-16">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row items-center space-y-6 md:space-y-0 md:space-x-8">
            @if($trainer->avatar_path)
                <img src="{{ Storage::url($trainer->avatar_path) }}" alt="{{ $trainer->name }}" class="w-32 h-32 rounded-full object-cover flex-shrink-0 border-4 border-white/20">
            @else
                <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-blue-900 font-bold text-4xl">{{ Str::of($trainer->name)->explode(' ')->map(fn ($part) => Str::substr($part, 0, 1))->join('') }}</span>
                </div>
            @endif

            <div class="text-center md:text-left">
                <h1 class="text-4xl md:text-5xl font-bold mb-3">{{ $trainer->name }}</h1>
                <div class="flex flex-wrap items-center justify-center md:justify-start gap-4 text-lg text-blue-100">
                    <span>Trener</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trainer Details -->
<div class="container mx-auto px-4 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Teams Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6 sticky top-4">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Prowadzone drużyny</h2>

                @if($teams->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($teams as $team)
                            <a href="{{ route('teams.show', $team) }}" class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3 hover:border-blue-300 hover:bg-blue-50 transition">
                                <span class="font-semibold text-gray-900">{{ $team->name }}</span>
                                @if($team->trainer_id === $trainer->id)
                                    <span class="text-xs font-medium text-blue-700 bg-blue-100 rounded-full px-2 py-0.5">Trener główny</span>
                                @else
                                    <span class="text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-2 py-0.5">Trener pomocniczy</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-sm">Ten trener nie prowadzi obecnie żadnej drużyny.</p>
                @endif
            </div>
        </div>

        <!-- Bio & Description -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">O trenerze</h2>

                @if($trainer->additional_info)
                    <div class="prose max-w-none text-gray-700 leading-relaxed">
                        {!! $trainer->additional_info !!}
                    </div>
                @else
                    <div class="bg-gray-50 rounded-lg p-8 text-center">
                        <p class="text-gray-500">Brak informacji o trenerze.</p>
                    </div>
                @endif

                @if($trainer->instagram)
                    <p class="mt-6 text-sm text-gray-700">
                        Instagram:
                        <a href="{{ $trainer->instagram }}" target="_blank" rel="noopener" class="font-semibold text-blue-700 hover:underline">{{ $trainer->instagram }}</a>
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
