<div class="inline-flex">
    @php
        $tone = $liked
            ? 'bg-pink-100 text-pink-700'
            : ($dark ? 'bg-white/10 text-white ring-1 ring-white/20' : 'bg-zinc-100 text-zinc-700');
    @endphp
    @auth
        <button
            type="button"
            wire:click="toggle"
            aria-pressed="{{ $liked ? 'true' : 'false' }}"
            title="{{ $liked ? __('Cofnij polubienie') : __('Polub') }}"
            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium transition {{ $tone }} {{ $liked ? '' : ($dark ? 'hover:bg-white/20' : 'hover:bg-zinc-200') }}"
        >
            <span aria-hidden="true">{{ $liked ? '❤️' : '🤍' }}</span>
            <span class="tabular-nums">{{ $likesCount }}</span>
            <span class="sr-only">{{ __('Polubienia') }}</span>
        </button>
    @else
        <span
            title="{{ __('Zaloguj się, aby polubić') }}"
            class="inline-flex cursor-default items-center gap-1.5 rounded-full px-3 py-1 text-sm font-medium {{ $tone }}"
        >
            <span aria-hidden="true">❤️</span>
            <span class="tabular-nums">{{ $likesCount }}</span>
            <span class="sr-only">{{ __('Polubienia') }} · {{ __('Zaloguj się, aby polubić') }}</span>
        </span>
    @endauth
</div>
