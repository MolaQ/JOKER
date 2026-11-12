@php
    $busy = $flag || $locked;
    $megabytes = fn (int $bytes) => number_format($bytes / 1024 / 1024, 1, ',', ' ').' MB';
@endphp
<div class="max-w-3xl space-y-4" @if($busy) wire:poll.5s @endif>
    <div class="admin-card flex flex-wrap items-center justify-between gap-3 p-4">
        <p class="text-sm text-gray-700">Zainstalowana wersja: <strong class="text-gray-900 tabular-nums">{{ $installed ?? 'nieznana (paczka bez numeru)' }}</strong></p>
        @if($status && ! $busy)
            <p @class(['rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-50 text-emerald-700' => $status['stan'] === 'gotowe', 'bg-red-50 text-red-700' => $status['stan'] === 'blad', 'bg-zinc-100 text-zinc-700' => ! in_array($status['stan'], ['gotowe', 'blad'], true)])>{{ $status['opis'] }} · {{ $status['czas'] }}</p>
        @endif
    </div>

    @if($busy)
        {{-- Zatwierdzone: czekamy na cron albo instalacja właśnie trwa --}}
        <div class="admin-card space-y-3 p-4" wire:key="site-update-busy">
            <p class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                <span class="size-2.5 animate-pulse rounded-full bg-amber-500" aria-hidden="true"></span>
                @if($locked)
                    {{ ($flag['typ'] ?? '') === 'cofnij' ? 'Trwa przywracanie poprzedniej wersji…' : 'Trwa instalacja paczki…' }}
                @else
                    {{ ($flag['typ'] ?? '') === 'cofnij' ? 'Przywrócenie poprzedniej wersji czeka na cron' : 'Paczka '.($flag['wersja'] ?? '').' czeka na cron' }} (najbliższe uruchomienie za ok. {{ $cronIn }} min).
                @endif
            </p>
            <p class="text-xs text-gray-500">Zatwierdził: {{ $flag['kto'] ?? '—' }} · {{ $flag['czas'] ?? '' }}. Na czas podmiany plików strona pokazuje planszę „Przerwa techniczna”. Ta strona odświeża się sama.</p>
            @if(! $locked)
                <button type="button" wire:click="cancel" wire:confirm="Wycofać zatwierdzenie i usunąć wgraną paczkę?" class="admin-action admin-action-danger">Wycofaj i usuń paczkę</button>
            @endif
        </div>
    @elseif($package)
        {{-- Paczka wgrana i sprawdzona: decyzja administratora --}}
        <div class="admin-card space-y-4 p-4" wire:key="site-update-package">
            <h2 class="text-base font-semibold text-gray-900">Paczka gotowa do instalacji</h2>
            <dl class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-xs text-gray-500">Wersja paczki</dt><dd class="font-semibold text-gray-900 tabular-nums">{{ $package['wersja'] }}</dd></div>
                <div><dt class="text-xs text-gray-500">Rozmiar i liczba plików</dt><dd class="text-gray-900">{{ $megabytes($package['rozmiar']) }} · {{ number_format($package['pliki'], 0, ',', ' ') }} plików</dd></div>
                <div><dt class="text-xs text-gray-500">Wgrał</dt><dd class="text-gray-900">{{ $package['kto'] }} · {{ $package['wgrano'] }}</dd></div>
                <div class="min-w-0"><dt class="text-xs text-gray-500">Suma kontrolna (SHA-256)</dt><dd class="truncate font-mono text-xs text-gray-700" title="{{ $package['sha256'] }}">{{ $package['sha256'] }}</dd></div>
            </dl>

            @if($package['migracje'])
                <div class="rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
                    <p class="font-semibold">Paczka ma migracje bazy ({{ count($package['migracje']) }}). Instalator zrobi wcześniej kopię bazy.</p>
                    <ul class="mt-1 list-inside list-disc text-xs">@foreach($package['migracje'] as $migration)<li class="break-all">{{ $migration }}</li>@endforeach</ul>
                </div>
            @else
                <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 ring-1 ring-emerald-200">Paczka nie ma nowych migracji bazy.</p>
            @endif

            @if($package['starsza'])
                <label class="flex items-start gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200">
                    <input id="site-update-older" type="checkbox" wire:model="allowOlder" class="mt-0.5 rounded border-red-300 text-red-600">
                    <span><strong>Ta paczka jest starsza niż zainstalowana ({{ $installed }}).</strong> Zaznacz, jeśli naprawdę chcesz ją wgrać.</span>
                </label>
                @error('allowOlder')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
            @endif

            <form wire:submit="install" class="flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4">
                <label class="min-w-0 flex-1 text-xs font-medium text-gray-600" for="site-update-password">Potwierdź swoim hasłem
                    <input id="site-update-password" type="password" wire:model="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs focus:border-sky-500 focus:ring-sky-500">
                </label>
                <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700" wire:loading.attr="disabled">Zainstaluj</button>
                <button type="button" wire:click="cancel" wire:confirm="Usunąć wgraną paczkę?" class="admin-action admin-action-danger">Usuń paczkę</button>
            </form>
            @error('password')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @else
        {{-- Wgrywanie w kawałkach: omija limit wielkości pliku na hostingu, a każdy kawałek ponawia się przy błędzie sieci --}}
        <div class="admin-card space-y-3 p-4" wire:key="site-update-upload"
             x-data="{ busy: false, done: 0, total: 0, error: '', name: '',
                 async send(file) {
                     if (! file) { return; }
                     if (! /\.zip$/i.test(file.name)) { this.error = 'Wybierz plik joker-kod.zip.'; return; }
                     this.busy = true; this.error = ''; this.name = file.name;
                     const size = {{ $chunkBytes }}; const upload = Math.random().toString(36).slice(2, 12) + Date.now().toString(36);
                     this.total = Math.max(1, Math.ceil(file.size / size)); this.done = 0;
                     for (let index = 0; index < this.total; index++) {
                         const body = new FormData();
                         body.append('upload', upload); body.append('index', index); body.append('total', this.total);
                         body.append('chunk', file.slice(index * size, (index + 1) * size), 'czesc.bin');
                         let ok = false; let message = 'Połączenie przerwane. Spróbuj ponownie.';
                         for (let attempt = 0; attempt < 4 && ! ok; attempt++) {
                             try {
                                 const response = await fetch(@js(route('admin.site-update.chunk')), { method: 'POST', body, credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' } });
                                 if (response.ok) { ok = true; break; }
                                 if (response.status === 422 || response.status === 403 || response.status === 419) { message = (await response.json().catch(() => ({}))).message ?? 'Serwer odrzucił plik (kod ' + response.status + '). Odśwież stronę i spróbuj ponownie.'; break; }
                             } catch (error) {}
                             await new Promise((resolve) => setTimeout(resolve, 1500 * (attempt + 1)));
                         }
                         if (! ok) { this.error = message; this.busy = false; return; }
                         this.done = index + 1;
                     }
                     $wire.$refresh(); this.busy = false;
                 } }">
            <h2 class="text-base font-semibold text-gray-900">Wgraj paczkę</h2>
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-zinc-300 px-4 py-8 text-center transition hover:border-sky-500 hover:bg-sky-50" :class="busy && 'pointer-events-none opacity-60'" for="site-update-file"
                   x-on:dragover.prevent x-on:drop.prevent="send($event.dataTransfer.files[0])">
                <x-admin-icon name="upload" class="size-8 text-zinc-400" />
                <span class="text-sm font-semibold text-gray-900">Wybierz albo przeciągnij tu plik joker-kod.zip</span>
                <span class="text-xs text-gray-500">Samo wgranie niczego nie zmienia na stronie. Instalację zatwierdzasz w następnym kroku.</span>
                <input id="site-update-file" type="file" accept=".zip" class="sr-only" x-on:change="send($event.target.files[0]); $event.target.value = ''">
            </label>
            <div x-show="busy || done" x-cloak>
                <div class="flex justify-between text-xs text-gray-600"><span class="truncate" x-text="name"></span><span class="tabular-nums" x-text="Math.round(done * 100 / Math.max(1, total)) + '%'"></span></div>
                <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-100"><div class="h-full rounded-full bg-sky-500 transition-all" :style="'width:' + (done * 100 / Math.max(1, total)) + '%'"></div></div>
            </div>
            <p x-show="error" x-cloak class="rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200" x-text="error" role="alert"></p>
        </div>

        @if($canRollback)
            <details class="admin-card p-4 text-sm">
                <summary class="cursor-pointer font-semibold text-gray-900">Przywróć poprzednią wersję kodu</summary>
                <p class="mt-2 text-gray-600">Przed każdą aktualizacją z panelu instalator zachowuje poprzednią wersję plików strony. Przywrócenie cofa tylko kod; baza danych zostaje taka, jaka jest.</p>
                <form wire:submit="rollback" class="mt-3 flex flex-wrap items-end gap-3">
                    <label class="min-w-0 flex-1 text-xs font-medium text-gray-600" for="site-rollback-password">Potwierdź swoim hasłem
                        <input id="site-rollback-password" type="password" wire:model="password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs focus:border-sky-500 focus:ring-sky-500">
                    </label>
                    <button type="submit" class="admin-action admin-action-danger">Przywróć poprzednią wersję</button>
                </form>
                @error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </details>
        @endif
    @endif

    @if($log !== '')
        <details class="admin-card p-4" @if($busy || ($status['stan'] ?? '') === 'blad') open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-gray-900">Log instalatora (ostatnie linie)</summary>
            <pre class="mt-3 max-h-80 overflow-auto rounded-lg bg-zinc-900 p-3 text-xs leading-relaxed whitespace-pre-wrap text-zinc-100">{{ $log }}</pre>
        </details>
    @endif
</div>
