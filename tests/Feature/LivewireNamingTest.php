<?php

use Illuminate\Support\Str;
use Livewire\Component;

// W Livewire 4 $wire.nazwa zwraca właściwość, jeśli istnieje, więc metoda o tej samej nazwie jest niewywoływalna z widoku.
test('no livewire component has an action named like one of its public properties', function () {
    $clashes = collect(glob(app_path('Livewire/{,*/,*/*/}*.php'), GLOB_BRACE))
        ->map(fn (string $path) => 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($path, app_path().'/')))
        ->filter(fn (string $class) => is_subclass_of($class, Component::class) && ! (new ReflectionClass($class))->isAbstract())
        ->flatMap(function (string $class) {
            $reflection = new ReflectionClass($class);
            $properties = collect($reflection->getProperties(ReflectionProperty::IS_PUBLIC))->map->getName();

            return collect($reflection->getMethods(ReflectionMethod::IS_PUBLIC))
                ->filter(fn (ReflectionMethod $method) => $method->getDeclaringClass()->getName() === $class)
                ->map->getName()
                ->intersect($properties)
                ->map(fn (string $name) => class_basename($class).'::'.$name);
        });

    expect($clashes->values()->all())->toBe([]);
});
