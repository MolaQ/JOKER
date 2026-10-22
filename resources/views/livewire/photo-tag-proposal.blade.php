<div>
    @if($photo)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-ink/70 p-4" role="dialog" aria-modal="true" wire:keydown.escape.window="close">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-4">
                    <img src="{{ $photo->url() }}" alt="" class="h-20 w-28 flex-none rounded-lg object-cover">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900">{{ __('Kto jest na zdjęciu?') }}</h2>
                        <p class="text-sm text-zinc-500">{{ __('Oznaczenie pojawi się po zatwierdzeniu przez klub.') }}</p>
                    </div>
                </div>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="{{ __('Szukaj zawodnika po nazwisku...') }}" class="mt-4 block w-full rounded-md border border-zinc-300 px-3 py-2" autofocus>
                <ul class="mt-3 max-h-64 divide-y divide-zinc-100 overflow-y-auto">
                    @forelse($players as $player)
                        <li wire:key="tag-player-{{ $player->id }}">
                            <button type="button" wire:click="propose({{ $player->id }})" class="flex w-full items-center justify-between px-2 py-2 text-left text-sm hover:bg-sky-club/10">
                                <span class="font-medium text-zinc-900">{{ $player->full_name }}</span>
                                <span class="text-zinc-500">{{ $player->team?->name }}</span>
                            </button>
                        </li>
                    @empty
                        <li class="px-2 py-2 text-sm text-zinc-500">{{ __('Wpisz co najmniej 2 litery nazwiska.') }}</li>
                    @endforelse
                </ul>
                <button type="button" wire:click="close" class="mt-4 w-full rounded-lg bg-zinc-100 py-2 text-sm font-semibold text-zinc-800 hover:bg-zinc-200">{{ __('Anuluj') }}</button>
            </div>
        </div>
    @endif
</div>
