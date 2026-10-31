@props(['progress', 'dark' => false])
{{-- Dyskretna plakietka postępu grupy, w stylu „prognoza”: „zakończona” albo „2 z 3 meczów”. --}}
@if($progress['total'] > 0)
    <span @class(['shrink-0 rounded-full border border-dashed px-2 font-sans text-[11px] font-normal tracking-normal normal-case', 'border-zinc-300 text-zinc-400' => ! $dark, 'border-white/40 text-white/80' => $dark])>{{ $progress['finished'] ? __('zakończona') : __(':played z :total meczów', ['played' => $progress['played'], 'total' => $progress['total']]) }}</span>
@endif
