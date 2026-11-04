@extends('admin.layout')

@section('title', 'Sztab na stronie')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Sztab na stronie</h1>
    </div>

    <div class="admin-card">
        @livewire('admin.staff-order')
    </div>
</div>
@endsection
