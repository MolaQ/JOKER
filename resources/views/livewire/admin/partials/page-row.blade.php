<li @class(['flex flex-wrap items-center gap-3 py-3 pr-4 sm:pr-6', 'pl-10 sm:pl-14 bg-gray-50/60' => $child, 'pl-4 sm:pl-6' => ! $child]) wire:key="page-{{ $page->id }}">
    <div class="flex flex-col">
        <button type="button" wire:click="move({{ $page->id }}, -1)" @disabled($first) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Wyżej">▲</button>
        <button type="button" wire:click="move({{ $page->id }}, 1)" @disabled($last) class="px-1 text-gray-400 hover:text-gray-900 disabled:opacity-25" aria-label="Niżej">▼</button>
    </div>
    <div class="min-w-0 flex-1">
        <p class="font-semibold text-gray-900">
            @if($child)<span class="text-gray-400" aria-hidden="true">↳</span>@endif
            @if($page->theme_accent)<span class="mr-1 inline-block h-2.5 w-2.5 rounded-full align-middle" style="background: {{ $page->theme_accent }}"></span>@endif
            {{ $page->title }}
            @if($page->hasEnglish())
                <x-english-badge class="ml-1" />
            @endif
            @unless($page->is_published)
                <span class="ml-2 rounded-md bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">Projekt (ukryta)</span>
            @endunless
            @if($page->footer_link)
                <span class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">w stopce</span>
            @endif
            @unless($page->in_menu)
                <span class="ml-1 rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">poza menu</span>
            @endunless
        </p>
        <a href="{{ route('pages.show', $page) }}" target="_blank" class="font-mono text-xs text-sky-700 hover:underline">/strona/{{ $page->slug }} ↗</a>
    </div>
    <div class="flex flex-wrap gap-1.5">
        <button type="button" wire:click="togglePublished({{ $page->id }})" @if(! $page->is_published) wire:confirm="Opublikować „{{ $page->title }}”? Zobaczą ją wszyscy odwiedzający." @endif class="admin-action">{{ $page->is_published ? 'Ukryj' : 'Opublikuj' }}</button>
        <button type="button" wire:click="openEditModal({{ $page->id }})" class="admin-action">Edytuj</button>
        <button type="button" wire:click="delete({{ $page->id }})" wire:confirm="Usunąć podstronę „{{ $page->title }}”? Linki do niej w menu przestaną działać." class="admin-action admin-action-danger">Usuń</button>
    </div>
</li>
