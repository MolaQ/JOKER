{{-- Menu działu podstron: pozycje i ich podstrony (np. Programy i projekty → KLUB PRO 2026) --}}
<ul class="space-y-1">
    @foreach($menu as $item)
        @php
            $active = $item->id === $currentId;
            $open = $item->id === $openGroupId;
        @endphp
        <li>
            <a href="{{ route('pages.show', $item) }}" @if($active) aria-current="page" @endif @class([
                'flex min-h-11 items-center justify-between gap-2 rounded-xl px-4 py-2.5 font-semibold transition',
                'bg-ink text-white shadow-sm' => $active,
                'text-zinc-700 hover:bg-zinc-100 hover:text-zinc-950' => ! $active,
            ])>
                <span class="min-w-0">{{ $item->localizedTitle() }}@unless($item->is_published) <span class="ml-1 rounded-full bg-amber-200 px-2 py-0.5 align-middle text-[10px] font-semibold text-amber-900 uppercase">{{ __('projekt') }}</span>@endunless</span>
                @if($item->children->isNotEmpty())
                    <span @class(['text-xs transition', 'rotate-180' => $open, 'text-zinc-400' => ! $active]) aria-hidden="true">▾</span>
                @endif
            </a>
            @if($item->children->isNotEmpty() && $open)
                <ul class="mt-1 mb-2 ml-4 space-y-1 border-l-2 border-zinc-200 pl-2">
                    @foreach($item->children as $child)
                        @php $childActive = $child->id === $currentId; @endphp
                        <li>
                            <a href="{{ route('pages.show', $child) }}" @if($childActive) aria-current="page" @endif
                                @if($child->theme_accent) style="--item-accent: {{ e($child->theme_accent) }}" @endif
                                @class([
                                    'flex min-h-10 items-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition',
                                    'bg-zinc-100 text-zinc-950' => $childActive,
                                    'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' => ! $childActive,
                                ])>
                                <span class="h-2 w-2 shrink-0 rounded-full bg-[var(--item-accent,var(--color-sky-club))]" aria-hidden="true"></span>
                                <span class="min-w-0">{{ $child->localizedTitle() }}@unless($child->is_published) <span class="ml-1 rounded-full bg-amber-200 px-2 py-0.5 align-middle text-[10px] font-semibold text-amber-900 uppercase">{{ __('projekt') }}</span>@endunless</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    @endforeach
</ul>
