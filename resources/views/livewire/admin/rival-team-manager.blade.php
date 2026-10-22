@php
    $input = 'block w-full rounded-md border-gray-300 shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm';
    $logoBox = 'h-10 w-10 shrink-0 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs';
@endphp

<div>
    <div class="flex flex-col gap-4 border-b border-gray-200 bg-white p-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Szukaj klubu, drużyny, dawnej nazwy..." class="{{ $input }} sm:w-80">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" wire:model.live="showArchived" class="rounded border-gray-300 text-sky-700">
                Pokaż archiwalne ({{ $archivedCount }})
            </label>
        </div>
        <div class="flex gap-2">
            <button type="button" wire:click="openClubModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">+ Klub</button>
            <button type="button" wire:click="openCreateModal" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">+ Drużyna rywali</button>
        </div>
    </div>

    {{-- Nowe nazwy z importu --}}
    @if($toReview->isNotEmpty())
        <section class="border-b border-amber-200 bg-amber-50 p-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-amber-900">Do przypisania ({{ $toReview->count() }})</h3>
                    <p class="text-xs text-amber-800">Nowe nazwy z importu. Wybierz klub albo wskaż, że to ta sama drużyna pod nową nazwą (np. ze sponsorem). Następny import rozpozna nazwę sam.</p>
                </div>
                <button type="button" wire:click="acceptAllReview" class="text-xs font-semibold text-amber-900 underline">Zostaw wszystkie jako osobne drużyny</button>
            </div>
            <ul class="mt-3 space-y-2">
                @foreach($toReview as $team)
                    <li class="grid gap-2 rounded-md bg-white p-3 ring-1 ring-amber-200 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1fr)_auto] lg:items-center" wire:key="review-{{ $team->id }}">
                        <span class="text-sm font-semibold text-gray-900">{{ $team->full_name }} <span class="font-normal text-gray-500">· meczów: {{ $team->games_count }}</span></span>
                        <select wire:model="review.{{ $team->id }}.club" class="{{ $input }}">
                            <option value="">— bez klubu —</option>
                            @foreach($allClubs as $club)
                                <option value="{{ $club->id }}">Klub: {{ $club->name }}</option>
                            @endforeach
                        </select>
                        <select wire:model="review.{{ $team->id }}.merge" class="{{ $input }}">
                            <option value="">— nowa, osobna drużyna —</option>
                            @foreach($allTeams->where('id', '!==', $team->id)->where('needs_review', false) as $other)
                                <option value="{{ $other->id }}">To ta sama co: {{ $other->full_name }}</option>
                            @endforeach
                        </select>
                        <button type="button" wire:click="resolveReview({{ $team->id }})" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-700">Zatwierdź</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Podpowiedzi klubów --}}
    @if($suggestions->isNotEmpty() && $search === '')
        <section class="border-b border-blue-100 bg-blue-50 p-4">
            <h3 class="text-sm font-semibold text-blue-900">Proponowane kluby</h3>
            <p class="text-xs text-blue-800">Drużyny o podobnej nazwie: różnią się numerem zespołu albo nazwą sponsora. Sprawdź przed kliknięciem.</p>
            <ul class="mt-2 space-y-2">
                @foreach($suggestions as $group)
                    <li class="flex flex-wrap items-center gap-2 text-sm" wire:key="suggest-{{ $group->first()->id }}">
                        @php $existingClub = $group->pluck('club')->filter()->first(); @endphp
                        <span class="text-gray-900">{{ $group->pluck('full_name')->implode(', ') }}</span>
                        <button type="button" wire:click="createSuggestedClub(@js($group->pluck('id')->all()))" class="rounded-md bg-sky-600 px-3 py-1 text-xs font-semibold text-white hover:bg-sky-700">{{ $existingClub ? 'Dodaj do klubu '.$existingClub->name : 'Utwórz klub' }}</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Łączenie zaznaczonych --}}
    @if(collect($selected)->filter()->isNotEmpty())
        <div class="sticky top-0 z-10 flex flex-wrap items-center gap-2 border-b border-gray-200 bg-white/95 p-3 shadow-sm">
            <span class="text-sm font-semibold text-gray-900">Zaznaczone: {{ collect($selected)->filter()->count() }}</span>
            <select wire:model.live="groupClubId" class="{{ $input }} w-auto">
                <option value="">Nowy klub…</option>
                @foreach($allClubs as $club)
                    <option value="{{ $club->id }}">{{ $club->name }}</option>
                @endforeach
            </select>
            @if($groupClubId === '')
                <input type="text" wire:model="groupClubName" placeholder="Nazwa nowego klubu" class="{{ $input }} w-64">
            @endif
            <button type="button" wire:click="groupSelected" class="rounded-md bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">Połącz w klub</button>
            @error('groupClubName') <span class="text-sm text-red-600">{{ $message }}</span> @enderror
        </div>
    @endif

    <div class="divide-y divide-gray-200 bg-white">
        @foreach($clubs as $club)
            <div x-data="{ open: @js($search !== '') }" wire:key="club-{{ $club->id }}">
                <div class="flex items-center gap-3 px-4 py-3 sm:px-6">
                    <button type="button" @click="open = !open" class="flex min-w-0 flex-1 items-center gap-3 text-left">
                        <span class="text-gray-400 transition" :class="open && 'rotate-90'">▶</span>
                        @if($club->logo_path)
                            <img src="{{ \App\Support\Media::url($club->logo_path) }}" class="h-10 w-10 shrink-0 rounded object-contain" alt="">
                        @else
                            <div class="{{ $logoBox }}">brak</div>
                        @endif
                        <span class="truncate text-sm font-semibold text-gray-900">{{ $club->name }}</span>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $club->visibleTeams->count() }} {{ $club->visibleTeams->count() === 1 ? 'drużyna' : ($club->visibleTeams->count() < 5 && $club->visibleTeams->count() > 0 ? 'drużyny' : 'drużyn') }}</span>
                    </button>
                    <button type="button" wire:click="openClubModal({{ $club->id }})" class="text-sm text-sky-700 hover:text-sky-900">Edytuj klub</button>
                    <button type="button" wire:click="deleteClub({{ $club->id }})" wire:confirm="Usunąć klub {{ $club->name }}? Drużyny zostaną, tylko bez klubu." class="text-sm text-red-600 hover:text-red-900">Usuń</button>
                </div>
                <ul x-show="open" x-cloak class="border-t border-gray-100 bg-gray-50/60">
                    @foreach($club->visibleTeams as $team)
                        <x-rival-team-row :team="$team" :in-club="true" />
                    @endforeach
                </ul>
            </div>
        @endforeach

        @if($unclubbed->isNotEmpty())
            <div>
                <p class="bg-gray-50 px-4 py-2 text-xs font-semibold tracking-wide text-gray-500 uppercase sm:px-6">Drużyny bez klubu ({{ $unclubbed->count() }})</p>
                <ul>
                    @foreach($unclubbed as $team)
                        <x-rival-team-row :team="$team" />
                    @endforeach
                </ul>
            </div>
        @endif

        @if($clubs->isEmpty() && $unclubbed->isEmpty())
            <p class="px-4 py-6 text-center text-sm text-gray-500">Brak drużyn rywali do wyświetlenia</p>
        @endif
    </div>

    {{-- Drużyna --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="closeModal"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/5" @click.stop>
                    <form wire:submit.prevent="save">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edytuj drużynę rywali' : 'Dodaj drużynę rywali' }}</h3>
                            <p class="text-xs text-gray-500">Drużyna jest wspólna dla wszystkich kategorii.</p>
                        </div>
                        <div class="max-h-[70vh] space-y-4 overflow-y-auto px-6 py-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Pełna nazwa</label>
                                <input type="text" wire:model="full_name" placeholder="np. UMKS MOS Wola Warszawa" class="mt-1 {{ $input }}">
                                @error('full_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Krótka nazwa</label>
                                <input type="text" wire:model="short_name" placeholder="np. Wola Warszawa" class="mt-1 {{ $input }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Klub</label>
                                <select wire:model="rival_club_id" class="mt-1 {{ $input }}">
                                    <option value="">— bez klubu —</option>
                                    @foreach($allClubs as $club)
                                        <option value="{{ $club->id }}">{{ $club->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Własne logo</label>
                                <p class="text-xs text-gray-500">Zostaw puste, a drużyna użyje logo klubu. Własne logo ustaw tylko, gdy sekcja lub rocznik ma inne.</p>
                                @if($existingLogo && ! $logo)
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ \App\Support\Media::url($existingLogo) }}" class="h-16 w-16 rounded object-contain" alt="Logo">
                                        <button type="button" wire:click="removeOwnLogo" class="text-xs text-red-600 underline">Usuń własne logo</button>
                                    </div>
                                @endif
                                @if($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="mt-2 h-16 w-16 rounded object-contain" alt="Podgląd">
                                @endif
                                <input type="file" wire:model="logo" accept="image/*" class="mt-2 block w-full text-sm text-gray-700">
                                @error('logo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Dawne nazwy (jedna w linii)</label>
                                <textarea wire:model="aliases" rows="3" class="mt-1 {{ $input }}" placeholder="np. Energetyk Poznań bez sponsora"></textarea>
                                <p class="mt-1 text-xs text-gray-500">Import z tymi nazwami trafi do tej drużyny.</p>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 rounded-b-lg bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="closeModal" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Anuluj</button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">Zapisz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Klub --}}
    @if($showClubModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="fixed inset-0 bg-zinc-950/40 backdrop-blur-sm" wire:click="$set('showClubModal', false)"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl ring-1 ring-zinc-900/5" @click.stop>
                    <form wire:submit.prevent="saveClub">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingClubId ? 'Edytuj klub' : 'Dodaj klub' }}</h3>
                        </div>
                        <div class="space-y-4 px-6 py-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nazwa klubu</label>
                                <input type="text" wire:model="clubName" placeholder="np. Energetyk Poznań" class="mt-1 {{ $input }}">
                                @error('clubName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo klubu</label>
                                <p class="text-xs text-gray-500">Pokaże się przy wszystkich drużynach klubu, które nie mają własnego logo.</p>
                                @if($existingClubLogo && ! $clubLogo)
                                    <img src="{{ \App\Support\Media::url($existingClubLogo) }}" class="mt-2 h-16 w-16 rounded object-contain" alt="Logo">
                                @endif
                                @if($clubLogo)
                                    <img src="{{ $clubLogo->temporaryUrl() }}" class="mt-2 h-16 w-16 rounded object-contain" alt="Podgląd">
                                @endif
                                <input type="file" wire:model="clubLogo" accept="image/*" class="mt-2 block w-full text-sm text-gray-700">
                                @error('clubLogo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 rounded-b-lg bg-gray-50 px-6 py-4">
                            <button type="button" wire:click="$set('showClubModal', false)" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Anuluj</button>
                            <button type="submit" class="rounded-md bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-700">Zapisz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
