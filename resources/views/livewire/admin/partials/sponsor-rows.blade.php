<ul class="divide-y divide-gray-100">
    @forelse($sponsors as $sponsor)
        <li wire:key="sponsor-{{ $sponsor->id }}" class="flex flex-wrap items-center gap-3 px-3 py-2">
            <div class="flex flex-col">
                <button type="button" wire:click="move({{ $sponsor->id }}, -1)" @disabled($loop->first) class="px-1 text-xs text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Wyżej">▲</button>
                <button type="button" wire:click="move({{ $sponsor->id }}, 1)" @disabled($loop->last) class="px-1 text-xs text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Niżej">▼</button>
            </div>
            <span class="flex h-12 w-24 shrink-0 items-center justify-center rounded-md p-1 ring-1 ring-gray-200" style="{{ $sponsor->tile_color ? 'background: '.$sponsor->tile_color : '' }}">
                @if($sponsor->logo_path)<img src="{{ \App\Support\Media::url($sponsor->logo_path) }}" alt="" class="max-h-full max-w-full object-contain">@endif
            </span>
            <div class="min-w-0 flex-1">
                <p @class(['text-sm font-semibold', 'text-gray-900' => $sponsor->is_active, 'text-gray-400 line-through' => ! $sponsor->is_active])>{{ $sponsor->name }}</p>
                <p class="truncate text-xs text-gray-500">{{ $sponsor->website_url ?: 'bez linku' }}@unless($sponsor->logo_path) · brak logo (nie pokaże się w bloku)@endunless</p>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" wire:click="toggleActive({{ $sponsor->id }})" class="admin-action">{{ $sponsor->is_active ? 'Ukryj' : 'Pokaż' }}</button>
                <button type="button" wire:click="openEditModal({{ $sponsor->id }})" class="admin-action">Edytuj</button>
                <button type="button" wire:click="delete({{ $sponsor->id }})" wire:confirm="Usunąć „{{ $sponsor->name }}”? Zniknie też z partnerów turniejów." class="admin-action admin-action-danger">Usuń</button>
            </div>
        </li>
    @empty
        <li class="px-4 py-4 text-center text-sm text-gray-500">Brak logotypów w tej kategorii.</li>
    @endforelse
</ul>
