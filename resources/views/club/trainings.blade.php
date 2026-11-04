@extends('layouts.public')

@section('title', $single ? __('Plan treningów').' - '.$single['name'] : __('Plan treningów'))
@section('description', __('Stały tygodniowy plan treningów drużyn :club: dni, godziny i miejsca zajęć.', ['club' => $site['club_name_genitive']]))

@section('content')
<section class="container-site pt-14 pb-8 lg:pt-20">
    <p class="eyebrow">{{ __('Treningi') }}</p>
    <h1 class="heading-xl mt-4">{{ __('Plan treningów') }}</h1>
    <p class="mt-6 max-w-2xl text-lg text-zinc-600">{{ __('Stały plan zajęć w tygodniu. Chcesz przyjść na trening próbny? Wybierz grupę z rocznika dziecka i zadzwoń albo napisz do nas.') }}</p>
</section>

<section class="container-site pb-16">
    @if($sessions->isEmpty())
        <p class="text-zinc-500">{{ __('Plan treningów jest w przygotowaniu. Zadzwoń, a podpowiemy, kiedy przyjść.') }}</p>
    @else
        <x-training-week :sessions="$sessions" :selected="$selected" />
    @endif

    <div class="mt-10 flex flex-wrap gap-3">
        <a href="{{ route('contact.index', ['subject' => __('Trening próbny')]) }}" class="btn-primary">{{ __('Zapisz na trening próbny') }}</a>
        @if($site['trainings_extra_label'] && $site['trainings_extra_url'])
            <a href="{{ $site->href($site['trainings_extra_url']) }}" class="btn-dark">{{ $site['trainings_extra_label'] }}</a>
        @endif
    </div>
</section>
@endsection
