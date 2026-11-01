@props(['tournament'])
{{-- Partnerzy turnieju: osobna lista dla każdego turnieju (panel turnieju → Partnerzy), partner główny wyróżniony --}}
@php
    $tournament->loadMissing('sponsors');
    $partners = $tournament->sponsors->where('is_active', true)->filter(fn ($sponsor) => $sponsor->logo_path || $sponsor->name);
    $main = $partners->filter(fn ($sponsor) => $sponsor->pivot->is_main)->values();
    $others = $partners->reject(fn ($sponsor) => $sponsor->pivot->is_main)->values();
@endphp
@if($partners->isNotEmpty())
<section class="border-t border-zinc-200 bg-zinc-50" aria-labelledby="partnerzy-turnieju">
    <div class="container-site py-12 lg:py-16">
        <p class="text-center text-xs font-semibold tracking-[0.3em] text-sky-club-dark uppercase">{{ $tournament->fullName() }}</p>
        <h2 id="partnerzy-turnieju" class="mt-2 text-center font-display text-3xl font-bold text-zinc-950 uppercase sm:text-4xl">{{ __('Partnerzy turnieju') }}</h2>

        <div class="mx-auto mt-8 max-w-5xl space-y-4">
            @if($main->isNotEmpty())
                <ul @class(['grid gap-4', 'sm:grid-cols-2' => $main->count() > 1])>
                    @foreach($main as $sponsor)
                        <li>
                            <{{ $sponsor->href() ? 'a' : 'div' }} @if($sponsor->href()) href="{{ $sponsor->href() }}" @if($sponsor->isExternal()) target="_blank" rel="noopener" @endif @endif title="{{ $sponsor->name }}"
                                @if($sponsor->tile_color) style="background: {{ $sponsor->tile_color }}" @endif
                                @class(['group mx-auto flex h-40 max-w-xl flex-col items-center justify-center gap-3 rounded-3xl p-5 ring-2 ring-[var(--color-countdown,#ffd600)] transition sm:h-48', 'bg-white' => ! $sponsor->tile_color, 'hover:-translate-y-1 hover:shadow-xl' => $sponsor->href()])>
                                <span @class(['text-[10px] font-semibold tracking-[0.25em] uppercase', 'text-zinc-500' => ! $sponsor->tile_color, 'text-white/70' => $sponsor->tile_color])>{{ __('Partner główny') }}</span>
                                @if($sponsor->logo_path)
                                    <img src="{{ \App\Support\Media::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" loading="lazy" class="min-h-0 max-w-full flex-1 object-contain">
                                @else
                                    <span class="font-display text-3xl font-bold text-zinc-900 uppercase">{{ $sponsor->name }}</span>
                                @endif
                            </{{ $sponsor->href() ? 'a' : 'div' }}>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if($others->isNotEmpty())
                <ul @class(['grid grid-cols-2 gap-3', 'sm:grid-cols-3' => $others->count() % 3 === 0 || $others->count() > 4, 'sm:grid-cols-4' => $others->count() === 4, 'lg:grid-cols-5' => $others->count() > 6])>
                    @foreach($others as $sponsor)
                        <li>
                            <{{ $sponsor->href() ? 'a' : 'div' }} @if($sponsor->href()) href="{{ $sponsor->href() }}" @if($sponsor->isExternal()) target="_blank" rel="noopener" @endif @endif title="{{ $sponsor->name }}"
                                @if($sponsor->tile_color) style="background: {{ $sponsor->tile_color }}" @endif
                                @class(['flex h-24 items-center justify-center rounded-2xl p-3 ring-1 transition sm:h-28', 'bg-white ring-zinc-200' => ! $sponsor->tile_color, 'ring-black/10' => $sponsor->tile_color, 'hover:-translate-y-1 hover:shadow-lg' => $sponsor->href()])>
                                @if($sponsor->logo_path)
                                    <img src="{{ \App\Support\Media::url($sponsor->logo_path) }}" alt="{{ $sponsor->name }}" loading="lazy" class="max-h-full max-w-full object-contain">
                                @else
                                    <span class="text-center font-semibold text-zinc-800">{{ $sponsor->name }}</span>
                                @endif
                            </{{ $sponsor->href() ? 'a' : 'div' }}>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
@endif
