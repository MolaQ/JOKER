<button
    type="button"
    wire:click="toggle"
    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-sm font-medium transition {{ $liked ? 'bg-pink-100 text-pink-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
>
    <span>{{ $liked ? '❤️' : '🤍' }}</span>
    <span>{{ $likesCount }}</span>
</button>
