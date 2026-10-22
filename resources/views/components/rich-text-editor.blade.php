@props([
    'model' => null,
    'name' => null,
    'value' => '',
    'placeholder' => 'Wpisz treść...',
    'height' => 'min-h-[180px]',
    // Autozapis: treść trafia do Livewire dopiero po wyjściu z edytora, a nie przy każdym znaku.
    'saveOnBlur' => false,
])

@if($model)
    <div
        wire:ignore
        x-data="{ content: $wire.entangle('{{ $model }}'){{ $saveOnBlur ? '' : '.live' }} }"
        @if($saveOnBlur) x-on:focusout="if (! $el.contains($event.relatedTarget)) $nextTick(() => $wire.$commit())" @endif
        x-init="$refs.editor.innerHTML = content || ''"
        x-effect="if (($refs.editor.innerHTML || '') !== (content || '')) { $refs.editor.innerHTML = content || '' }"
        class="space-y-2"
    >
        <x-rich-text-toolbar />

        <div
            x-ref="editor"
            contenteditable="true"
            @input="content = $refs.editor.innerHTML"
            data-placeholder="{{ __($placeholder) }}"
            class="{{ $height }} w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 empty:before:pointer-events-none empty:before:text-gray-400 empty:before:content-[attr(data-placeholder)]"
        ></div>
    </div>
@else
    <div
        x-data="{ content: @js((string) $value) }"
        x-init="$refs.editor.innerHTML = content || ''"
        x-effect="if (($refs.editor.innerHTML || '') !== (content || '')) { $refs.editor.innerHTML = content || '' }"
        class="space-y-2"
    >
        <x-rich-text-toolbar />

        <div
            x-ref="editor"
            contenteditable="true"
            @input="content = $refs.editor.innerHTML"
            data-placeholder="{{ __($placeholder) }}"
            class="{{ $height }} w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 empty:before:pointer-events-none empty:before:text-gray-400 empty:before:content-[attr(data-placeholder)]"
        ></div>

        @if($name)
            <textarea name="{{ $name }}" x-model="content" class="hidden"></textarea>
        @endif
    </div>
@endif
