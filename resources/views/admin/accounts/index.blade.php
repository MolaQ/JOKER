@extends('admin.layout')

@section('title', 'Konta do aktywacji')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Konta do aktywacji</h1>
        <p class="mt-2 max-w-3xl text-sm text-gray-700">Nowe konto działa dopiero, gdy powiążesz je z profilem: zawodnika z jego profilem w kadrze, rodzica z dzieckiem, trenera z drużyną. Konto kibica aktywuje się samo po potwierdzeniu e-maila.</p>
    </div>

    @livewire('admin.account-activation')
</div>
@endsection
