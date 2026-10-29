@props(['to' => null, 'player' => null])
{{-- Koperta przy nazwisku: otwiera nową wiadomość do tej osoby. Widzą ją tylko zalogowani, którzy mogą pisać.
     Zawodnik bez własnego konta: wiadomość trafia do jego rodziców. --}}
@php
    $me = auth()->user();
    $recipients = collect();

    if ($me?->canMessage()) {
        $recipients = match (true) {
            $to !== null => collect([$to]),
            $player?->user !== null => collect([$player->user]),
            $player !== null => $player->guardians,
            default => collect(),
        };
        $recipients = $recipients->filter(fn ($user) => $user->accountStatus() === \App\AccountStatus::Active && $user->id !== $me->id);
    }
@endphp
@if($recipients->isNotEmpty())
    @php($title = $player && ! $player->user ? __('Napisz do rodziców') : __('Napisz wiadomość'))
    <a href="{{ route('messages.index', ['do' => $recipients->pluck('id')->implode(',')]) }}" title="{{ $title }}" aria-label="{{ $title }}"
        {{ $attributes->class('inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/10 align-middle text-white ring-1 ring-white/15 transition hover:scale-110 hover:bg-sky-club') }}>
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6"/></svg>
    </a>
@endif
