@extends('layouts.public')

@section('title', __('Wiadomości'))

@section('robots', 'noindex, nofollow')

@section('content')
<div class="container-site py-10 sm:py-14">
    <h1 class="heading-lg mb-6">{{ __('Wiadomości') }}</h1>
    @livewire('messages')
</div>
@endsection
