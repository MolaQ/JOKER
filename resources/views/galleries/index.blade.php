@extends('layouts.public')

@section('title', $team ? __('Galerie').' - '.$team->name : __('Galerie'))
@section('description', __('Zdjęcia z meczów, turniejów i wydarzeń klubu siatkarskiego Joker Piła.'))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Zdjęcia') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Galerie') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Zdjęcia z meczów, turniejów i życia klubu. Kliknij galerię, aby zobaczyć wszystkie zdjęcia i oznaczonych zawodników.') }}</p>

    <nav class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('Drużyny') }}">
        <a href="{{ route('galleries.index') }}" @class(['chip', 'bg-ink text-white' => ! $team])>{{ __('Wszystkie') }}</a>
        @foreach($teams as $option)
            <a href="{{ route('galleries.index', ['druzyna' => $option->slug]) }}" @class(['chip', 'bg-ink text-white' => $team?->is($option)])>{{ $option->name }}</a>
        @endforeach
    </nav>
</section>

<section class="container-site pb-16">
    @if($galleries->isEmpty())
        <p class="text-zinc-500">{{ __('Nie ma jeszcze galerii.') }}</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($galleries as $gallery)
                @php $cover = $gallery->photos->first(); @endphp
                <a href="{{ route('galleries.show', $gallery) }}" class="card card-hover group overflow-hidden">
                    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                        @if($cover)
                            <img src="{{ $cover->url() }}" alt="{{ $gallery->title }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @endif
                        <span class="chip absolute top-4 left-4 bg-white/90">📷 {{ $gallery->photos_count }}</span>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-zinc-500">{{ $gallery->displayDate()?->translatedFormat('j F Y') }}@if($gallery->team) · {{ $gallery->team->name }}@endif</p>
                        <h2 class="mt-1 font-display text-xl font-bold uppercase group-hover:text-sky-club-dark">{{ $gallery->title }}</h2>
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-10">{{ $galleries->links() }}</div>
    @endif
</section>
@endsection
