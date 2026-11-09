<div class="space-y-4">
    @forelse($groups as $key => $group)
        <div wire:key="dubel-zawodnika-{{ $key }}" class="admin-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-amber-700 uppercase">⚠ Możliwy dubel</p>
                    <p class="mt-1 font-semibold text-gray-900">{{ $group->first()->full_name }}</p>
                    <p class="text-sm text-gray-500">{{ $group->count() }} wpisy z tym imieniem i nazwiskiem. Wybierz profil, który zostaje.</p>
                </div>
                <button type="button" wire:click="dismiss('{{ $key }}')" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">To różne osoby</button>
            </div>
            <ul class="mt-3 divide-y divide-gray-100 rounded-lg ring-1 ring-gray-200">
                @foreach($group as $player)
                    <li wire:key="dubel-zawodnik-{{ $player->id }}" class="flex flex-wrap items-center justify-between gap-3 px-3 py-2.5 text-sm">
                        <div class="min-w-0">
                            <p class="text-gray-900">
                                <span class="font-semibold">{{ $player->team?->name ?? 'bez drużyny' }}</span>@if($player->jersey_number) · #{{ $player->jersey_number }}@endif
                                @if($player->birth_date) · ur. {{ $player->birth_date->format('d.m.Y') }}@elseif($player->birth_year) · rocznik {{ $player->birth_year }}@endif
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ $player->user ? 'konto: '.$player->user->email : 'bez konta' }}
                                · {{ trans_choice(':count nagroda|:count nagrody|:count nagród', $player->awards->count()) }}
                                · {{ $player->views_count }} wyświetleń
                                · dodany {{ $player->created_at?->format('d.m.Y') }}
                                · <a href="{{ route('players.show', $player) }}" target="_blank" class="text-sky-700 hover:underline">profil</a>
                            </p>
                        </div>
                        <button type="button" wire:click="keep('{{ $key }}', {{ $player->id }})" wire:confirm="Zostawić profil {{ $player->full_name }} ({{ $player->team?->name ?? 'bez drużyny' }})? Pozostałe wpisy zostaną z nim scalone i trafią do kosza." class="rounded-md bg-amber-500 px-3 py-1.5 text-sm font-semibold text-white hover:bg-amber-600">Zostaw ten</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nie ma możliwych dubli. Każdy zawodnik jest wpisany raz.</p>
    @endforelse
</div>
