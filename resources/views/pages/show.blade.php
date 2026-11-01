@extends('layouts.public')

@section('title', $page->localizedTitle())
@section('description', Str::limit(strip_tags((string) ($page->localizedLead() ?: $page->localizedContent())), 155))
@if($isPreview)
    @section('robots', 'noindex, nofollow')
@endif

@if($page->image_path)
    @section('og_image', \App\Support\Media::url($page->image_path))
@endif

@php
    $themed = (bool) $page->themeStyle();
    $eyebrow = $page->parent?->localizedTitle() ?? $site['club_name'];
    $currentId = $page->id;
    $openGroupId = $page->parent_id ?? $page->id;
@endphp

@section('content')
<article data-print-area @if($themed) style="{{ $page->themeStyle() }}" @endif class="page-themed">
    @if($isPreview)
        <div data-no-print class="border-b border-amber-300 bg-amber-50 text-amber-900">
            <div class="container-site flex max-w-6xl flex-wrap items-center gap-x-4 gap-y-2 py-3 text-sm">
                <p class="w-full min-w-0 sm:w-auto sm:flex-1"><span class="font-semibold">{{ __('Projekt przed publikacją.') }}</span> {{ __('Tę stronę i link do niej widzą tylko osoby z podglądem ukrytych podstron (administrator, szef).') }}</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" onclick="window.print()" class="rounded-full bg-amber-900 px-4 py-2 text-xs font-semibold text-white hover:bg-amber-800">⬇ {{ __('Zapisz jako PDF') }}</button>
                    @can('site.manage')
                        <a href="{{ route('admin.pages.index') }}" class="rounded-full px-4 py-2 text-xs font-semibold ring-1 ring-amber-900/30 hover:bg-amber-100">{{ __('Edytuj w panelu') }}</a>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    @if($page->image_path && $page->image_is_banner)
        {{-- Baner (np. programu z logotypami): cały, bez przycinania i przyciemniania --}}
        <header class="relative isolate overflow-hidden bg-[var(--page-primary,var(--color-ink))] text-white">
            <div class="container-site max-w-6xl pt-5 pb-8 sm:pt-8 lg:pb-12">
                <img src="{{ \App\Support\Media::url($page->image_path) }}" alt="{{ $page->localizedTitle() }}" width="1300" height="483" fetchpriority="high" class="block h-auto w-full rounded-2xl shadow-2xl shadow-black/30 sm:rounded-3xl">
                <p class="mt-6 text-xs font-semibold tracking-widest text-[var(--page-accent,var(--color-sky-club))] uppercase sm:mt-8">{{ $eyebrow }}</p>
                <h1 class="mt-2 font-display text-4xl leading-[1.05] font-bold uppercase sm:text-6xl">{{ $page->localizedTitle() }}@if($page->hasEnglish()) <x-english-badge class="ml-1" />@endif</h1>
            </div>
            <div class="h-1.5 bg-[var(--page-accent,var(--color-sky-club))]" aria-hidden="true"></div>
        </header>
    @elseif($page->image_path)
        <header class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
            <x-photo :src="\App\Support\Media::url($page->image_path)" :alt="$page->localizedTitle()" eager sizes="100vw" width="1800" height="900" class="absolute inset-0 -z-20 h-full w-full opacity-50" />
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/30"></div>
            <div class="container-site max-w-6xl pt-24 pb-14 lg:pt-36">
                <p class="eyebrow">{{ $eyebrow }}</p>
                <h1 class="mt-4 font-display text-4xl leading-[1.05] font-bold uppercase sm:text-6xl">{{ $page->localizedTitle() }}@if($page->hasEnglish()) <x-english-badge class="ml-1" />@endif</h1>
            </div>
        </header>
    @else
        <header class="container-site max-w-6xl pt-14 lg:pt-20">
            <p class="eyebrow" @if($themed) style="color: var(--page-primary)" @endif>{{ $eyebrow }}</p>
            <h1 class="heading-xl mt-4 text-balance">{{ $page->localizedTitle() }}@if($page->hasEnglish()) <x-english-badge class="ml-1" />@endif</h1>
        </header>
    @endif

    @php
        $hasMenu = $menu->count() > 1 || $menu->contains(fn ($item) => $item->children->isNotEmpty());
    @endphp

    <div @class(['container-site max-w-6xl gap-x-12 py-8 lg:py-14', 'grid lg:grid-cols-[minmax(0,1fr)_17rem]' => $hasMenu])>
        @if($hasMenu)
            {{-- Telefon: menu działu zwinięte nad treścią --}}
            <details data-no-print class="group mb-8 rounded-2xl bg-white ring-1 ring-zinc-200 lg:hidden">
                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 [&::-webkit-details-marker]:hidden">
                    <span class="min-w-0">
                        <span class="block text-[11px] font-semibold tracking-widest text-zinc-500 uppercase">{{ __('W tym dziale') }}</span>
                        <span class="block truncate font-semibold text-zinc-900">{{ $page->localizedTitle() }}</span>
                    </span>
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 transition group-open:rotate-180" aria-hidden="true">▾</span>
                </summary>
                <div class="border-t border-zinc-100 p-2">
                    @include('pages.partials.section-menu')
                </div>
            </details>
        @endif

        <div class="max-w-3xl">
            @if(app()->getLocale() === 'en' && ! $page->hasEnglish())
                <p class="mb-8 rounded-2xl bg-zinc-100 px-5 py-4 text-sm text-zinc-600">{{ __('Ta strona jest dostępna tylko po polsku.') }}</p>
            @endif

            @if($page->localizedLead())
                <p class="mb-10 border-l-4 border-[var(--page-accent,var(--color-sky-club))] pl-5 text-lg leading-relaxed font-medium text-zinc-800 sm:pl-6 sm:text-xl">{{ $page->localizedLead() }}</p>
            @endif

            @if(filled($page->highlights))
                <dl @class(['mb-10 grid gap-2 sm:gap-3', [1 => 'sm:grid-cols-1', 2 => 'sm:grid-cols-2'][count($page->highlights)] ?? 'sm:grid-cols-3'])>
                    @foreach($page->highlights as $highlight)
                        <div class="flex items-center gap-4 rounded-2xl bg-[var(--page-primary,var(--color-ink))] px-5 py-4 text-white sm:flex-col sm:items-start sm:gap-1 sm:py-5">
                            <dt class="order-2 text-sm leading-snug text-white/75">{{ $highlight['label'] ?? '' }}</dt>
                            <dd class="order-1 shrink-0 font-display text-3xl font-bold whitespace-nowrap text-[var(--page-accent,var(--color-sky-club))] sm:text-4xl">{{ $highlight['value'] ?? '' }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif

            <div class="prose-club" @if(app()->getLocale() === 'en' && blank($page->content_en)) lang="pl" @endif>
                {!! \App\Support\DeadLinks::strip($page->localizedContent()) !!}
            </div>

            @if($page->children->isNotEmpty())
                <ul class="mt-10 grid gap-4 sm:grid-cols-2">
                    @foreach($page->children as $child)
                        <li>
                            <a href="{{ route('pages.show', $child) }}" style="{{ $child->themeStyle() }}" class="group block h-full overflow-hidden rounded-3xl bg-[var(--page-primary,var(--color-ink))] text-white ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-xl">
                                @if($child->image_path)
                                    <img src="{{ \App\Support\Media::url($child->image_path) }}" alt="" loading="lazy" @class(['block w-full', 'h-auto' => $child->image_is_banner, 'aspect-video object-cover' => ! $child->image_is_banner])>
                                @endif
                                <span class="block border-t-4 border-[var(--page-accent,var(--color-sky-club))] p-5">
                                    <span class="flex items-center justify-between gap-3 font-display text-2xl font-bold uppercase">{{ $child->localizedTitle() }} <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span></span>
                                    @if($child->localizedLead())
                                        <span class="mt-2 line-clamp-3 block text-sm text-white/75">{{ $child->localizedLead() }}</span>
                                    @endif
                                    @unless($child->is_published)
                                        <span class="mt-3 inline-block rounded-full bg-amber-300 px-2.5 py-0.5 text-xs font-semibold text-amber-950">{{ __('projekt') }}</span>
                                    @endunless
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div data-no-print class="mt-12 flex justify-end border-t border-zinc-200 pt-6">
                @livewire('like-button', ['type' => 'page', 'id' => $page->id], key('page-like-'.$page->id))
            </div>
        </div>

        @if($hasMenu)
            <aside data-no-print class="hidden lg:block">
                <nav class="sticky top-28" aria-label="{{ __('W tym dziale') }}">
                    <p class="eyebrow mb-4">{{ __('W tym dziale') }}</p>
                    @include('pages.partials.section-menu')
                </nav>
            </aside>
        @endif
    </div>
</article>
@endsection
