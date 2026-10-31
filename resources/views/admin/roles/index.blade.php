@extends('admin.layout')

@section('title', 'Role i uprawnienia')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900">Role i uprawnienia</h1>
    <p class="mt-2 mb-6 max-w-3xl text-sm text-gray-700">Zaznacz, co może każda rola. Konto może mieć kilka ról, wtedy ma uprawnienia ich wszystkich. Pojedynczemu kontu można też coś dodatkowo nadać albo odebrać, niezależnie od ról.</p>

    @livewire('admin.role-manager')
</div>
@endsection
