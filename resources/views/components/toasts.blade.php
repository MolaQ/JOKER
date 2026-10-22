{{-- Toasty: komunikaty z Livewire (zdarzenie „toast”), z JS (window.toast) i z sesji po przeładowaniu. --}}
<div
    x-data="toastStack"
    data-toast-stack
    data-toast-config="{{ json_encode($config, JSON_UNESCAPED_UNICODE) }}"
    data-toasts="{{ json_encode($initial, JSON_UNESCAPED_UNICODE) }}"
    x-on:toast.window="add($event.detail)"
    x-on:toast-config.window="configure($event.detail)"
    aria-live="polite"
    class="pointer-events-none fixed z-[100] flex w-[calc(100%-2rem)] max-w-sm gap-3"
    :class="positionClass"
    style="--toast-dir: {{ str_starts_with($config['position'], 'bottom') ? '1' : '-1' }}"
    :style="`--toast-dir: ${fromBottom ? 1 : -1}`"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition duration-300 ease-out"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-on:mouseenter="pause(toast)"
            x-on:mouseleave="resume(toast)"
            x-on:focusin="pause(toast)"
            x-on:focusout="resume(toast)"
            :role="toast.type === 'error' ? 'alert' : 'status'"
            :style="`background-color: ${toast.bg}; color: ${toast.text}`"
            class="pointer-events-auto relative w-full overflow-hidden rounded-2xl border-l-4 border-current shadow-xl shadow-black/10 ring-1 ring-black/5 backdrop-blur"
        >
            <div class="flex items-start gap-3 p-4 pr-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-current/10" aria-hidden="true">
                    <svg x-show="toast.type === 'success'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                    <svg x-show="toast.type === 'error'" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8v5m0 3.5h.01M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold leading-5" x-text="toast.title" x-show="toast.title"></p>
                    <p class="mt-0.5 text-sm leading-5 break-words whitespace-pre-line opacity-90" x-text="toast.message"></p>
                </div>
                <button type="button" x-on:click="dismiss(toast)" class="-m-1 shrink-0 rounded-lg p-1.5 opacity-60 transition hover:bg-current/10 hover:opacity-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-current" aria-label="{{ __('Zamknij') }}">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                </button>
            </div>
            <div class="absolute inset-x-0 bottom-0 h-1 bg-current/10" aria-hidden="true">
                <div
                    class="toast-progress h-full bg-current/50"
                    :style="`animation-duration: ${toast.duration}ms; animation-play-state: ${toast.paused || ! toast.visible ? 'paused' : 'running'}`"
                ></div>
            </div>
        </div>
    </template>
</div>

@once
    <style>
        .toast-enter-start { opacity: 0; transform: translateY(calc(var(--toast-dir, -1) * 0.75rem)) scale(0.96); }
        .toast-progress { transform-origin: left; animation-name: toast-progress; animation-timing-function: linear; animation-fill-mode: forwards; }
        @keyframes toast-progress { from { transform: scaleX(1); } to { transform: scaleX(0); } }
        @media (prefers-reduced-motion: reduce) {
            .toast-enter-start { transform: none; }
        }
    </style>
@endonce
