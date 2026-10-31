<div class="space-y-4">
    @forelse($groups as $key => $group)
        @php $first = $group->first(); @endphp
        <div wire:key="dubel-{{ $key }}" class="admin-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-amber-700 uppercase">⚠ Możliwy dubel</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $first->team->name }} · {{ $first->opponentDisplayName() }}</p>
                    <p class="text-sm text-gray-500">{{ $first->game_date->translatedFormat('l, j F Y') }} · {{ $group->count() }} mecze tego dnia z tym rywalem</p>
                </div>
                <button type="button" wire:click="dismiss('{{ $key }}')" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">To nie dubel</button>
            </div>
            <ul class="mt-3 divide-y divide-gray-100 rounded-lg ring-1 ring-gray-200">
                @foreach($group as $game)
                    <li wire:key="dubel-game-{{ $game->id }}" class="flex flex-wrap items-center justify-between gap-3 px-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <p class="text-gray-900">
                                <span class="font-semibold">{{ $game->game_date->format('H:i') === '00:00' ? 'godz. do ustalenia' : $game->game_date->format('H:i') }}</span>
                                · {{ $game->is_home ? 'u siebie' : 'na wyjeździe' }}
                                · {{ ['scheduled' => 'Zaplanowany', 'live' => 'Na żywo', 'finished' => 'Zakończony'][$game->status] ?? $game->status }}
                                @if(! is_null($game->home_score) && ! is_null($game->away_score))
                                    · <span class="font-semibold">{{ $game->home_score }}:{{ $game->away_score }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $game->competition?->name ?? 'bez rozgrywek' }}
                                · {{ $game->external_id ? 'z importu ze związku (nr '.$game->external_id.')' : 'dodany ręcznie' }}
                                · dodany {{ $game->created_at?->format('d.m.Y H:i') }}
                            </p>
                        </div>
                        <button type="button" wire:click="delete({{ $game->id }})" wire:confirm="Usunąć ten mecz? Trafi do kosza i będzie można go przywrócić." class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700">Usuń ten</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nie ma możliwych dubli. Każda drużyna gra najwyżej jeden mecz dziennie z danym rywalem.</p>
    @endforelse
</div>
