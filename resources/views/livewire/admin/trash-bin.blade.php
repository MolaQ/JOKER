<div class="space-y-4">
    <div class="admin-card grid gap-3 p-4 sm:grid-cols-2">
        <div>
            <label class="block text-xs font-medium text-gray-600" for="trash-search">Szukaj</label>
            <input id="trash-search" type="search" wire:model.live.debounce.400ms="search" placeholder="np. Kowalski, Memoriał…" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs focus:border-sky-500 focus:ring-sky-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600" for="trash-type">Rodzaj</label>
            <select id="trash-type" wire:model.live="type" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs">
                <option value="">— wszystko —</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <ul class="admin-card divide-y divide-gray-100">
        @forelse($items as $item)
            @php $left = (int) ceil(now()->diffInDays($item->expiresAt(), false)); @endphp
            <li wire:key="trash-{{ $item->id }}" class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <p class="text-sm text-gray-900">
                        <span class="mr-1 inline-block rounded bg-red-50 px-1.5 py-0.5 text-[11px] font-semibold text-red-700">{{ \App\Support\Trash::label($item->type) }}</span>
                        <span class="font-medium">{{ $item->label }}</span>
                    </p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Usunął: {{ $item->deleter?->name ?? 'nieznany' }} · {{ $item->created_at->format('d.m.Y H:i') }}
                        @if($item->rowCount() > 1) · wróci z {{ $item->rowCount() - 1 }} {{ trans_choice('{1} powiązanym wpisem|[2,*] powiązanymi wpisami', $item->rowCount() - 1) }}@endif
                        · <span @class(['font-semibold text-amber-700' => $left <= 3])>{{ $left > 0 ? trans_choice('{1} zniknie za :count dzień|[2,*] zniknie za :count dni', $left, ['count' => $left]) : 'zniknie dziś w nocy' }}</span>
                    </p>
                </div>
                <div class="flex shrink-0 gap-2">
                    <button type="button" wire:click="restore({{ $item->id }})" class="rounded-lg bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-700">Przywróć</button>
                    <button type="button" wire:click="purge({{ $item->id }})" wire:confirm="Usunąć „{{ $item->label }}” na stałe? Tego nie da się cofnąć." class="rounded-lg px-3 py-1.5 text-sm font-semibold text-red-700 ring-1 ring-red-200 hover:bg-red-50">Usuń na stałe</button>
                </div>
            </li>
        @empty
            <li class="px-4 py-10 text-center text-sm text-gray-500">Kosz jest pusty.</li>
        @endforelse
    </ul>

    {{ $items->links() }}
</div>
