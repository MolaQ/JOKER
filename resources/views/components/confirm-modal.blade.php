{{-- Okno potwierdzenia dla wszystkich wire:confirm="..." (obsługa: resources/js/confirm-modal.js). --}}
<div
    x-data="confirmModal"
    data-confirm-modal
    data-title="{{ __('Czy na pewno?') }}"
    data-title-danger="{{ __('Potwierdź usunięcie') }}"
    data-label="{{ __('Potwierdź') }}"
    data-label-danger="{{ __('Usuń') }}"
    data-fallback="{{ __('Czy na pewno?') }}"
    x-show="open"
    x-cloak
    x-on:keydown.escape.window="cancel()"
    class="fixed inset-0 z-[110] flex items-end justify-center p-4 sm:items-center"
    role="dialog"
    aria-modal="true"
    aria-labelledby="confirm-modal-title"
    aria-describedby="confirm-modal-message"
>
    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="cancel()"
        class="absolute inset-0 bg-zinc-950/60 backdrop-blur-sm"
        aria-hidden="true"
    ></div>

    <div
        x-show="open"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        x-transition:leave="transition duration-150 ease-in"
        x-transition:leave-start="opacity-100 sm:scale-100"
        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-on:keydown.tab="trapFocus($event)"
        class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white text-left shadow-2xl ring-1 ring-black/5"
    >
        <div class="flex gap-4 p-6">
            <span
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                :class="danger ? 'bg-red-100 text-red-600' : 'bg-sky-100 text-sky-600'"
                aria-hidden="true"
            >
                <svg x-show="danger" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"/></svg>
                <svg x-show="! danger" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9.1 9a3 3 0 0 1 5.8 1c0 2-3 3-3 3M12 17h.01M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
            </span>
            <div class="min-w-0 pt-1">
                <h2 id="confirm-modal-title" class="text-lg font-bold text-zinc-900" x-text="title"></h2>
                <p id="confirm-modal-message" class="mt-2 text-sm leading-6 text-zinc-600 break-words whitespace-pre-line" x-text="message"></p>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-2 bg-zinc-50 px-6 py-4 sm:flex-row sm:justify-end">
            <button
                type="button"
                x-ref="cancel"
                x-on:click="cancel()"
                class="inline-flex justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-300 transition hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
            >{{ __('Anuluj') }}</button>
            <button
                type="button"
                x-ref="accept"
                x-on:click="accept()"
                x-text="confirmLabel"
                :class="danger ? 'bg-red-600 hover:bg-red-700 focus-visible:ring-red-500' : 'bg-sky-600 hover:bg-sky-700 focus-visible:ring-sky-500'"
                class="inline-flex justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            ></button>
        </div>
    </div>
</div>
