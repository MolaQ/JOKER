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
                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Wyświetlenia</th>
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
                        @php
                            $statusColors = [
                                'draft' => 'bg-gray-50 text-gray-600 ring-gray-500/10',
                                'published' => 'bg-green-50 text-green-700 ring-green-600/20',
                                'archived' => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                            ];
                        @endphp
                        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusColors[$article->status] ?? '' }}">
                            {{ $article->status }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                        {{ $article->views_count }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <button type="button" wire:click="openEditModal({{ $article->id }})" class="text-sky-700 hover:text-sky-900 mr-4">Edytuj</button>
                        <button type="button" wire:click="delete({{ $article->id }})" wire:confirm="Czy na pewno chcesz usunąć ten artykuł?" class="text-red-600 hover:text-red-900">Usuń</button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-4 text-sm text-gray-500 text-center">Brak artykułów do wyświetlenia</td>
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
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Zdjęcie wyróżniające</label>
                                @if($existingImage && !$featured_image)
                                    <img src="{{ \App\Support\Media::url($existingImage) }}" onerror="this.style.visibility='hidden'" class="h-24 w-full max-w-xs object-cover rounded mb-2" alt="Obraz">
                                @endif
                                @if($featured_image)
                                    <img src="{{ $featured_image->temporaryUrl() }}" class="h-24 w-full max-w-xs object-cover rounded mb-2" alt="Podgląd">
                                @endif
                                <input type="file" wire:model="featured_image" accept="image/*" class="mt-1 block w-full text-sm text-gray-700">
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
                                    <label for="is_featured" class="ml-2 text-sm text-gray-700">Wyróżniony</label>
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
