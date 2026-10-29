@extends('layouts.public')

@php use App\Models\TrainingSession; @endphp

@section('title', $team ? __('Plan treningów').' - '.$team->name : __('Plan treningów'))
@section('description', __('Stały tygodniowy plan treningów drużyn :club: dni, godziny i miejsca zajęć.', ['club' => $site['club_name_genitive']]))

@section('content')
<section class="container-site pt-14 pb-10 lg:pt-20">
    <p class="eyebrow">{{ __('Treningi') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Plan treningów') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Stały plan zajęć w tygodniu. Chcesz przyjść na trening próbny? Wybierz grupę z rocznika dziecka i zadzwoń albo napisz do nas.') }}</p>

    @if($teams->isNotEmpty())
        <nav class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('Drużyny') }}">
            <a href="{{ route('trainings.index') }}" @class(['chip', 'bg-ink text-white' => ! $team])>{{ __('Wszystkie') }}</a>
            @foreach($teams as $option)
                <a href="{{ route('trainings.index', ['team' => $option->slug]) }}" @class(['chip', 'bg-ink text-white' => $team?->is($option)])>{{ $option->name }}</a>
            @endforeach
        </nav>
    @endif
</section>

<section class="container-site pb-16">
    @if($days->isEmpty())
        <p class="text-zinc-500">{{ __('Plan treningów jest w przygotowaniu. Zadzwoń, a podpowiemy, kiedy przyjść.') }}</p>
    @else
        <div @class(['grid gap-3 sm:grid-cols-2', match ($days->count()) { 5 => 'lg:grid-cols-5', 6 => 'lg:grid-cols-3 xl:grid-cols-6', 7 => 'lg:grid-cols-4 xl:grid-cols-7', default => 'lg:grid-cols-4' }])>
            @foreach($days as $weekday => $sessions)
                <div class="card p-4">
                    <h2 class="font-display text-xl font-bold uppercase">{{ __(TrainingSession::WEEKDAYS[$weekday]) }}</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach($sessions as $session)
                            <li class="rounded-xl bg-zinc-50 p-3 ring-1 ring-zinc-200/70">
                                <p class="font-semibold whitespace-nowrap tabular-nums">{{ $session->timeRange() }}</p>
                                <a href="{{ route('teams.show', $session->team) }}" class="text-sm font-medium text-sky-club-dark hover:underline">{{ $session->team->name }}</a>
                                <p class="mt-1 flex items-center gap-2 text-sm text-zinc-600"><x-venue-icon :venue="$session->venue" small />{{ $session->venueLabel() }}</p>
                                @if($session->note)<p class="mt-1 text-xs text-zinc-500">{{ $session->note }}</p>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-10 flex flex-wrap gap-3">
        <a href="{{ route('contact.index', ['subject' => __('Trening próbny')]) }}" class="btn-primary">{{ __('Zapisz na trening próbny') }}</a>
        @if($site['trainings_extra_label'] && $site['trainings_extra_url'])
            <a href="{{ $site->href($site['trainings_extra_url']) }}" class="btn-dark">{{ $site['trainings_extra_label'] }}</a>
        @endif
    </div>
</section>
@endsection
