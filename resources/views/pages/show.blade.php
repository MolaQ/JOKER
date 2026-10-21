@extends('layouts.public')

@section('title', $page->localizedTitle())
@section('description', Str::limit(strip_tags((string) ($page->localizedLead() ?: $page->localizedContent())), 155))

@section('content')
<article>
    @if($page->image_path)
        <header class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
            <x-photo :src="\App\Support\Media::url($page->image_path)" :alt="$page->localizedTitle()" eager sizes="100vw" width="1800" height="900" class="absolute inset-0 -z-20 h-full w-full opacity-50" />
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-ink via-ink/60 to-ink/30"></div>
            <div class="container-site max-w-6xl pt-24 pb-14 lg:pt-36">
                <p class="eyebrow">{{ $site['club_name'] }}</p>
                <h1 class="mt-4 font-display text-4xl leading-[1.05] font-bold uppercase sm:text-6xl">{{ $page->localizedTitle() }}@if($page->hasEnglish()) <x-english-badge class="ml-1" />@endif</h1>
            </div>
        </header>
    @else
        <header class="container-site max-w-6xl pt-14 lg:pt-20">
            <p class="eyebrow">{{ $site['club_name'] }}</p>
            <h1 class="heading-xl mt-4 text-balance">{{ $page->localizedTitle() }}@if($page->hasEnglish()) <x-english-badge class="ml-1" />@endif</h1>
        </header>
    @endif

    <div class="container-site grid max-w-6xl gap-12 py-14 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="max-w-3xl">
            @if(app()->getLocale() === 'en' && ! $page->hasEnglish())
                <p class="mb-8 rounded-2xl bg-zinc-100 px-5 py-4 text-sm text-zinc-600">{{ __('Ta strona jest dostępna tylko po polsku.') }}</p>
            @endif

            @if($page->localizedLead())
                <p class="mb-10 border-l-4 border-sky-club pl-6 text-xl leading-relaxed font-medium text-zinc-800">{{ $page->localizedLead() }}</p>
            @endif

            <div class="prose-club" @if(app()->getLocale() === 'en' && blank($page->content_en)) lang="pl" @endif>
                {!! $page->localizedContent() !!}
            </div>

            <div class="mt-12 flex justify-end border-t border-zinc-200 pt-6">
                @livewire('like-button', ['type' => 'page', 'id' => $page->id], key('page-like-'.$page->id))
            </div>
        </div>

        @if($otherPages->isNotEmpty())
            <aside class="lg:pt-2">
                <p class="eyebrow mb-4">{{ __('Zobacz też') }}</p>
                <ul class="space-y-1">
                    @foreach($otherPages as $otherPage)
                        <li><a href="{{ route('pages.show', $otherPage) }}" class="block rounded-xl px-4 py-3 font-semibold text-zinc-700 ring-1 ring-zinc-200 transition hover:bg-white hover:text-sky-club-dark">{{ $otherPage->localizedTitle() }}@if($otherPage->hasEnglish()) <x-english-badge class="ml-1" />@endif <span aria-hidden="true">→</span></a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </div>
</article>
@endsection
