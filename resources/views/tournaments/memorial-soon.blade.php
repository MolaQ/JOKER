@extends('layouts.public')

@section('title', __('Memoriał im. Włodzimierza Winklera'))
@section('description', __('Memoriał im. Włodzimierza Winklera - turniej siatkówki młodzieżowej organizowany przez Joker Piła.'))

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-gradient-to-br from-club-navy via-club-navy to-sky-club-dark pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10 opacity-60" aria-hidden="true"></div>
    <div class="container-site pt-20 pb-16 lg:pt-28 lg:pb-24">
        <p class="eyebrow">🏆 {{ __('Turniej młodzieżowy w Pile') }}</p>
        <h1 class="heading-xl mt-2 max-w-4xl">{{ __('Memoriał im. Włodzimierza Winklera') }}</h1>
        <p class="mt-5 max-w-2xl text-lg text-white/80">{{ __('Strona najbliższej edycji pojawi się tu wkrótce: drużyny, grupy, wyniki na żywo i informator dla gości.') }}</p>
    </div>
</section>
@endsection
