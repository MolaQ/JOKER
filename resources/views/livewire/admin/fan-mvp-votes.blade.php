<div class="space-y-3">
    @forelse($matches as $match)
        <div wire:key="mvp-{{ $match['key'] }}" class="admin-card">
            <button type="button" wire:click="toggle('{{ $match['key'] }}')" class="flex w-full flex-wrap items-center justify-between gap-3 px-4 py-3 text-left">
                <span class="min-w-0">
                    <span class="block font-semibold text-gray-900">{{ $match['label'] }}</span>
                    <span class="block text-sm text-gray-500">
                        Głosy: {{ $match['total'] }}
                        @if($match['leader'] && $match['leader']['votes'] > 0)
                            · prowadzi {{ $match['leader']['player']->full_name }} ({{ $match['leader']['votes'] }})
                        @endif
                    </span>
                </span>
                <span class="text-sm font-semibold text-sky-700">{{ $open === $match['key'] ? 'Zwiń ▴' : 'Kto na kogo ▾' }}</span>
            </button>
            @if($open === $match['key'])
                <div class="border-t border-gray-100 px-4 py-3">
                    <ul class="divide-y divide-gray-100 text-sm">
                        @foreach($match['ballots'] as $ballot)
                            <li class="flex flex-wrap items-baseline justify-between gap-x-3 py-1.5">
                                <span class="min-w-0 break-words">{{ $ballot['voter'] }} → <strong>{{ $ballot['player'] }}</strong>@unless($ballot['counted']) <span class="text-xs text-gray-500">(nie liczy się)</span>@endunless</span>
                                <span class="text-xs text-gray-500 tabular-nums">{{ $ballot['at']?->format('d.m H:i') }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($match['url'])<a href="{{ $match['url'] }}" class="mt-2 inline-block text-sm font-semibold text-sky-700 hover:underline">Strona meczu</a>@endif
                </div>
            @endif
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nikt jeszcze nie głosował w Kibicowskim MVP.</p>
    @endforelse

    {{ $page->links() }}
</div>
