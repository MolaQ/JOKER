{{-- Blok „Wspierają nas” nad stopką: kategorie i logotypy z panelu (Klub → Sponsorzy).
     Komputer: logotypy kategorii w jednym szeregu. Telefon: kategoria z więcej niż 4 logotypami przesuwa się powoli sama. --}}
@php
    $groups = \App\Models\SponsorGroup::forBlock();
    $heights = ['xl' => 'h-28 sm:h-32', 'lg' => 'h-24 sm:h-28', 'md' => 'h-24 sm:h-28'];
    // Dwie sąsiednie kategorie z najwyżej 3 logotypami stają na komputerze obok siebie.
    $half = [];
    for ($i = 0; $i < $groups->count(); $i++) {
        if ($groups[$i]->sponsors->count() <= 3 && ($groups[$i + 1] ?? null)?->sponsors->count() <= 3 && isset($groups[$i + 1])) {
            $half[$groups[$i]->id] = $half[$groups[$i + 1]->id] = true;
            $i++;
        }
    }
    $cooperation = $site['sponsors_block_cta'] ? \App\Models\Page::published()->where('slug', 'wspolpraca')->first(['slug', 'title', 'title_en']) : null;
@endphp
@if($groups->isNotEmpty())
<section class="relative border-t border-zinc-200 bg-white" aria-labelledby="wspieraja-nas" data-sponsor-block>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-sky-club via-[var(--color-countdown,#ffd600)] to-sky-club" aria-hidden="true"></div>
    <div class="container-site py-14 lg:py-20">
        <div class="text-center">
            @if(filled($site['sponsors_block_eyebrow']))
                <p class="text-xs font-semibold tracking-[0.3em] text-sky-club-dark uppercase">{{ __($site['sponsors_block_eyebrow']) }}</p>
            @endif
            <h2 id="wspieraja-nas" class="mt-2 font-display text-4xl font-bold text-zinc-950 uppercase sm:text-5xl">{{ __($site['sponsors_block_title'] ?: 'Wspierają nas') }}</h2>
        </div>

        <div class="mt-10 grid gap-x-8 gap-y-10 lg:mt-14 lg:grid-cols-2">
            @foreach($groups as $group)
                @php
                    $count = $group->sponsors->count();
                    $moving = $count > 4;
                    $logos = $moving ? $group->sponsors->concat($group->sponsors) : $group->sponsors;
                @endphp
                <div @class(['min-w-0', 'lg:col-span-2' => ! isset($half[$group->id])])>
                    <div class="mb-4 flex items-center gap-3">
                        <span class="h-px flex-1 bg-zinc-200"></span>
                        <h3 class="max-w-[80%] text-center text-[11px] font-semibold tracking-[0.25em] text-zinc-500 uppercase sm:text-xs">{{ __($group->name) }}</h3>
                        <span class="h-px flex-1 bg-zinc-200"></span>
                    </div>
                    <div @class(['sponsor-marquee' => $moving, 'sponsor-marquee-wide' => $count > 7])>
                        <ul @class([
                            'grid gap-3',
                            'sponsor-track' => $moving,
                            'grid-cols-1' => $count === 1,
                            'grid-cols-2' => $count === 2 || $count === 4,
                            'grid-cols-3' => $count === 3,
                            'sm:grid-cols-4' => $count === 4,
                        ]) @if($moving) style="--sponsor-count: {{ $count }}" @endif>
                            @foreach($logos as $i => $sponsor)
                                @php $copy = $i >= $count; @endphp
                                <li @class(['sponsor-copy' => $copy]) @if($copy) aria-hidden="true" @endif>
                                    <{{ $sponsor->href() ? 'a' : 'div' }} @if($sponsor->href()) href="{{ $sponsor->href() }}" @if($sponsor->isExternal()) target="_blank" rel="noopener" @endif @if($copy) tabindex="-1" @endif @endif
                                        title="{{ $sponsor->name }}"
                                        @if($sponsor->tile_color) style="background: {{ $sponsor->tile_color }}" @endif
                                        @class([
                                            'group flex items-center justify-center rounded-2xl p-3 ring-1 transition duration-300 sm:p-4',
                                            'hover:-translate-y-1 hover:shadow-xl hover:shadow-zinc-900/10' => $sponsor->href(),
                                            $heights[$group->size] ?? $heights['md'],
                                            'bg-white ring-zinc-200 hover:ring-sky-club' => ! $sponsor->tile_color,
                                            'ring-black/10' => $sponsor->tile_color,
                                        ])>
                                        <img src="{{ \App\Support\Media::url($sponsor->logo_path) }}" alt="{{ $copy ? '' : $sponsor->name }}" loading="lazy" decoding="async" class="max-h-full max-w-full object-contain transition duration-300 group-hover:scale-105">
                                    </{{ $sponsor->href() ? 'a' : 'div' }}>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endforeach
        </div>

        @if($cooperation)
            <p class="mt-12 text-center text-sm text-zinc-500">{{ __('Chcesz dołączyć do grona partnerów :club?', ['club' => $site['club_name_genitive'] ?: $site['club_name']]) }} <a href="{{ route('pages.show', $cooperation) }}" class="font-semibold whitespace-nowrap text-sky-club-dark underline-offset-4 hover:underline">{{ $cooperation->localizedTitle() }} →</a></p>
        @endif
    </div>
</section>
@endif
