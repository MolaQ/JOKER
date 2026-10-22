@extends('admin.layout')

@section('title', 'Plan treningów')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Plan treningów</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Stały tygodniowy plan zajęć drużyn: dzień, godziny i miejsce (siłownia, boisko 1, 2 albo 3). Trener i członek sztabu układają plan swoich drużyn, szef i administrator wszystkich.</p>
    </div>

    @livewire('admin.training-planner')
</div>
@endsection
