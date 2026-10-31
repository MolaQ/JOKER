<div class="space-y-3">
    @forelse($messages as $message)
        <div wire:key="reported-{{ $message->id }}" class="admin-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm text-gray-500">
                        <span class="font-semibold text-gray-900">{{ $message->author?->name ?? 'Konto usunięte' }}</span>
                        w rozmowie „{{ $message->conversation->titleFor(auth()->user()) }}” · {{ $message->created_at->format('d.m.Y H:i') }}
                    </p>
                    <p class="mt-2 rounded-lg bg-gray-50 p-3 text-sm whitespace-pre-line text-gray-800">{{ $message->body }}</p>
                    <p class="mt-2 text-xs text-gray-500">Zgłosił(a): {{ $message->reporter?->name ?? '—' }}, {{ $message->reported_at->format('d.m.Y H:i') }}</p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="hide({{ $message->id }})" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700">Ukryj</button>
                    <button type="button" wire:click="dismiss({{ $message->id }})" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50">Zostaw</button>
                </div>
            </div>
        </div>
    @empty
        <p class="admin-card p-6 text-sm text-gray-500">Nie ma zgłoszonych wiadomości.</p>
    @endforelse
</div>
