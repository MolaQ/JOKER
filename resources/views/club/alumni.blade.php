@extends('layouts.public')

@section('title', __('Wychowankowie'))
@section('description', __('Wychowankowie :club, którzy grają w PlusLidze, w reprezentacji i w rozgrywkach centralnych.', ['club' => $site['club_name_genitive']]))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Od :club do PlusLigi', ['club' => $site['club_name_genitive']]) }}</p>
    <h1 class="heading-xl mt-4">{{ __('Wychowankowie') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Zaczynali w naszych grupach młodzieżowych, dziś grają na najwyższym poziomie. Każdy z nich przeszedł przez treningi w Pile.') }}</p>
</section>

<section class="container-site pb-16">
    @if($alumni->isEmpty())
        <p class="text-zinc-500">{{ __('Lista wychowanków jest w przygotowaniu.') }}</p>
    @else
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($alumni as $alumnus)
                <article @class(['card overflow-hidden', 'ring-2 ring-sky-club sm:col-span-2 lg:col-span-1' => $alumnus->is_featured])>
                    <div class="relative aspect-[4/3] bg-zinc-100">
                        @if($alumnus->photoUrl())
                            <img src="{{ $alumnus->photoUrl() }}" alt="{{ $alumnus->name }}" loading="lazy" decoding="async" class="absolute inset-0 h-full w-full object-cover object-top">
                        @else
                            <img src="{{ $site->image('logo', asset('img/logo-256.webp')) }}" alt="" class="absolute inset-0 m-auto h-1/2 w-1/2 object-contain opacity-30">
                        @endif
                        @if($alumnus->level)
                            <span class="chip absolute top-4 left-4 bg-sky-club text-white">{{ $alumnus->level }}</span>
                        @endif
                    </div>
                    <div class="p-6">
                        <h2 class="font-display text-2xl font-bold uppercase">
                            @if($alumnus->link())
                                <a href="{{ $alumnus->link() }}" class="hover:text-sky-club-dark" @unless($alumnus->player) target="_blank" rel="noopener" @endunless>{{ $alumnus->name }}</a>
                            @else
                                {{ $alumnus->name }}
                            @endif
                        </h2>
                        <p class="mt-1 text-sm text-zinc-500">{{ collect([$alumnus->position, $alumnus->current_club, $alumnus->years_in_club ? __('w :club', ['club' => $site['club_name_locative']]).' '.$alumnus->years_in_club : null])->filter()->implode(' · ') }}</p>
                        @if($alumnus->achievementList())
                            <ul class="mt-4 space-y-1 text-sm text-zinc-700">
                                @foreach($alumnus->achievementList() as $achievement)
                                    <li class="flex gap-2"><span class="text-sky-club" aria-hidden="true">★</span> {{ $achievement }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    <div class="mt-12 rounded-3xl bg-ink p-8 text-white lg:p-10">
        <h2 class="font-display text-3xl font-bold uppercase">{{ __('Następny możesz być Ty') }}</h2>
        <p class="mt-3 max-w-xl text-white/80">{{ __('Zapraszamy chłopców od 7. roku życia. Przyjdź na trening próbny.') }}</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('pages.show', 'nabor') }}" class="btn bg-white text-ink hover:bg-zinc-100">{{ __('Nabór') }} →</a>
            <a href="{{ route('trainings.index') }}" class="btn-ghost">{{ __('Plan treningów') }}</a>
        </div>
    </div>
</section>
@endsection
