<div>
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 p-4 bg-white border-b border-gray-200">
        <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Szukaj artykułu..."
                class="w-full sm:w-64 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm"
            >
            <select wire:model.live="statusFilter" class="w-full sm:w-48 rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm">
                <option value="">Wszystkie statusy</option>
                <option value="draft">Szkic</option>
                <option value="published">Opublikowany</option>
                <option value="archived">Zarchiwizowany</option>
            </select>
        </div>
        <button type="button" wire:click="openCreateModal" class="inline-flex items-center justify-center rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
            + Dodaj artykuł
        </button>
    </div>

    <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
            <tr>
                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Tytuł</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Autor</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Status</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Odbiór</th>
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Multimedia</th>
                <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Akcje</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
            @forelse($articles as $article)
                <tr wire:key="article-{{ $article->id }}">
                    <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 max-w-xs truncate">
                        {{ $article->title }}
                        @if($article->hasEnglish())
                            <x-english-badge class="ml-1" />
                        @endif
                        @if($article->is_featured)
                            <span class="ml-2 inline-flex items-center rounded-md bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Wyróżniony</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $article->author->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php [$statusLabel, $statusClass] = $article->statusBadge(); @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        <span class="flex items-center gap-3 tabular-nums">
                            <span class="inline-flex items-center gap-1" title="Wyświetlenia">
                                <svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.57 3 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.57-3-9.96-7.18Z"/><circle cx="12" cy="12" r="3"/></svg>
                                {{ number_format($article->views_count, 0, ',', ' ') }}<span class="sr-only"> wyświetleń</span>
                            </span>
                            <span @class(['inline-flex items-center gap-1', 'text-rose-600' => $article->likes_count > 0]) title="Polubienia">
                                <svg class="size-4 {{ $article->likes_count > 0 ? 'text-rose-500' : 'text-zinc-400' }}" fill="{{ $article->likes_count > 0 ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linejoin="round" d="M12 20s-7.5-4.6-9.6-9.3C.9 7.2 3 3.5 6.6 3.5c2.1 0 3.6 1.2 5.4 3.2 1.8-2 3.3-3.2 5.4-3.2 3.6 0 5.7 3.7 4.2 7.2C19.5 15.4 12 20 12 20Z"/></svg>
                                {{ $article->likes_count }}<span class="sr-only"> polubień</span>
                            </span>
                            <a href="{{ route('admin.comments.index') }}" class="inline-flex items-center gap-1 hover:text-zinc-900" title="Komentarze opublikowane{{ $article->pending_comments_count ? ' · czeka na akceptację: '.$article->pending_comments_count : '' }}">
                                <svg class="size-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12M2.25 12.76c0 1.6 1.12 2.99 2.7 3.23 1.09.16 2.19.29 3.3.37v4.39l4.16-4.16a1.15 1.15 0 0 1 .78-.33 48.3 48.3 0 0 0 5.83-.5c1.58-.23 2.7-1.62 2.7-3.22V6.74c0-1.6-1.12-2.99-2.7-3.23A48.4 48.4 0 0 0 12 3c-2.4 0-4.76.18-7.05.51-1.58.23-2.7 1.62-2.7 3.22v6.02Z"/></svg>
                                {{ $article->approved_comments_count }}<span class="sr-only"> komentarzy</span>
                                @if($article->pending_comments_count)
                                    <span class="rounded-full bg-amber-100 px-1.5 text-[11px] font-semibold text-amber-800">+{{ $article->pending_comments_count }}</span>
                                @endif
                            </a>
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        @php
                            $hasVideo = $article->games->contains(fn ($game) => $game->hasStreams()) || preg_match('~youtu\.?be|facebook\.com/.+/videos|<iframe~i', (string) $article->content);
                            $galleriesCount = $article->games->sum('galleries_count');
                        @endphp
                        <span class="flex flex-wrap items-center gap-1.5">
                            @if($hasVideo)
                                <span class="inline-flex items-center gap-1 rounded-md bg-red-50 px-1.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/15 ring-inset" title="Artykuł ma film: transmisję meczu albo film w treści">
                                    <svg class="size-3.5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5.5v13l11-6.5-11-6.5Z"/></svg> Film
                                </span>
                            @endif
                            @if($galleriesCount)
                                <span class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-1.5 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-sky-600/15 ring-inset" title="Galerie zdjęć z meczów, o których jest artykuł">
                                    <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m3 16 5-5 4 4 3-3 6 6"/></svg> {{ $galleriesCount > 1 ? $galleriesCount.' galerie' : 'Galeria' }}
                                </span>
                            @endif
                            @if($article->games->isNotEmpty() && ! $hasVideo && ! $galleriesCount)
                                <span class="text-xs text-zinc-400" title="Artykuł jest powiązany z meczem, ale mecz nie ma jeszcze transmisji ani galerii">mecz bez mediów</span>
                            @elseif($article->games->isEmpty() && ! $hasVideo)
                                <span class="text-xs text-zinc-300">—</span>
                            @endif
                        </span>
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $article->id }})" class="text-sky-700 hover:text-sky-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $article->id }})" wire:confirm="Czy na pewno chcesz usunąć ten artykuł?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-3 py-4 text-sm text-gray-500 text-center">Brak artykułów do wyświetlenia</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4 px-4 pb-4">
        {{ $articles->links() }}
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl ring-1 ring-zinc-900/5 w-full max-w-2xl" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ $editingId ? 'Edytuj artykuł' : 'Dodaj artykuł' }}
                            </h3>
                        </div>

                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tytuł</label>
                                <input type="text" wire:model="title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zajawka (excerpt)</label>
                                <x-rich-text-editor model="excerpt" placeholder="Krótka zajawka artykułu..." height="min-h-[110px]" />
                                @error('excerpt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Treść</label>
                                <x-rich-text-editor model="content" placeholder="Pełna treść artykułu..." height="min-h-[220px]" />
                                @error('content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Wersja angielska: pokazywana odwiedzającym, którzy wybrali flagę brytyjską --}}
                            <div x-data="{ open: @js(filled($title_en) || filled($content_en)) }" class="rounded-lg border border-gray-200">
                                <button type="button" @click="open = ! open" :aria-expanded="open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-sm font-medium text-gray-700">
                                    <span class="inline-flex items-center gap-2"><x-flag country="gb" class="h-3 w-[1.125rem] rounded-[2px] ring-1 ring-black/15" /> Wersja angielska (opcjonalnie)</span>
                                    <span class="text-gray-400 transition" :class="open && 'rotate-180'" aria-hidden="true">▾</span>
                                </button>
                                <div x-show="open" x-cloak class="space-y-4 border-t border-gray-200 px-4 py-4">
                                    <p class="text-xs text-gray-500">Gdy uzupełnisz angielski tytuł, wpis dostanie brytyjską flagę, a osoby oglądające stronę po angielsku zobaczą tę wersję. Puste pola zostaną zastąpione polskimi.</p>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tytuł (EN)</label>
                                        <input type="text" wire:model="title_en" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        @error('title_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Zajawka (EN)</label>
                                        <x-rich-text-editor model="excerpt_en" placeholder="Short article excerpt..." height="min-h-[110px]" />
                                        @error('excerpt_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Treść (EN)</label>
                                        <x-rich-text-editor model="content_en" placeholder="Full article content..." height="min-h-[220px]" />
                                        @error('content_en') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="draft">Szkic</option>
                                        <option value="published">Opublikowany</option>
                                        <option value="archived">Zarchiwizowany</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Data publikacji</label>
                                    <input type="datetime-local" wire:model="published_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                    <p class="mt-1 text-xs text-gray-500">Puste = od razu po zapisaniu. Data w przyszłości = artykuł pojawi się wtedy sam.</p>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zdjęcie wyróżniające</label>
                                @php $previewUrl = $featured_image ? $featured_image->temporaryUrl() : ($existingImage ? \App\Support\Media::url($existingImage) : null); @endphp
                                @if($previewUrl)
                                    {{-- Kadr: kliknij (albo przeciągnij) punkt, który ma być zawsze widoczny; podglądy pokazują kadry ze strony. --}}
                                    <div class="mt-2 rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-200" x-data="{ x: $wire.entangle('focusX'), y: $wire.entangle('focusY'), dragging: false,
                                        pick(event) { const box = this.$refs.frame.getBoundingClientRect(); this.x = Math.round(Math.min(100, Math.max(0, (event.clientX - box.left) / box.width * 100))); this.y = Math.round(Math.min(100, Math.max(0, (event.clientY - box.top) / box.height * 100))); } }">
                                        <p class="text-xs text-zinc-600"><strong>Kadr:</strong> kliknij na zdjęciu to, co ma być zawsze widoczne (np. twarze zawodników). Poniżej podgląd, jak zdjęcie wytnie się na stronie.</p>
                                        <div class="mt-2 grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                            <div x-ref="frame" class="relative self-start cursor-crosshair touch-none select-none overflow-hidden rounded-lg bg-zinc-200"
                                                @pointerdown.prevent="dragging = true; pick($event)" @pointermove="dragging && pick($event)" @pointerup.window="dragging = false">
                                                <img src="{{ $previewUrl }}" alt="Zdjęcie wyróżniające" class="block h-auto w-full" draggable="false">
                                                <span class="pointer-events-none absolute size-7 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white bg-sky-500/40 shadow-[0_0_0_1px_rgba(0,0,0,.4)]" :style="`left: ${x}%; top: ${y}%`"></span>
                                            </div>
                                            <div class="space-y-2">
                                                @foreach(['Nagłówek artykułu' => 'aspect-[21/9] w-full', 'Kafelek na liście' => 'aspect-[16/10] w-full', 'Miniatura' => 'aspect-[4/3] w-1/2'] as $label => $shape)
                                                    <figure>
                                                        <img src="{{ $previewUrl }}" alt="" class="{{ $shape }} rounded-md object-cover ring-1 ring-zinc-300" :style="`object-position: ${x}% ${y}%`">
                                                        <figcaption class="mt-0.5 text-[11px] text-zinc-500">{{ $label }}</figcaption>
                                                    </figure>
                                                @endforeach
                                            </div>
                                        </div>
                                        <button type="button" class="mt-2 text-xs font-medium text-zinc-500 underline hover:text-zinc-800" @click="x = 50; y = 50">Środek zdjęcia</button>
                                    </div>
                                @endif
                                <x-photo-cropper model="featured_image" class="mt-2" :preview="false" aspect="free" :width="2000" hint="Zaznacz fragment zdjęcia, który ma zostać (np. obetnij krzywy brzeg). Potem kliknij punkt, który zawsze ma być widoczny." />
                                <p class="mt-1 text-xs text-gray-500">Najlepiej poziome zdjęcie, co najmniej 1600 px szerokości.</p>
                                @error('featured_image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dotyczy meczów</label>
                                <p class="text-xs text-gray-500">Kliknięcie meczu w kalendarzu otworzy ten artykuł (gdy jest kilka - najnowszy).</p>
                                @if($linkedGames->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($linkedGames as $linkedGame)
                                            <span wire:key="linked-game-{{ $linkedGame->id }}" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-800 ring-1 ring-blue-200">
                                                {{ \App\Livewire\Admin\ArticleManager::gameOptionLabel($linkedGame) }}
                                                <button type="button" wire:click="removeGame({{ $linkedGame->id }})" class="ml-1 text-blue-500 hover:text-red-600" aria-label="Odepnij mecz">✕</button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                                <input type="search" wire:model.live.debounce.250ms="gameSearch" placeholder="Szukaj meczu: rywal, drużyna, data (np. 12.10)…" class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                @if($gameSuggestions->isNotEmpty())
                                    <ul class="mt-1 max-h-56 divide-y divide-gray-100 overflow-y-auto rounded-md border border-gray-200 bg-white text-sm">
                                        @foreach($gameSuggestions as $suggestion)
                                            <li wire:key="suggest-game-{{ $suggestion->id }}">
                                                <button type="button" wire:click="addGame({{ $suggestion->id }})" class="block w-full px-3 py-2 text-left hover:bg-blue-50">+ {{ \App\Livewire\Admin\ArticleManager::gameOptionLabel($suggestion) }}</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                @error('gameIds.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center space-x-6">
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="is_featured" id="is_featured" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Wyróżniony (przypięty na górze aktualności i strony głównej)</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="allow_comments" id="allow_comments" class="rounded border-gray-300 text-sky-700 focus:ring-sky-500">
                                    <label for="allow_comments" class="ml-2 text-sm text-gray-700">Zezwól na komentarze</label>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3 rounded-b-lg">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                Anuluj
                            </button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">
                                <span wire:loading.remove wire:target="save">Zapisz</span>
                                <span wire:loading wire:target="save">Zapisywanie...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
