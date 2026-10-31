@extends('layouts.public')

@section('title', __('Drużyny'))
@section('description', __('Drużyny siatkarskie :club: składy, trenerzy, terminarze i tabele.', ['club' => $site['club_name']]))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Sezon') }} {{ \App\Models\Season::current()->value('name') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Drużyny') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Każda drużyna ma swój skład, sztab i terminarz. Kliknij, aby zobaczyć zawodników i trenerów.') }}</p>
</section>

<section class="container-site">
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($teams as $team)
            <a href="{{ route('teams.show', $team) }}" class="card card-hover group overflow-hidden" data-reveal>
                <x-photo :src="\App\Support\Media::teamPhoto($team)" :alt="__('Drużyna').' '.$team->name" sizes="(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw" class="aspect-[4/3] w-full">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink/70 to-transparent"></div>
                    @if($team->birthYearsLabel())
                        <span class="chip absolute top-4 left-4 bg-white/90">{{ $team->birthYearsLabel() }}</span>
                    @endif
                </x-photo>
                <div class="p-6">
                    <h2 class="font-display text-2xl font-bold uppercase group-hover:text-sky-club-dark">{{ $team->name }}</h2>
                    <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-zinc-500">
                        <span><strong class="text-zinc-900">{{ $team->active_players_count }}</strong> {{ __('zawodników') }}</span>
                        @if($team->trainer)
                            <span>{{ __('Trener') }}: <strong class="text-zinc-900">{{ $team->trainer->name }}</strong></span>
                        @endif
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</section>

@if($miniGroups->isNotEmpty())
<section class="container-site pt-16 pb-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="eyebrow">{{ __($site['mini_eyebrow']) }}</p>
            <h2 class="heading-md mt-2">{{ __($site['mini_title']) }}</h2>
        </div>
        <a href="{{ route('mini.index') }}" class="link-underline pb-1 text-sm font-semibold">{{ __('Wszystko o minisiatkówce') }} →</a>
    </div>
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach($miniGroups as $miniGroup)
            <x-mini-group-tile :group="$miniGroup" class="min-h-44! p-4! sm:min-h-52!" />
        @endforeach
    </div>
</section>
@endif
@endsection
