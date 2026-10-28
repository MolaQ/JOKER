@props(['dark' => true])
{{-- Ikona „Wiadomości” z liczbą nieprzeczytanych (tylko dla zalogowanych) --}}
@auth
    @php $unread = auth()->user()->unreadMessagesCount(); @endphp
    <a href="{{ route('messages.index') }}" {{ $attributes->merge(['class' => 'relative flex h-11 w-11 items-center justify-center rounded-full transition '.($dark ? 'text-zinc-300 hover:bg-white/10 hover:text-white' : 'text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900')]) }} title="{{ __('Wiadomości') }}{{ $unread ? ': '.$unread.' '.__('nieprzeczytane') : '' }}" aria-label="{{ __('Wiadomości') }}{{ $unread ? ': '.$unread.' '.__('nieprzeczytane') : '' }}">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-9 6 2.5-3H18a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v7a3 3 0 0 0 1 2.2V20Z"/></svg>
        @if($unread > 0)
            <span class="absolute top-1 right-1 flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] leading-none font-bold text-white tabular-nums">{{ $unread > 99 ? '99+' : $unread }}</span>
        @endif
    </a>
@endauth
