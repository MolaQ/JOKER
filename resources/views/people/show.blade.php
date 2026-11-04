@extends('layouts.public')

@section('title', $person->name)
@section('description', __(':name - :roles w klubie siatkarskim :club.', ['name' => $person->name, 'roles' => $person->rolesLabel(), 'club' => $site['club_name']]))
@unless($person->hasPublicProfile())
    @push('head')
        <meta name="robots" content="noindex, nofollow">
    @endpush
@endunless

@section('content')
<section class="relative isolate -mt-16 overflow-hidden bg-ink pt-16 text-white lg:-mt-20 lg:pt-20">
    <div class="net-pattern absolute inset-0 -z-10" aria-hidden="true"></div>

    <div class="container-site grid items-end gap-10 pt-16 pb-14 lg:grid-cols-[auto_1fr] lg:pt-24">
        <x-avatar :photo="$person->avatar_path" :name="$person->name" class="aspect-[4/5] w-40 rounded-3xl text-6xl ring-4 ring-sky-club/40 lg:w-56" />
        <div>
            <p class="eyebrow">{{ $person->rolesLabel() ?: __('Społeczność klubu') }}</p>
            <h1 class="heading-xl mt-3 flex flex-wrap items-center gap-x-4">{{ $person->name }} <x-message-button :to="$person" /></h1>
            {{-- Wyświetlenia i serduszka profili osób widzą tylko zalogowani. --}}
            @auth
                <div class="mt-4 flex flex-wrap items-center gap-4">
                    <x-view-count :model="$person" class="text-white/70" />
                    @livewire('like-button', ['type' => 'person', 'id' => $person->id, 'dark' => true], key('person-like-'.$person->id))
                </div>
            @endauth
        </div>
    </div>
</section>

<section class="container-site grid gap-8 py-14 lg:grid-cols-3">
    <div class="card p-6 lg:self-start">
        <h2 class="heading-md">{{ __('W klubie') }}</h2>
        <ul class="mt-5 space-y-3">
            @foreach($person->children as $child)
                <li>
                    <a href="{{ route('players.show', $child) }}" class="group flex items-center justify-between gap-4 rounded-2xl bg-zinc-50 p-4 transition hover:bg-sky-club/10">
                        <span>
                            <span class="block text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ __('Rodzic zawodnika') }}</span>
                            <span class="block font-semibold">{{ $child->full_name }}@if($child->team) <span class="font-normal text-zinc-500">· {{ $child->team->name }}</span>@endif</span>
                        </span>
                        <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
            @foreach($person->coordinatedTeams as $team)
                <li>
                    <a href="{{ route('teams.show', $team) }}" class="group flex items-center justify-between gap-4 rounded-2xl bg-zinc-50 p-4 transition hover:bg-sky-club/10">
                        <span>
                            <span class="block text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ __('Koordynator drużyny') }}</span>
                            <span class="block font-semibold">{{ $team->name }}</span>
                        </span>
                        <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span>
                    </a>
                </li>
            @endforeach
            @if($person->children->isEmpty() && $person->coordinatedTeams->isEmpty())
                <li class="rounded-2xl bg-zinc-50 p-4">
                    <span class="block text-xs font-semibold tracking-widest text-sky-club-dark uppercase">{{ __('Rola') }}</span>
                    <span class="block font-semibold">{{ $person->rolesLabel() ?: __('Społeczność klubu') }}</span>
                </li>
            @endif
        </ul>

        @if($socialProfiles = $person->socialProfiles())
            <div class="mt-6 flex flex-wrap gap-2">
                @foreach($socialProfiles as $profile)
                    <a href="{{ $profile['url'] }}" target="_blank" rel="noopener nofollow" class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-700 transition hover:bg-sky-club hover:text-white" aria-label="{{ $profile['label'] }}" title="{{ $profile['label'] }}"><x-social-icon :network="$profile['icon']" class="h-4 w-4" /></a>
                @endforeach
            </div>
        @endif
    </div>

    @if(filled(strip_tags((string) $person->additional_info)) || $mentions->isNotEmpty())
        <div class="space-y-8 lg:col-span-2">
            @if(filled(strip_tags((string) $person->additional_info)))
                <div class="card p-8">
                    <h2 class="heading-md">{{ __('Sylwetka') }}</h2>
                    <div class="prose-club mt-5">
                        {!! strip_tags($person->additional_info, '<p><br><strong><em><b><i><ul><ol><li>') !!}
                    </div>
                </div>
            @endif
            @if($mentions->isNotEmpty())
                <x-mentions-list :articles="$mentions" :name="$person->name" />
            @endif
        </div>
    @endif
</section>
@endsection
