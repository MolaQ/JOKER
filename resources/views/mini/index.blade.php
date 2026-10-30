@extends('layouts.public')

@section('title', __('Minisiatkówka'))
@section('description', __('Minisiatkówka w :club: grupy, dzieci, wyjazdy na turnieje i wyniki.', ['club' => $site['club_name']]))

@section('content')
<section class="container-site py-16 lg:py-24">
    <p class="eyebrow">{{ __('Pierwsze kroki') }}</p>
    <h1 class="heading-xl mt-3">{{ __('Minisiatkówka') }}</h1>

    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @forelse($groups as $group)
            <x-mini-group-tile :group="$group" />
        @empty
            <p class="text-zinc-500">{{ __('Grupy minisiatkówki pojawią się wkrótce.') }}</p>
        @endforelse
    </div>

    <div class="mt-16 grid gap-10 lg:grid-cols-2">
        <div>
            <h2 class="heading-md">{{ __('Najbliższe wyjazdy') }}</h2>
            <div class="mt-5 space-y-3">
                @forelse($upcoming as $trip)
                    <x-mini-trip :trip="$trip" :show-parent-info="$showParentInfo" />
                @empty
                    <p class="text-sm text-zinc-500">{{ __('Nie ma zaplanowanych wyjazdów.') }}</p>
                @endforelse
            </div>
        </div>
        <div>
            <h2 class="heading-md">{{ __('Ostatnie wyniki') }}</h2>
            <ul class="mt-5 divide-y divide-zinc-100 overflow-hidden rounded-2xl bg-white ring-1 ring-zinc-200">
                @forelse($results as $team)
                    <li class="flex items-center gap-4 px-4 py-3">
                        <span @class(['w-12 text-center font-display text-3xl font-bold', 'text-amber-500' => $team->place === 1, 'text-zinc-400' => $team->place === 2, 'text-orange-600' => $team->place === 3, 'text-zinc-800' => $team->place > 3])>{{ $team->place }}.</span>
                        <div class="min-w-0">
                            <p class="font-semibold text-zinc-900">{{ $team->trip->title }}</p>
                            <p class="text-sm text-zinc-500">{{ collect([$team->group?->name, $team->name, $team->teams_count ? __('na :count drużyn', ['count' => $team->teams_count]) : null, $team->trip->starts_on->translatedFormat('j M Y')])->filter()->implode(' · ') }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-3 text-sm text-zinc-500">{{ __('Wyniki pojawią się po pierwszym turnieju.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</section>
@endsection
