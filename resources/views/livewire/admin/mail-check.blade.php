<div class="space-y-3 p-4 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Poczta</h2>
            <p class="text-sm text-gray-600">
                Sposób wysyłki: <span class="font-semibold">{{ $mailer }}</span>@if ($host) · serwer <span class="font-semibold">{{ $host }}</span>@endif · nadawca <span class="font-semibold">{{ $from }}</span>.
                Ustawienia (hasło) zmienia się w pliku <code>.env</code> na serwerze.
            </p>
        </div>
        <button type="button" wire:click="sendTest" wire:loading.attr="disabled" class="admin-action">
            <span wire:loading.remove wire:target="sendTest">Wyślij testowy e-mail do mnie</span>
            <span wire:loading wire:target="sendTest">Wysyłam…</span>
        </button>
    </div>

    @if ($result)
        <p role="status" @class(['rounded-lg p-3 text-sm', 'bg-green-50 text-green-800 ring-1 ring-green-600/20' => $ok, 'bg-red-50 text-red-800 ring-1 ring-red-600/20' => ! $ok])>{{ $result }}</p>
    @endif

    <details class="text-sm">
        <summary class="cursor-pointer font-medium text-gray-700">Ostatnie błędy poczty ({{ count($errors) }})</summary>
        @forelse ($errors as $error)
            <p class="mt-2 text-gray-700"><span class="font-mono text-xs text-gray-500">{{ $error['time'] }}</span> {{ $error['message'] }}</p>
        @empty
            <p class="mt-2 text-gray-500">Brak błędów poczty w bieżącym logu.</p>
        @endforelse
    </details>
</div>
