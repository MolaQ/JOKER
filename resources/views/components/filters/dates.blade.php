@props(['label' => 'Data', 'from' => '', 'to' => '', 'preset' => null, 'presets' => \App\Support\ListFilters::DATE_PRESETS])
{{-- Zakres dat: skróty jednym kliknięciem i własny zakres „od – do” (trait FiltersList). --}}
<div {{ $attributes->merge(['class' => 'flex min-w-0 flex-col gap-1.5 md:flex-row md:items-center md:gap-3']) }} role="group" aria-label="{{ $label }}">
    <span class="w-24 shrink-0 text-[11px] font-bold tracking-wider text-zinc-500 uppercase">{{ $label }}</span>
    <div class="flex flex-col gap-2 lg:flex-row lg:flex-wrap lg:items-center">
        <div class="scrollbar-none -mx-4 flex gap-1.5 overflow-x-auto px-4 md:mx-0 md:flex-wrap md:overflow-visible md:px-0">
            @foreach($presets as $key => $presetLabel)
                <button type="button" wire:click="setDatePreset('{{ $key }}')" aria-pressed="{{ $preset === $key ? 'true' : 'false' }}" class="filter-chip">{{ $presetLabel }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-2 text-sm text-zinc-500">
            <input type="date" wire:model.live="dateFrom" aria-label="{{ $label }} od" class="filter-date" data-date-from>
            <span aria-hidden="true">–</span>
            <input type="date" wire:model.live="dateTo" aria-label="{{ $label }} do" class="filter-date" data-date-to>
        </div>
    </div>
</div>
