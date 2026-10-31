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
            let mentionPeople = null;

            return {
                content: config.content,
                tick: 0,
                ready: false,
                emojiOpen: false,
                mention: { open: false, items: [], index: 0, top: 0, left: 0, from: 0, to: 0 },

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
                        editorProps: {
                            attributes: { class: 'rich-editor-content focus:outline-none' },
                            handleKeyDown: (view, event) => this.mentionKey(event),
                        },
                        onUpdate: () => {
                            this.content = editor.isEmpty ? '' : editor.getHTML();
                            this.findMention();
                        },
                        onSelectionUpdate: () => {
                            this.findMention();
                        },
                        onBlur: () => {
                            this.mention.open = false;
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

                /**
                 * Wzmianki: po wpisaniu „@” i początku imienia lub nazwiska pokazujemy listę zawodników.
                 * Wybór wstawia „@Imię Nazwisko”, a strona przy wyświetlaniu zamienia to w link do profilu.
                 */
                async findMention() {
                    if (! config.mentions || ! editor) {
                        return;
                    }

                    const { from, empty } = editor.state.selection;
                    const before = empty ? editor.state.doc.textBetween(Math.max(0, from - 40), from, '\n', '\0') : '';
                    const match = before.match(/(?:^|[\s(„"])@([\p{L}'’-]{1,20}(?: [\p{L}'’-]{0,20})?)$/u);

                    if (! match) {
                        this.mention.open = false;

                        return;
                    }

                    mentionPeople ??= fetch(config.mentions, { headers: { Accept: 'application/json' } }).then((response) => response.json()).catch(() => []);
                    const people = await mentionPeople;
                    const words = match[1].toLocaleLowerCase('pl').split(' ').filter(Boolean);
                    const items = people.filter((person) => {
                        const name = person.name.toLocaleLowerCase('pl');

                        return words.every((word) => name.split(/[\s-]+/).some((part) => part.startsWith(word)));
                    }).slice(0, 8);

                    if (items.length === 0) {
                        this.mention.open = false;

                        return;
                    }

                    const coords = editor.view.coordsAtPos(from);
                    const box = this.$el.getBoundingClientRect();
                    this.mention = {
                        open: true,
                        items,
                        index: 0,
                        top: coords.bottom - box.top + 6,
                        left: Math.max(0, Math.min(coords.left - box.left, box.width - 288)),
                        from: from - match[1].length - 1,
                        to: from,
                    };
                },

                chooseMention(person) {
                    editor.chain().focus().insertContentAt({ from: this.mention.from, to: this.mention.to }, '@' + person.name + ' ').run();
                    this.mention.open = false;
                },

                mentionKey(event) {
                    if (! this.mention.open) {
                        return false;
                    }

                    const count = this.mention.items.length;

                    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                        this.mention.index = (this.mention.index + (event.key === 'ArrowDown' ? 1 : count - 1)) % count;

                        return true;
                    }

                    if (event.key === 'Enter' || event.key === 'Tab') {
                        this.chooseMention(this.mention.items[this.mention.index]);

                        return true;
                    }

                    if (event.key === 'Escape') {
                        this.mention.open = false;

                        return true;
                    }

                    return false;
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
