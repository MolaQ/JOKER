@extends('admin.layout')

@section('title', 'Pulpit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">Cześć, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}!</h1>
    <p class="mt-1 mb-4 text-gray-600">Co chcesz dziś zrobić?</p>

    @php
        $quickActions = array_filter([
            ['📰', 'Dodaj aktualność', 'Napisz wpis na stronę', 'admin.articles.index', 'articles.manage'],
            ['🏐', 'Wpisz wynik meczu', 'Albo dodaj nowy mecz', 'admin.games.index', 'games.manage'],
            ['🎽', 'Dodaj zawodnika', 'Lub popraw dane w kadrze', 'admin.players.index', 'teams.manage'],
            ['👥', 'Drużyny i sztab', 'Zdjęcie, trenerzy, funkcje', 'admin.teams.index', 'teams.manage'],
            ['🎥', 'Transmisje i galerie', 'Link do transmisji, zdjęcia', 'admin.media.index', 'media.manage'],
            ['🎨', 'Wygląd i ustawienia', 'Kolory, menu, pasek, teksty', 'admin.settings.index', 'site.manage'],
            ['📑', 'Podstrony', 'Dla rodziców, FAQ, O klubie', 'admin.pages.index', 'site.manage'],
        ], fn (array $action) => auth()->user()->can($action[4]));
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        @foreach($quickActions as [$icon, $title, $hint, $route])
            <a href="{{ route($route) }}" title="{{ $hint }}" class="group flex items-center gap-2 rounded-xl bg-white px-3 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-gray-200 transition hover:text-sky-600 hover:ring-sky-400">
                <span class="text-xl" aria-hidden="true">{{ $icon }}</span>
                <span class="leading-tight">{{ $title }}</span>
            </a>
        @endforeach
    </div>

    @if($approvals->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-300">
            <span class="mr-1 text-sm font-semibold text-amber-900">Czeka na zatwierdzenie:</span>
            @foreach($approvals as $section)
                <a href="{{ route($section['route']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm font-medium text-gray-800 ring-1 ring-amber-200 hover:ring-amber-400">
                    <span aria-hidden="true">{{ $section['icon'] }}</span> {{ $section['label'] }}
                    <span class="rounded-full bg-amber-400 px-1.5 text-xs font-bold text-gray-900">{{ $section['count'] }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mb-8 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        @foreach($stats as [$icon, $label, $value, $note, $route])
            @php $tile = 'rounded-xl bg-white px-3 py-2.5 shadow-sm ring-1 ring-gray-200'; @endphp
            @if($route)<a href="{{ route($route) }}" class="{{ $tile }} transition hover:ring-sky-400">@else<div class="{{ $tile }}">@endif
                <span class="flex items-center gap-1.5 text-xs font-medium text-gray-500"><span aria-hidden="true">{{ $icon }}</span> {{ $label }}</span>
                <span class="mt-0.5 block text-2xl font-bold text-gray-900 tabular-nums">{{ $value }}</span>
                @if($note)<span class="block truncate text-xs text-gray-400">{{ $note }}</span>@endif
            @if($route)</a>@else</div>@endif
        @endforeach
    </div>

    <!-- Recent Data Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Nadchodzące mecze -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Najbliższe mecze</h3>
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
                                {{ ['scheduled' => 'zaplanowany', 'finished' => 'rozegrany', 'cancelled' => 'odwołany'][$game->status] ?? $game->status }}
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
                <h3 class="text-lg leading-6 font-medium text-gray-900">Ostatnie aktualności</h3>
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
