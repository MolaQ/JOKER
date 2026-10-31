@php
    $show = fn ($value) => match (true) {
        $value === null => '—',
        $value === true => 'tak',
        $value === false => 'nie',
        default => (string) $value,
    };
@endphp
<div class="space-y-4">
    <div class="admin-card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="sm:col-span-2 lg:col-span-1">
            <label class="block text-xs font-medium text-gray-600" for="audit-search">Szukaj w opisie i zmianach</label>
            <input id="audit-search" type="search" wire:model.live.debounce.400ms="search" placeholder="np. Kadeci, Kowalski, wynik…" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs focus:border-sky-500 focus:ring-sky-500">
        </div>
        <div>
            <span class="block text-xs font-medium text-gray-600">Kto</span>
            <x-search-select model="actor" class="mt-1" empty="— wszyscy —" placeholder="Szukaj osoby…" :options="$actorOptions" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600" for="audit-type">Czego dotyczy</label>
            <select id="audit-type" wire:model.live="type" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs">
                <option value="">— wszystko —</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600" for="audit-action">Rodzaj zmiany</label>
            <select id="audit-action" wire:model.live="action" class="mt-1 block w-full rounded-lg border-zinc-300 text-sm shadow-xs">
                <option value="">— każda —</option>
                @foreach($actionOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-600" for="audit-from">Od</label>
                <input id="audit-from" type="date" wire:model.live="from" class="mt-1 block w-full rounded-lg border-zinc-300 px-2 text-sm shadow-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600" for="audit-to">Do</label>
                <input id="audit-to" type="date" wire:model.live="to" class="mt-1 block w-full rounded-lg border-zinc-300 px-2 text-sm shadow-xs">
            </div>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-gray-600">
        <p>
            {{ trans_choice('{0} Brak wpisów|{1} :count wpis|[2,4] :count wpisy|[5,*] :count wpisów', $entries->total(), ['count' => number_format($entries->total(), 0, ',', ' ')]) }}{{ $filtered ? ' pasujących do filtrów' : '' }}
            <span wire:loading class="ml-2 text-sky-700">szukam…</span>
        </p>
        @if($filtered)
            <button type="button" wire:click="resetFilters" class="font-semibold text-sky-700 hover:text-sky-900">Wyczyść filtry</button>
        @endif
    </div>

    <ul class="admin-card divide-y divide-gray-100">
        @forelse($entries as $entry)
            @php
                $changes = $entry->changes();
                $verb = \App\Support\ChangeLog::ACTIONS[$entry->action] ?? null;
            @endphp
            <li wire:key="audit-{{ $entry->id }}" x-data="{ open: false }" class="px-4 py-3">
                <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                    <div class="min-w-0">
                        <p class="text-sm text-gray-900">
                            <span @class([
                                'mr-1 inline-block rounded px-1.5 py-0.5 text-[11px] font-semibold',
                                'bg-emerald-50 text-emerald-700' => $entry->action === 'model.created',
                                'bg-sky-50 text-sky-700' => $entry->action === 'model.updated',
                                'bg-red-50 text-red-700' => $entry->action === 'model.deleted',
                                'bg-violet-50 text-violet-700' => ! $verb,
                            ])>{{ \App\Support\ChangeLog::typeLabel($entry->subject_type) ?? 'Panel' }}</span>
                            {{ $entry->description }}
                        </p>
                        @if(! empty($entry->details['dodane']) || ! empty($entry->details['odebrane']))
                            <p class="mt-0.5 text-sm text-gray-600">
                                @if(! empty($entry->details['dodane']))<span class="text-emerald-700">+ {{ implode(', ', $entry->details['dodane']) }}</span>@endif
                                @if(! empty($entry->details['odebrane']))<span class="ml-2 text-red-700">− {{ implode(', ', $entry->details['odebrane']) }}</span>@endif
                            </p>
                        @endif
                    </div>
                    <p class="shrink-0 text-xs text-gray-500" title="{{ $entry->created_at->format('d.m.Y H:i:s') }}">
                        <span class="font-medium text-gray-700">{{ $entry->actor?->name ?? ($entry->actor_id ? 'konto usunięte' : 'system') }}</span>
                        · {{ $entry->created_at->format('d.m.Y H:i') }}
                    </p>
                </div>
                @if($changes)
                    <button type="button" x-on:click="open = ! open" class="mt-1 text-xs font-semibold text-sky-700 hover:text-sky-900" :aria-expanded="open">
                        <span x-text="open ? 'Ukryj szczegóły' : @js('Pokaż, co się zmieniło ('.count($changes).')')"></span>
                    </button>
                    <div x-show="open" x-cloak class="mt-2 overflow-hidden rounded-lg border border-gray-200">
                        <table data-scroll-table class="w-full text-left text-xs">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="w-1/5 px-3 py-1.5 font-medium">Pole</th>
                                    @if($entry->action !== 'model.created')<th class="px-3 py-1.5 font-medium">Było</th>@endif
                                    @if($entry->action !== 'model.deleted')<th class="px-3 py-1.5 font-medium">Jest</th>@endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 align-top">
                                @foreach($changes as $field => [$before, $after])
                                    <tr>
                                        <td class="px-3 py-1.5 font-medium text-gray-700">{{ \App\Support\ChangeLog::fieldLabel($field) }}</td>
                                        @if($entry->action !== 'model.created')<td class="px-3 py-1.5 text-gray-500"><div class="max-h-40 overflow-y-auto break-words whitespace-pre-wrap">{{ $show($before) }}</div></td>@endif
                                        @if($entry->action !== 'model.deleted')<td class="px-3 py-1.5 text-gray-900"><div class="max-h-40 overflow-y-auto break-words whitespace-pre-wrap">{{ $show($after) }}</div></td>@endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </li>
        @empty
            <li class="px-4 py-10 text-center text-sm text-gray-500">{{ $filtered ? 'Nic nie pasuje do filtrów.' : 'Dziennik jest jeszcze pusty.' }}</li>
        @endforelse
    </ul>

    {{ $entries->links() }}
</div>
