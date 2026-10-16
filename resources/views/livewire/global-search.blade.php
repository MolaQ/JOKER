<div class="relative w-full max-w-full">
    <input
        type="text"
        wire:model.live.debounce.250ms="query"
        placeholder="Szukaj: drużyny, zawodnicy, artykuły, mecze..."
        autofocus class="w-full rounded-full border border-zinc-200 bg-white px-5 py-3 text-sm text-zinc-900 shadow-sm outline-none focus:border-sky-club focus:ring-2 focus:ring-sky-club/30"
    >

    @if(trim($query) !== '')
        <div class="absolute z-50 mt-2 w-full rounded-2xl border border-zinc-200 bg-white text-zinc-900 shadow-2xl">
            <div class="max-h-96 overflow-y-auto p-2 text-sm">
                @if($teams->isEmpty() && $players->isEmpty() && $articles->isEmpty() && $games->isEmpty())
                    <p class="px-3 py-2 text-gray-500">Brak wyników dla: <strong>{{ $query }}</strong></p>
                @endif

                @if($teams->isNotEmpty())
                    <p class="px-3 py-1 text-xs font-bold uppercase text-gray-400">Drużyny</p>
                    @foreach($teams as $team)
                        <a href="{{ route('teams.show', $team) }}" class="block rounded px-3 py-2 hover:bg-gray-100">{{ $team->name }}</a>
                    @endforeach
                @endif

                @if($players->isNotEmpty())
                    <p class="mt-2 px-3 py-1 text-xs font-bold uppercase text-gray-400">Zawodnicy</p>
                    @foreach($players as $player)
                        <a href="{{ route('players.show', $player) }}" class="block rounded px-3 py-2 hover:bg-gray-100">{{ $player->full_name }}</a>
                    @endforeach
                @endif

                @if($articles->isNotEmpty())
                    <p class="mt-2 px-3 py-1 text-xs font-bold uppercase text-gray-400">Artykuły</p>
                    @foreach($articles as $article)
                        <a href="{{ route('articles.show', $article) }}" class="block rounded px-3 py-2 hover:bg-gray-100">{{ $article->title }}</a>
                    @endforeach
                @endif

                @if($games->isNotEmpty())
                    <p class="mt-2 px-3 py-1 text-xs font-bold uppercase text-gray-400">Mecze</p>
                    @foreach($games as $game)
                        <a href="{{ route('schedule.index') }}" class="block rounded px-3 py-2 hover:bg-gray-100">{{ $game->team?->name }} vs {{ $game->opponent }}</a>
                    @endforeach
                @endif
            </div>
        </div>
    @endif
</div>
