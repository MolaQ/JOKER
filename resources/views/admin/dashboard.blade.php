@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Dashboard</h1>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
        <!-- Drużyny -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Drużyny</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['teams'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zawodnicy -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Zawodnicy</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['players'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mecze -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Mecze</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['games'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Artykuły -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Artykuły</dt>
                            <dd class="text-3xl font-semibold text-gray-900">{{ $stats['articles'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Data Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Nadchodzące mecze -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Nadchodzące mecze ({{ $stats['upcoming_games'] }})</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @forelse($recent_games as $game)
                    <div class="mb-4 pb-4 border-b border-gray-200 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $game->team->name }} vs {{ $game->opponent }}</p>
                                <p class="text-sm text-gray-500">{{ $game->game_date->format('d.m.Y H:i') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $game->status === 'scheduled' ? 'blue' : ($game->status === 'finished' ? 'green' : 'gray') }}-100 text-{{ $game->status === 'scheduled' ? 'blue' : ($game->status === 'finished' ? 'green' : 'gray') }}-800">
                                {{ $game->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Brak nadchodzących meczów</p>
                @endforelse
                <a href="{{ route('admin.games.index') }}" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-500">
                    Zobacz wszystkie mecze →
                </a>
            </div>
        </div>

        <!-- Ostatnie artykuły -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Ostatnie artykuły ({{ $stats['published_articles'] }})</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @forelse($recent_articles as $article)
                    <div class="mb-4 pb-4 border-b border-gray-200 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $article->title }}</p>
                                <p class="text-sm text-gray-500">{{ $article->author->name }} • {{ $article->published_at->format('d.m.Y') }}</p>
                            </div>
                            @if($article->is_featured)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Wyróżniony
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Brak opublikowanych artykułów</p>
                @endforelse
                <a href="{{ route('admin.articles.index') }}" class="mt-4 text-sm font-medium text-blue-600 hover:text-blue-500">
                    Zobacz wszystkie artykuły →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
