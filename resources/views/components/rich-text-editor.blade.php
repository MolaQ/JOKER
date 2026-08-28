@props([
    'model' => null,
    'name' => null,
    'value' => '',
    'placeholder' => 'Wpisz treść...',
    'height' => 'min-h-[180px]',
])

@php
    $toolbarButton = 'rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50';
@endphp

@if($model)
    <div
        wire:ignore
        x-data="{ content: $wire.entangle('{{ $model }}').live }"
        x-init="$refs.editor.innerHTML = content || ''"
        x-effect="if (($refs.editor.innerHTML || '') !== (content || '')) { $refs.editor.innerHTML = content || '' }"
        class="space-y-2"
    >
        <div class="flex flex-wrap gap-2">
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('bold'); $refs.editor.focus()"><strong>B</strong></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('italic'); $refs.editor.focus()"><em>I</em></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('underline'); $refs.editor.focus()"><u>U</u></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertUnorderedList'); $refs.editor.focus()">• Lista</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertOrderedList'); $refs.editor.focus()">1. Lista</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('formatBlock', false, 'h3'); $refs.editor.focus()">Nagłówek</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('removeFormat'); $refs.editor.focus()">Wyczyść</button>
        </div>

        <div
            x-ref="editor"
            contenteditable="true"
            @input="content = $refs.editor.innerHTML"
            data-placeholder="{{ $placeholder }}"
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
        <div class="flex flex-wrap gap-2">
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('bold'); $refs.editor.focus()"><strong>B</strong></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('italic'); $refs.editor.focus()"><em>I</em></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('underline'); $refs.editor.focus()"><u>U</u></button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertUnorderedList'); $refs.editor.focus()">• Lista</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertOrderedList'); $refs.editor.focus()">1. Lista</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('formatBlock', false, 'h3'); $refs.editor.focus()">Nagłówek</button>
            <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('removeFormat'); $refs.editor.focus()">Wyczyść</button>
        </div>

        <div
            x-ref="editor"
            contenteditable="true"
            @input="content = $refs.editor.innerHTML"
            data-placeholder="{{ $placeholder }}"
            class="{{ $height }} w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-blue-500 empty:before:pointer-events-none empty:before:text-gray-400 empty:before:content-[attr(data-placeholder)]"
        ></div>

        @if($name)
            <textarea name="{{ $name }}" x-model="content" class="hidden"></textarea>
        @endif
    </div>
@endif
