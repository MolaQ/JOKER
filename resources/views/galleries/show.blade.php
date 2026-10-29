@extends('layouts.public')

@section('title', $gallery->title)
@section('description', \Illuminate\Support\Str::limit($gallery->description ?: __('Zdjęcia: :title. Galeria klubu siatkarskiego :club.', ['title' => $gallery->title, 'club' => $site['club_name']]), 160))
@section('og_image', $gallery->photos->first()?->url() ?? '')

@push('structured-data')
    {!! \App\Support\Seo::jsonLd($structuredData) !!}
    {!! \App\Support\Seo::jsonLd(\App\Support\Seo::breadcrumbs([[__('Galerie'), route('galleries.index')], [$gallery->title, route('galleries.show', $gallery)]])) !!}
@endpush

@section('content')
<section class="container-site pt-14 pb-8 lg:pt-20">
    <a href="{{ route('galleries.index') }}" class="text-sm font-semibold text-sky-club-dark hover:underline">← {{ __('Wszystkie galerie') }}</a>
    <p class="eyebrow mt-6">{{ $gallery->displayDate()?->translatedFormat('j F Y') }}@if($gallery->team) · {{ $gallery->team->name }}@endif</p>
    @if($gallery->matchUrl())
        <p class="mt-3"><a href="{{ $gallery->matchUrl() }}" class="inline-flex items-center gap-2 rounded-full bg-sky-club/10 px-4 py-2 text-sm font-semibold text-sky-club-dark hover:bg-sky-club/20">🏐 {{ __('Mecz') }}: {{ $gallery->match->mediaLabel() }} →</a></p>
    @endif
</section>

<section class="container-site pb-16">
    @php $canTag = \App\Livewire\PhotoTagProposal::canPropose(auth()->user()); @endphp
    <x-match-gallery :gallery="$gallery" :can-tag="$canTag" heading="h1" />

    @php $people = $gallery->photos->flatMap->visibleTags()->unique(fn ($tag) => $tag->taggable_type.$tag->taggable_id)->filter(fn ($tag) => $tag->url()); @endphp
    @if($people->isNotEmpty())
        <div class="mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-zinc-500">{{ __('Na zdjęciach') }}</h2>
            <p class="mt-3 flex flex-wrap gap-2">
                @foreach($people as $tag)
                    <a href="{{ $tag->url() }}" class="rounded-full bg-zinc-100 px-3 py-1 text-sm font-medium text-zinc-800 hover:bg-sky-club/15">{{ $tag->label() }}</a>
                @endforeach
            </p>
        </div>
    @endif
    @if($canTag)
        <p class="mt-4 text-sm text-zinc-500">{{ __('Rozpoznajesz siebie lub swoje dziecko? Otwórz zdjęcie i kliknij „Zaproponuj oznaczenie”.') }}</p>
        @livewire('photo-tag-proposal')
    @endif
</section>
@endsection
