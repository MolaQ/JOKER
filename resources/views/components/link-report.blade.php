@props(['target', 'url', 'dark' => false])
{{-- Dyskretna ikonka „link nie działa”: tylko dla zalogowanych, zgłoszenie trafia do „Do zatwierdzenia → Niedziałające linki” --}}
@auth
    <button type="button" {{ $attributes->merge(['class' => 'inline-flex size-7 shrink-0 items-center justify-center rounded-full align-middle text-xs transition disabled:cursor-default '.($dark ? 'text-white/50 hover:bg-white/10 hover:text-white' : 'text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700')]) }}
            x-data="{ sent: false }" :disabled="sent"
            x-on:click="if (sent || ! confirm(@js(__('Zgłosić administratorowi, że ten link nie działa?')))) return;
                fetch(@js(route('links.report')), { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token()) }, body: JSON.stringify({ key: @js($target), url: @js($url) }) })
                    .then((r) => r.json()).then((d) => { sent = true; window.toast?.(d.message) }).catch(() => window.toast?.(@js(__('Nie udało się wysłać zgłoszenia.')), 'error'))"
            title="{{ __('Link nie działa? Zgłoś to') }}" aria-label="{{ __('Zgłoś niedziałający link') }}">
        <svg x-show="! sent" class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 2.25a.75.75 0 0 1 .75.75v.54l1.84-.46a5.25 5.25 0 0 1 3.62.39l.13.06a3.75 3.75 0 0 0 2.59.28l2.68-.67A.75.75 0 0 1 15.55 3.9v8.6a.75.75 0 0 1-.57.73l-2.98.75a5.25 5.25 0 0 1-3.62-.39l-.13-.06a3.75 3.75 0 0 0-2.59-.28l-1.91.48v4.27a.75.75 0 0 1-1.5 0V3A.75.75 0 0 1 3 2.25Z" clip-rule="evenodd"/></svg>
        <span x-cloak x-show="sent" aria-hidden="true">✓</span>
    </button>
@endauth
