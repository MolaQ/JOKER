<div class="overflow-hidden rounded-lg bg-white shadow ring-1 ring-zinc-200">
    <div class="flex items-center justify-between gap-3 border-b p-4">
        <h3 class="text-lg font-semibold text-gray-900">{{ $showAll ? 'Ostatnie komentarze' : 'Czekają na akceptację' }}</h3>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" wire:model.live="showAll" class="rounded border-gray-300 text-sky-700"> Pokaż też opublikowane
        </label>
    </div>
    <ul class="divide-y divide-gray-200">
        @forelse($rows as $row)
            @php $comment = $row['comment']; @endphp
            <li class="flex flex-wrap items-start justify-between gap-3 p-4" wire:key="{{ $row['kind'] }}-{{ $comment->id }}">
                <div class="min-w-0 flex-1 text-sm">
                    <p class="text-gray-500">
                        <span class="font-semibold text-gray-900">{{ $comment->user?->name }}</span>
                        · {{ $comment->created_at->format('d.m.Y H:i') }}
                        · {{ $row['kind'] === 'article' ? 'aktualność' : 'mecz' }}:
                        @if($row['url'])<a href="{{ $row['url'] }}" target="_blank" class="text-sky-700 hover:underline">{{ $row['where'] }}</a>@else{{ $row['where'] }}@endif
                    </p>
                    <p class="mt-1 whitespace-pre-line text-gray-800">{{ $comment->content }}</p>
                </div>
                <div class="flex gap-2 text-sm">
                    @if($comment->is_approved)
                        <button type="button" wire:click="hide('{{ $row['kind'] }}', {{ $comment->id }})" class="rounded-md px-3 py-1.5 font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Ukryj</button>
                    @else
                        <button type="button" wire:click="approve('{{ $row['kind'] }}', {{ $comment->id }})" class="rounded-md bg-green-600 px-3 py-1.5 font-semibold text-white hover:bg-green-700">Opublikuj</button>
                    @endif
                    <button type="button" wire:click="delete('{{ $row['kind'] }}', {{ $comment->id }})" wire:confirm="Usunąć ten komentarz?" class="rounded-md px-3 py-1.5 font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50">Usuń</button>
                </div>
            </li>
        @empty
            <li class="p-6 text-center text-sm text-gray-500">Nie ma komentarzy do sprawdzenia.</li>
        @endforelse
    </ul>
</div>
