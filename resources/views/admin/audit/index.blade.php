@extends('admin.layout')

@section('title', 'Dziennik zmian')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Dziennik zmian</h1>
        <p class="mt-2 text-sm text-gray-700">Kto, co i kiedy dodał, zmienił albo usunął na stronie i w panelu, z wartościami przed i po. Wpisy są przechowywane przez rok.</p>
    </div>

    @livewire('admin.audit-log-viewer')
</div>
@endsection
