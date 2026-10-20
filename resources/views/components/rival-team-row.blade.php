@props(['team', 'inClub' => false])
{{-- Wiersz drużyny rywali w panelu: zaznaczenie do łączenia w klub, logo (własne albo klubu), dawne nazwy --}}
<li class="flex items-center gap-3 px-4 py-3 sm:px-6 {{ $inClub ? 'sm:pl-14' : '' }}" wire:key="rival-team-{{ $team->id }}">
    <input type="checkbox" wire:model.live="selected.{{ $team->id }}" class="rounded border-gray-300 text-blue-600" aria-label="Zaznacz {{ $team->full_name }}">
    @if($team->logoPath())
        <img src="{{ \App\Support\Media::url($team->logoPath()) }}" class="h-10 w-10 shrink-0 rounded object-contain {{ $team->logo_path ? '' : 'opacity-70' }}" alt="" title="{{ $team->logo_path ? 'Własne logo' : 'Logo klubu' }}">
    @else
        <div class="h-10 w-10 shrink-0 rounded bg-gray-100 flex items-center justify-center text-gray-400 text-xs">brak</div>
    @endif
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900">{{ $team->full_name }}@if($team->short_name) <span class="font-normal text-gray-500">· {{ $team->short_name }}</span>@endif</p>
        <p class="truncate text-xs text-gray-500">
            meczów: {{ $team->games_count }}
            @if($team->logo_path && $inClub) · własne logo @endif
            @if($team->aliases) · dawniej: {{ implode(', ', $team->aliases) }} @endif
        </p>
    </div>
    @if($inClub)
        <button type="button" wire:click="detachFromClub({{ $team->id }})" class="hidden text-sm text-gray-500 hover:text-gray-800 sm:inline">Wyłącz z klubu</button>
    @endif
    <button type="button" wire:click="openEditModal({{ $team->id }})" class="text-sm text-blue-600 hover:text-blue-900">Edytuj</button>
    <button type="button" wire:click="delete({{ $team->id }})" wire:confirm="Czy na pewno chcesz usunąć tę drużynę rywali?" class="text-sm text-red-600 hover:text-red-900">Usuń</button>
</li>
