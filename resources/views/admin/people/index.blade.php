@extends('admin.layout')

@section('title', 'Osoby w drużynach')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Osoby w drużynach</h1>
    </div>

    <div class="admin-card">
        @livewire('admin.user-directory')
    </div>
</div>
@endsection
