@php
    $toolbarButton = 'rounded-md border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50';
    $emojis = ['🏐', '🏆', '🥇', '🥈', '🥉', '🏅', '🎉', '👏', '💪', '🔥', '⭐', '✨', '⚡', '🙌', '👍', '🤝', '❤️', '💙', '💛', '🙏', '😀', '😂', '😍', '😎', '😢', '😮', '✅', '❌', '📅', '⏰', '📍', '🚌', '📸', '🎥', '📢', '🎂', '👉', '⬆️'];
@endphp

<div class="flex flex-wrap gap-2" x-data="{ emojiOpen: false }" @keydown.escape="emojiOpen = false">
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('bold'); $refs.editor.focus()"><strong>B</strong></button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('italic'); $refs.editor.focus()"><em>I</em></button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('underline'); $refs.editor.focus()"><u>U</u></button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertUnorderedList'); $refs.editor.focus()">• {{ __('Lista') }}</button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('insertOrderedList'); $refs.editor.focus()">1. {{ __('Lista') }}</button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('formatBlock', false, 'h3'); $refs.editor.focus()">{{ __('Nagłówek') }}</button>
    <button type="button" class="{{ $toolbarButton }}" @click.prevent="document.execCommand('removeFormat'); $refs.editor.focus()">{{ __('Wyczyść') }}</button>

    {{-- Emotikony (rozwijany pasek pod przyciskami): mousedown.prevent nie zabiera kursora z edytora, więc znak trafia tam, gdzie się pisało. --}}
    <button type="button" class="{{ $toolbarButton }}" title="{{ __('Emotikony') }}" aria-label="{{ __('Emotikony') }}" :aria-expanded="emojiOpen" @mousedown.prevent @click.prevent="emojiOpen = ! emojiOpen">😊 {{ __('Emotikony') }}</button>
    <div x-show="emojiOpen" x-cloak class="flex w-full flex-wrap gap-0.5 rounded-lg border border-gray-200 bg-gray-50 p-1.5">
        @foreach($emojis as $emoji)
            <button type="button" class="rounded p-1 text-xl leading-none hover:bg-white" @mousedown.prevent @click.prevent="
                const editor = $refs.editor;
                if (document.activeElement !== editor) {
                    editor.focus();
                    const range = document.createRange();
                    range.selectNodeContents(editor);
                    range.collapse(false);
                    const selection = window.getSelection();
                    selection.removeAllRanges();
                    selection.addRange(range);
                }
                document.execCommand('insertText', false, @js($emoji));
            ">{{ $emoji }}</button>
        @endforeach
    </div>
</div>
