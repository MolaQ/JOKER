<div class="space-y-6">
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="admin-card p-5 lg:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="flex items-center gap-2 text-base font-semibold text-zinc-900"><x-admin-icon name="list" class="h-5 w-5 text-sky-600" /> Mapa strony (sitemap.xml)</h2>
                    <p class="mt-1 text-sm text-zinc-500">Lista wszystkich publicznych adresów dla Google i Bing. Każda zmiana treści ją odświeża; przycisk buduje ją od razu.</p>
                </div>
                <button type="button" wire:click="rebuild" class="inline-flex items-center gap-1.5 rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    <x-admin-icon name="refresh" class="h-4 w-4" wire:loading.class="animate-spin" wire:target="rebuild" /> Zbuduj teraz
                </button>
            </div>
            <dl class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg bg-zinc-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Adresów</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums text-zinc-900">{{ $meta['count'] ?? '—' }}</dd>
                </div>
                <div class="rounded-lg bg-zinc-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Zbudowana</dt>
                    <dd class="mt-1 text-sm font-medium text-zinc-900">{{ $builtAt ? $builtAt->format('d.m.Y H:i') : 'jeszcze nie' }}@if(! empty($meta['by']))<span class="block text-xs font-normal text-zinc-500">przez {{ $meta['by'] }}</span>@endif</dd>
                </div>
                <div class="rounded-lg bg-zinc-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Stan</dt>
                    <dd class="mt-1 text-sm font-medium">
                        @if(! $meta)
                            <span class="text-zinc-600">zbuduje się przy pierwszym wejściu robota</span>
                        @elseif($meta['stale'] ?? false)
                            <span class="text-amber-700">treści się zmieniły, odświeży się przy następnym wejściu robota</span>
                        @else
                            <span class="text-emerald-700">aktualna</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                <a href="{{ route('sitemap') }}" target="_blank" class="inline-flex items-center gap-1 font-semibold text-sky-700 hover:text-sky-900">Otwórz sitemap.xml <x-admin-icon name="external" class="h-3.5 w-3.5" /></a>
                <a href="https://search.google.com/search-console/sitemaps" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-sky-700 hover:text-sky-900">Zgłoś mapę w Google Search Console <x-admin-icon name="external" class="h-3.5 w-3.5" /></a>
            </div>
            <p class="mt-3 text-xs text-zinc-500">W mapie: {{ collect($counts)->map(fn ($n, $label) => $label.' '.$n)->join(', ') }}, a także mecze z dwóch ostatnich sezonów, turnieje i strony sztabu.</p>
        </section>

        <section class="admin-card p-5">
            <h2 class="flex items-center gap-2 text-base font-semibold text-zinc-900"><x-admin-icon name="shield" class="h-5 w-5 text-sky-600" /> robots.txt</h2>
            @if($staticRobots !== null)
                <p class="mt-2 rounded-md bg-red-50 p-3 text-sm text-red-800">Na serwerze leży plik <code>public/robots.txt</code> i to on jest wysyłany do Google. Usuń go, żeby działał ten poniżej.</p>
                <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-900 p-3 text-xs text-zinc-300">{{ $staticRobots }}</pre>
                <p class="mt-3 text-xs font-semibold text-zinc-500">Po usunięciu pliku:</p>
            @endif
            <pre class="mt-2 overflow-x-auto rounded-lg bg-zinc-50 p-3 text-xs text-zinc-700 ring-1 ring-zinc-200">{{ $robots }}</pre>
        </section>
    </div>

    <section class="admin-card">
        <h2 class="border-b border-zinc-200 px-5 py-4 text-base font-semibold text-zinc-900">Kontrola strony</h2>
        <ul class="divide-y divide-zinc-100">
            @foreach($checks as [$level, $title, $text, $link])
                <li class="flex items-start gap-3 px-5 py-3">
                    <span @class(['mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full', 'bg-emerald-100 text-emerald-700' => $level === 'ok', 'bg-amber-100 text-amber-700' => $level === 'warn', 'bg-red-100 text-red-700' => $level === 'bad'])>
                        <x-admin-icon :name="$level === 'ok' ? 'check' : ($level === 'warn' ? 'eye' : 'close')" class="h-3.5 w-3.5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-zinc-900">{{ $title }}</p>
                        <p class="text-sm text-zinc-600">{{ $text }}</p>
                    </div>
                    @if($link)
                        <a href="{{ $link }}" @if(str_starts_with($link, 'http') && ! str_starts_with($link, url('/'))) target="_blank" rel="noopener" @endif class="shrink-0 rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-sky-700 ring-1 ring-sky-200 hover:bg-sky-50">{{ $level === 'ok' ? 'Zobacz' : 'Popraw' }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    <section class="admin-card p-5">
        <h2 class="text-base font-semibold text-zinc-900">Co strona robi sama</h2>
        <ul class="mt-3 grid gap-x-6 gap-y-2 text-sm text-zinc-600 sm:grid-cols-2">
            <li>Tytuł i opis każdej podstrony, adres kanoniczny (bez dubli z parametrami).</li>
            <li>Podgląd przy udostępnianiu (Open Graph, Twitter): tytuł, opis, zdjęcie.</li>
            <li>Dane strukturalne schema.org: klub, artykuły, mecze, drużyny, zawodnicy, galerie, okruszki.</li>
            <li>Polskie, czytelne adresy (np. /aktualnosci/tytul-artykulu, /zawodnicy/1-imie-nazwisko).</li>
            <li>Zdjęcia z opisem alt, wymiarami i leniwym ładowaniem (szybsze wczytywanie na telefonie).</li>
            <li>Strona błędu 404 z linkami do najważniejszych sekcji, panel i logowanie wyłączone z indeksowania.</li>
        </ul>
        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
            <a href="https://pagespeed.web.dev/analysis?url={{ urlencode(url('/')) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-sky-700 hover:text-sky-900">Test szybkości (PageSpeed) <x-admin-icon name="external" class="h-3.5 w-3.5" /></a>
            <a href="https://search.google.com/test/rich-results?url={{ urlencode(url('/')) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-sky-700 hover:text-sky-900">Test wyników z elementami rozszerzonymi <x-admin-icon name="external" class="h-3.5 w-3.5" /></a>
        </div>
    </section>
</div>
