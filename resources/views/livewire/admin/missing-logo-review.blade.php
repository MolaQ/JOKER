<div class="space-y-3">
    @forelse($items as ['game' => $game, 'rival' => $rival])
        <div wire:key="logo-{{ $game->id }}" class="admin-card flex flex-wrap items-start gap-4 p-4">
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-gray-100 text-2xl text-gray-400 ring-1 ring-gray-200">?</div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold tracking-wide text-sky-700 uppercase">
                    {{ $game->game_date->translatedFormat('l, j F') }}{{ $game->game_date->format('H:i') !== '00:00' ? ', '.$game->game_date->format('H:i') : '' }}
                    · {{ (int) today()->diffInDays($game->game_date->copy()->startOfDay()) === 0 ? 'dziś' : 'za '.trans_choice(':count dzień|:count dni|:count dni', (int) today()->diffInDays($game->game_date->copy()->startOfDay()), ['count' => (int) today()->diffInDays($game->game_date->copy()->startOfDay())]) }}
                </p>
                <p class="mt-1 font-semibold text-gray-900">{{ implode(' – ', $game->sidesNames()) }}</p>
                <p class="text-sm text-gray-500">{{ $game->team?->name }}{{ $game->competition ? ' · '.$game->competition->name : '' }}</p>

                @if($rival)
                    <form wire:submit="save({{ $rival->id }})" class="mt-3 flex flex-wrap items-end gap-4">
                        @foreach(['logos' => 'Logo na jasne tło', 'logosLight' => 'Na ciemne tło (miniatury, opcjonalnie)'] as $field => $label)
                            <label class="text-xs font-medium text-gray-700">
                                {{ $label }}
                                <span class="mt-1 flex items-center gap-2">
                                    @if(($file = $this->{$field}[$rival->id] ?? null) && method_exists($file, 'isPreviewable') && $file->isPreviewable())
                                        <img src="{{ $file->temporaryUrl() }}" alt="" @class(['size-12 rounded object-contain p-1 ring-1 ring-gray-200', 'bg-white' => $field === 'logos', 'bg-gray-900' => $field === 'logosLight'])>
                                    @endif
                                    <input type="file" wire:model="{{ $field }}.{{ $rival->id }}" accept="image/*" class="block max-w-56 text-xs">
                                </span>
                                @error($field.'.'.$rival->id)<span class="mt-1 block text-red-600">{{ $message }}</span>@enderror
                            </label>
                        @endforeach
                        <button type="submit" wire:loading.attr="disabled" class="rounded-md bg-sky-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-sky-700 disabled:opacity-50">Zapisz logo</button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">Zapisuje się w drużynie rywali „{{ $rival->full_name }}”@if($rival->club), klub {{ $rival->club->name }}@endif.</p>
                @else
                    <p class="mt-2 rounded-md bg-amber-50 p-2 text-xs text-amber-800">Rywal „{{ $game->opponent }}” nie jest przypisany do drużyny z listy rywali. Dodaj go albo przypisz w <a href="{{ route('admin.rival-teams.index') }}" class="font-semibold underline">Rywale</a>, a potem wgraj logo.</p>
                @endif
            </div>
        </div>
    @empty
        <div class="admin-card p-6 text-center text-sm text-gray-500">Wszyscy rywale z najbliższych {{ \App\Support\MissingLogos::DAYS }} dni mają logo.</div>
    @endforelse
</div>
