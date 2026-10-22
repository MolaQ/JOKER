/**
 * Edytor treści w panelu (TipTap) z wyborem emotikon jak w komunikatorach.
 * Biblioteki ładują się dopiero, gdy na stronie jest edytor, więc strona publiczna ich nie pobiera.
 * Instancji edytora nie trzymamy w danych Alpine (proxy psuje TipTap), tylko w zmiennej obok.
 */
export function registerRichEditor() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('richEditor', (config) => {
            let editor = null;
            let picker = null;

            return {
                content: config.content,
                tick: 0,
                ready: false,
                emojiOpen: false,

                async init() {
                    const [{ Editor }, { default: StarterKit }, { default: TextAlign }, { default: Highlight }, { Placeholder }] = await Promise.all([
                        import('@tiptap/core'),
                        import('@tiptap/starter-kit'),
                        import('@tiptap/extension-text-align'),
                        import('@tiptap/extension-highlight'),
                        import('@tiptap/extensions'),
                    ]);

                    editor = new Editor({
                        element: this.$refs.editor,
                        content: this.content || '',
                        extensions: [
                            StarterKit.configure({
                                heading: { levels: [2, 3] },
                                link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
                            }),
                            TextAlign.configure({ types: ['heading', 'paragraph'] }),
                            Highlight,
                            Placeholder.configure({ placeholder: config.placeholder || '' }),
                        ],
                        editorProps: { attributes: { class: 'rich-editor-content focus:outline-none' } },
                        onUpdate: () => {
                            this.content = editor.isEmpty ? '' : editor.getHTML();
                        },
                        onTransaction: () => {
                            this.tick++;
                        },
                    });

                    // Zmiana z zewnątrz (np. otwarcie innego artykułu w tym samym oknie).
                    this.$watch('content', (value) => {
                        const current = editor.isEmpty ? '' : editor.getHTML();

                        if ((value || '') !== current) {
                            editor.commands.setContent(value || '', { emitUpdate: false });
                        }
                    });

                    this.ready = true;
                },

                destroy() {
                    editor?.destroy();
                },

                isActive(name, attributes = {}) {
                    this.tick;

                    return editor ? editor.isActive(name, attributes) : false;
                },

                isAligned(alignment) {
                    this.tick;

                    return editor ? editor.isActive({ textAlign: alignment }) : false;
                },

                can(action) {
                    this.tick;

                    return editor ? editor.can()[action]() : false;
                },

                run(command, ...args) {
                    editor?.chain().focus()[command](...args).run();
                },

                setLink() {
                    const previous = editor.getAttributes('link').href || '';
                    const url = window.prompt('Adres linku (zostaw puste, aby usunąć link):', previous || 'https://');

                    if (url === null) {
                        return;
                    }

                    if (url.trim() === '' || url.trim() === 'https://') {
                        editor.chain().focus().extendMarkRange('link').unsetLink().run();

                        return;
                    }

                    editor.chain().focus().extendMarkRange('link').setLink({ href: url.trim() }).run();
                },

                async toggleEmoji() {
                    this.emojiOpen = ! this.emojiOpen;

                    if (! this.emojiOpen || picker) {
                        return;
                    }

                    const [, { default: polish }, { default: dataSource }] = await Promise.all([
                        import('emoji-picker-element'),
                        import('emoji-picker-element/i18n/pl.js'),
                        import('emoji-picker-element-data/pl/cldr-native/data.json?url'),
                    ]);

                    picker = document.createElement('emoji-picker');
                    picker.locale = 'pl';
                    picker.dataSource = dataSource;
                    picker.i18n = polish;
                    picker.classList.add('light');
                    picker.addEventListener('emoji-click', (event) => {
                        editor.chain().focus().insertContent(event.detail.unicode).run();
                    });
                    this.$refs.emojiSlot.appendChild(picker);
                },
            };
        });
    });
}
