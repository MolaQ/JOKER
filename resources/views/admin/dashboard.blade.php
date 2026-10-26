@extends('admin.layout')

@section('title', 'Pulpit')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">Cześć, {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}!</h1>
    <p class="mt-1 mb-4 text-gray-600">Co chcesz dziś zrobić?</p>

    @php
        $quickActions = array_filter([
            ['newspaper', 'Dodaj aktualność', 'Napisz wpis na stronę', 'admin.articles.index', 'articles.manage'],
            ['ball', 'Wpisz wynik meczu', 'Albo dodaj nowy mecz', 'admin.games.index', 'games.manage'],
            ['user', 'Dodaj zawodnika', 'Lub popraw dane w kadrze', 'admin.players.index', 'teams.manage'],
            ['users', 'Drużyny i sztab', 'Zdjęcie, trenerzy, funkcje', 'admin.teams.index', 'teams.manage'],
            ['video', 'Transmisje i galerie', 'Link do transmisji, zdjęcia', 'admin.media.index', 'media.manage'],
            ['cog', 'Wygląd i ustawienia', 'Kolory, menu, pasek, teksty', 'admin.settings.index', 'site.manage'],
            ['list', 'Podstrony', 'Dla rodziców, FAQ, O klubie', 'admin.pages.index', 'site.manage'],
        ], fn (array $action) => auth()->user()->can($action[4]));
    @endphp
    <div class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        @foreach($quickActions as [$icon, $title, $hint, $route])
            <a href="{{ route($route) }}" title="{{ $hint }}" class="group flex items-center gap-3 rounded-xl bg-white px-3 py-3 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-zinc-200 transition hover:-translate-y-0.5 hover:shadow-md hover:ring-sky-300">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-600 ring-1 ring-sky-100 transition group-hover:bg-sky-600 group-hover:text-white"><x-admin-icon :name="$icon" class="size-[18px]" /></span>
                <span class="leading-tight">{{ $title }}</span>
            </a>
        @endforeach
    </div>

    @if($approvals->isNotEmpty())
        <div class="mb-6 flex flex-wrap items-center gap-2 rounded-xl bg-amber-50 px-4 py-3 ring-1 ring-amber-300">
            <span class="mr-1 text-sm font-semibold text-amber-900">Czeka na zatwierdzenie:</span>
            @foreach($approvals as $section)
                <a href="{{ route($section['route']) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white px-3 py-1 text-sm font-medium text-gray-800 ring-1 ring-amber-200 hover:ring-amber-400">
                    <x-admin-icon :name="$section['icon']" class="size-4 text-zinc-500" /> {{ $section['label'] }}
                    <span class="rounded-full bg-amber-400 px-1.5 text-xs font-bold text-gray-900">{{ $section['count'] }}</span>
                </a>
            @endforeach
        </div>
    @endif

    <div class="mb-8 grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-7">
        @foreach($stats as [$icon, $label, $value, $note, $route])
            @php $tile = 'rounded-xl bg-white px-3.5 py-3 shadow-sm ring-1 ring-zinc-200'; @endphp
            @if($route)<a href="{{ route($route) }}" class="{{ $tile }} transition hover:ring-sky-300">@else<div class="{{ $tile }}">@endif
                <span class="flex items-center gap-1.5 text-xs font-medium text-zinc-500"><x-admin-icon :name="$icon" class="size-4 shrink-0 text-zinc-400" /> <span class="leading-tight">{{ $label }}</span></span>
                <span class="mt-1 block text-2xl font-semibold tracking-tight text-zinc-900 tabular-nums">{{ $value }}</span>
                @if($note)<span class="block truncate text-xs text-zinc-400">{{ $note }}</span>@endif
            @if($route)</a>@else</div>@endif
        @endforeach
    </div>

    <!-- Recent Data Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Nadchodzące mecze -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-200">
            <div class="border-b border-zinc-100 px-4 py-4 sm:px-6">
                <h3 class="text-base font-semibold text-zinc-900">Najbliższe mecze</h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
                @forelse($recent_games as $game)
                    <div class="mb-4 pb-4 border-b border-gray-200 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $game->team->name }} vs {{ $game->opponent }}</p>
                                <p class="text-sm text-gray-500">{{ $game->kickoffLabel('d.m.Y') }}</p>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $game->status === 'scheduled' ? 'blue' : ($game->status === 'finished' ? 'green' : 'gray') }}-100 text-{{ $game->status === 'scheduled' ? 'blue' : ($game->status === 'finished' ? 'green' : 'gray') }}-800">
                                {{ ['scheduled' => 'zaplanowany', 'finished' => 'rozegrany', 'cancelled' => 'odwołany'][$game->status] ?? $game->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Brak nadchodzących meczów</p>
                @endforelse
                <a href="{{ route('admin.games.index') }}" class="mt-4 text-sm font-medium text-sky-700 hover:text-sky-900">
                    Zobacz wszystkie mecze →
                </a>
            </div>
        </div>

        <!-- Ostatnie artykuły -->
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-200">
            <div class="border-b border-zinc-100 px-4 py-4 sm:px-6">
                <h3 class="text-base font-semibold text-zinc-900">Ostatnie aktualności</h3>
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
                <a href="{{ route('admin.articles.index') }}" class="mt-4 text-sm font-medium text-sky-700 hover:text-sky-900">
                    Zobacz wszystkie artykuły →
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
