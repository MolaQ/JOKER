@extends('layouts.public')

@section('title', __('Zdjęcia'))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Podziękowania') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Zdjęcia') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Tam, gdzie klub nie dodał jeszcze własnych zdjęć, używamy darmowych fotografii z serwisu') }} <a href="https://unsplash.com/license" class="font-semibold text-sky-club-dark underline" target="_blank" rel="noopener">Unsplash</a>. {{ __('Dziękujemy autorom!') }}</p>
</section>

<section class="container-site">
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach(config('media.photos') as $photo)
            <a href="{{ $photo['page'] }}" target="_blank" rel="noopener" class="card card-hover group overflow-hidden">
                <x-photo :src="$photo['url']" :alt="__('Zdjęcie').': '.$photo['author']" sizes="(min-width: 1024px) 25vw, 50vw" class="aspect-square w-full" />
                <p class="p-4 text-sm">{{ __('Fot.') }} <span class="font-semibold group-hover:text-sky-club-dark">{{ $photo['author'] }}</span> / Unsplash</p>
            </a>
        @endforeach
    </div>
</section>
@endsection
