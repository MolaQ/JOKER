/**
 * Ładne okno potwierdzenia zamiast systemowego confirm() dla każdego wire:confirm="...".
 *
 * Kliknięcie (albo wysłanie formularza) z wire:confirm zatrzymujemy w fazie przechwytywania,
 * pokazujemy okno, a po „Usuń”/„Potwierdź” powtarzamy kliknięcie z window.confirm zwracającym true,
 * więc Livewire wykonuje akcję jak zwykle. Bez okna na stronie zostaje zwykłe confirm().
 */

const DANGER_PATTERN = /usu[nńw]|skasuj|delete|remove/i;

let bypass = false;

function modalAvailable() {
    return Boolean(window.Alpine && document.querySelector('[data-confirm-modal]'));
}

function messageOf(el) {
    return (el.getAttribute('wire:confirm') || '').replaceAll('\\n', '\n');
}

function hasWireAttribute(el, prefix) {
    return [...el.attributes].some((attribute) => attribute.name === prefix || attribute.name.startsWith(prefix + '.'));
}

function runConfirmed(action) {
    const originalConfirm = window.confirm;

    bypass = true;
    window.confirm = () => true;

    try {
        action();
    } finally {
        window.confirm = originalConfirm;
        bypass = false;
    }
}

function ask(message, onAccept) {
    window.dispatchEvent(new CustomEvent('confirm-modal:open', { detail: { message, onAccept } }));
}

export function registerConfirmModal() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('confirmModal', () => ({
            open: false,
            message: '',
            danger: false,
            onAccept: null,
            returnFocus: null,

            init() {
                window.addEventListener('confirm-modal:open', (event) => this.show(event.detail));
            },

            get title() {
                return this.danger ? this.$el.dataset.titleDanger : this.$el.dataset.title;
            },

            get confirmLabel() {
                return this.danger ? this.$el.dataset.labelDanger : this.$el.dataset.label;
            },

            show({ message, onAccept }) {
                this.message = message || this.$el.dataset.fallback;
                this.danger = DANGER_PATTERN.test(this.message);
                this.onAccept = onAccept;
                this.returnFocus = document.activeElement;
                this.open = true;
                document.documentElement.classList.add('overflow-hidden');

                this.$nextTick(() => this.$refs.cancel?.focus());
            },

            accept() {
                const action = this.onAccept;

                this.close(false);

                if (action) {
                    action();
                }
            },

            cancel() {
                this.close(true);
            },

            close(restoreFocus) {
                if (! this.open) {
                    return;
                }

                this.open = false;
                this.onAccept = null;
                document.documentElement.classList.remove('overflow-hidden');

                if (restoreFocus && this.returnFocus?.isConnected) {
                    this.returnFocus.focus();
                }
            },

            /**
             * Tab krąży tylko między przyciskami okna.
             */
            trapFocus(event) {
                const buttons = [this.$refs.cancel, this.$refs.accept].filter(Boolean);
                const index = buttons.indexOf(document.activeElement);
                const next = event.shiftKey ? index - 1 : index + 1;

                event.preventDefault();
                buttons[(next + buttons.length) % buttons.length]?.focus();
            },
        }));
    });

    document.addEventListener('click', (event) => {
        if (bypass || ! (event.target instanceof Element)) {
            return;
        }

        const el = event.target.closest('[wire\\:confirm]');

        if (! el || ! hasWireAttribute(el, 'wire:click') || ! modalAvailable()) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        ask(messageOf(el), () => runConfirmed(() => el.click()));
    }, true);

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (bypass || ! (form instanceof HTMLFormElement) || ! form.hasAttribute('wire:confirm') || ! modalAvailable()) {
            return;
        }

        const submitter = event.submitter;

        event.preventDefault();
        event.stopImmediatePropagation();

        ask(messageOf(form), () => runConfirmed(() => {
            if (submitter && form.contains(submitter)) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        }));
    }, true);
}
