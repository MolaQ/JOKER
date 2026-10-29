@extends('admin.layout')

@section('title', 'Wychowankowie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Wychowankowie</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Zawodnicy, którzy zaczynali w klubie i grają wyżej: w PlusLidze, w reprezentacji albo w rozgrywkach centralnych. Lista jest na stronie „Wychowankowie” i na stronie głównej.</p>
    </div>

    @livewire('admin.alumni-manager')
</div>
@endsection
