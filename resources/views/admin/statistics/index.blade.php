@extends('admin.layout')

@section('title', 'Statystyki odwiedzin')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Statystyki odwiedzin</h1>
        <p class="mt-2 text-sm text-gray-700">Które strony, mecze i zakładki przyciągają kibiców i jak długo je czytają.</p>
    </div>

    @livewire('admin.site-statistics')
</div>
@endsection
