@props([
    'model' => null,
    'name' => null,
    'value' => '',
    'placeholder' => 'Wpisz treść...',
    'height' => 'min-h-[180px]',
    // Autozapis: treść trafia do Livewire dopiero po wyjściu z edytora, a nie przy każdym znaku.
    'saveOnBlur' => false,
])

@php
    $content = $model ? "\$wire.entangle('{$model}')".($saveOnBlur ? '' : '.live') : Js::from((string) $value);
    $icon = 'size-4';
    // Przyciski paska: [etykieta, akcja, czy aktywny, ikona lub tekst].
    $groups = [
        [
            ['Cofnij (Ctrl+Z)', "run('undo')", null, '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>'],
            ['Ponów (Ctrl+Y)', "run('redo')", null, '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 14 5-5-5-5"/><path d="M20 9H9.5a5.5 5.5 0 0 0 0 11H13"/></svg>'],
        ],
        [
            ['Duży nagłówek', "run('toggleHeading', { level: 2 })", "isActive('heading', { level: 2 })", '<span class="text-xs font-bold">H2</span>'],
            ['Mały nagłówek', "run('toggleHeading', { level: 3 })", "isActive('heading', { level: 3 })", '<span class="text-xs font-bold">H3</span>'],
        ],
        [
            ['Pogrubienie (Ctrl+B)', "run('toggleBold')", "isActive('bold')", '<span class="font-bold">B</span>'],
            ['Kursywa (Ctrl+I)', "run('toggleItalic')", "isActive('italic')", '<span class="font-serif italic">I</span>'],
            ['Podkreślenie (Ctrl+U)', "run('toggleUnderline')", "isActive('underline')", '<span class="underline">U</span>'],
            ['Przekreślenie', "run('toggleStrike')", "isActive('strike')", '<span class="line-through">S</span>'],
            ['Wyróżnienie markerem', "run('toggleHighlight')", "isActive('highlight')", '<span class="rounded-sm bg-yellow-200 px-1 text-xs font-bold">A</span>'],
        ],
        [
            ['Lista punktowana', "run('toggleBulletList')", "isActive('bulletList')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></svg>'],
            ['Lista numerowana', "run('toggleOrderedList')", "isActive('orderedList')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10 6h10M10 12h10M10 18h10"/><path d="M4 4v4M3 18h3l-3 3h3" stroke-width="1.5"/></svg>'],
            ['Cytat', "run('toggleBlockquote')", "isActive('blockquote')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="currentColor"><path d="M7 7h4v4c0 3-1.5 5-4 6l-1-1.5c1.5-.8 2.3-2 2.5-3.5H7zm8 0h4v4c0 3-1.5 5-4 6l-1-1.5c1.5-.8 2.3-2 2.5-3.5H15z"/></svg>'],
        ],
        [
            ['Do lewej', "run('setTextAlign', 'left')", "isAligned('left')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h14"/></svg>'],
            ['Do środka', "run('setTextAlign', 'center')", "isAligned('center')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M7 12h10M5 18h14"/></svg>'],
            ['Do prawej', "run('setTextAlign', 'right')", "isAligned('right')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M10 12h10M6 18h14"/></svg>'],
        ],
        [
            ['Link', 'setLink()', "isActive('link')", '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>'],
            ['Linia oddzielająca', "run('setHorizontalRule')", null, '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12h18"/></svg>'],
            ['Wyczyść formatowanie', "run('unsetAllMarks'); run('clearNodes')", null, '<svg class="'.$icon.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7V5h12v2M10 5l-3 14M5 19h6M15 14l6 6M21 14l-6 6"/></svg>'],
        ],
    ];
@endphp

<div
    @if($model) wire:ignore @endif
    x-data="richEditor({ content: {!! $content !!}, placeholder: @js(__($placeholder)) })"
    @if($model && $saveOnBlur) x-on:focusout="if (! $el.contains($event.relatedTarget)) $nextTick(() => $wire.$commit())" @endif
    class="rich-editor rounded-md border border-gray-300 bg-white shadow-sm focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"
>
    <div class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-1.5 py-1 rounded-t-md">
        @foreach($groups as $group)
            <div class="flex items-center gap-0.5 @if(! $loop->first) border-l border-gray-200 pl-1 @endif">
                @foreach($group as [$label, $action, $active, $iconHtml])
                    <button type="button" title="{{ $label }}" aria-label="{{ $label }}" @mousedown.prevent @click.prevent="{{ $action }}"
                        class="inline-flex size-8 items-center justify-center rounded text-gray-700 hover:bg-gray-200 disabled:opacity-40"
                        @if($active) :class="{{ $active }} && 'bg-sky-100 text-sky-700 hover:bg-sky-100'" :aria-pressed="{{ $active }}" @endif
                        @if(str_contains($action, "'undo'")) :disabled="! can('undo')" @elseif(str_contains($action, "'redo'")) :disabled="! can('redo')" @endif
                    >{!! $iconHtml !!}</button>
                @endforeach
            </div>
        @endforeach
        <div class="border-l border-gray-200 pl-1">
            <button type="button" title="{{ __('Emotikony') }}" aria-label="{{ __('Emotikony') }}" @mousedown.prevent @click.prevent="toggleEmoji()" :aria-expanded="emojiOpen"
                class="inline-flex h-8 items-center gap-1 rounded px-2 text-sm text-gray-700 hover:bg-gray-200" :class="emojiOpen && 'bg-sky-100 text-sky-700'">
                <span class="text-base leading-none">😊</span> {{ __('Emotikony') }}
            </button>
        </div>
    </div>

    <div x-show="emojiOpen" x-cloak class="border-b border-gray-200 bg-gray-50 p-2" @keydown.escape.stop="emojiOpen = false">
        <div x-ref="emojiSlot" class="rich-editor-emoji"></div>
    </div>

    <div x-ref="editor" class="{{ $height }} cursor-text px-3 py-2 text-sm text-gray-900" @click="$event.target === $el && $el.querySelector('[contenteditable]')?.focus()"></div>

    @if(! $model && $name)
        <textarea name="{{ $name }}" x-model="content" class="hidden"></textarea>
    @endif
</div>
