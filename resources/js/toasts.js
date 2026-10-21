/**
 * Toasty (dymki z komunikatem) w rogu ekranu.
 *
 * Z PHP (komponent Livewire z traitem ShowsToasts): $this->toast('Zapisano.') albo $this->toast('Błąd.', 'error').
 * Z JS: window.toast('Zapisano.') albo window.dispatchEvent(new CustomEvent('toast', { detail: { message, type } })).
 * Wygląd (kolory, tytuły, czas, miejsce) pochodzi z ustawień strony i jest podany w data-toast-config.
 */

const POSITION_CLASSES = {
    'top-right': 'top-4 right-4 sm:top-6 sm:right-6 items-end flex-col-reverse',
    'bottom-right': 'bottom-24 right-4 sm:bottom-6 sm:right-6 items-end flex-col',
    'top-center': 'top-4 left-1/2 -translate-x-1/2 sm:top-6 items-center flex-col-reverse',
    'bottom-center': 'bottom-24 left-1/2 -translate-x-1/2 sm:bottom-6 items-center flex-col',
};

const MAX_TOASTS = 5;

const LEAVE_MS = 250;

let nextId = 1;

/**
 * Ujednolica szczegóły zdarzenia: Livewire wysyła {message, type}, starsze wersje tablicę.
 */
function normalize(detail) {
    if (Array.isArray(detail)) {
        detail = detail[0];
    }

    if (typeof detail === 'string') {
        detail = { message: detail };
    }

    if (! detail || ! detail.message) {
        return null;
    }

    return {
        ...detail,
        message: String(detail.message),
        type: detail.type === 'error' ? 'error' : 'success',
    };
}

export function registerToasts() {
    window.toast = (message, type = 'success', options = {}) => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { ...options, message, type } }));
    };

    /**
     * Podgląd w panelu „Powiadomienia”: pokazuje toast z wartościami z formularza (jeszcze niezapisanymi).
     */
    window.toastPreview = (values, type = 'success') => {
        const color = (value) => (/^#[0-9a-f]{6}$/i.test(String(value ?? '')) ? value : undefined);

        if (POSITION_CLASSES[values.toast_position]) {
            window.dispatchEvent(new CustomEvent('toast-config', { detail: { position: values.toast_position } }));
        }

        window.toast(
            type === 'error' ? 'Tak wygląda komunikat o błędzie, np. gdy nie udało się usunąć drużyny.' : 'Tak wygląda komunikat o udanym zapisie lub imporcie.',
            type,
            {
                title: values[`toast_${type}_title`] ?? undefined,
                bg: color(values[`toast_${type}_bg`]),
                text: color(values[`toast_${type}_text`]),
                seconds: Number(values.toast_seconds) > 0 ? Number(values.toast_seconds) : undefined,
            },
        );
    };

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('toastStack', () => ({
            toasts: [],
            config: {
                position: 'top-right',
                seconds: 4,
                success: { bg: '#ecfdf5', text: '#047857', title: 'Gotowe' },
                error: { bg: '#fef2f2', text: '#b91c1c', title: 'Coś poszło nie tak' },
            },

            init() {
                try {
                    this.config = { ...this.config, ...JSON.parse(this.$el.dataset.toastConfig || '{}') };
                } catch (error) {
                    // Zostają ustawienia domyślne.
                }

                let initial = [];

                try {
                    initial = JSON.parse(this.$el.dataset.toasts || '[]');
                } catch (error) {
                    initial = [];
                }

                initial.forEach((toast) => this.add(toast));
            },

            get positionClass() {
                return POSITION_CLASSES[this.config.position] ?? POSITION_CLASSES['top-right'];
            },

            get fromBottom() {
                return String(this.config.position).startsWith('bottom');
            },

            /**
             * Zmiana wyglądu bez przeładowania (po zapisaniu ustawień albo w podglądzie).
             */
            configure(detail) {
                const config = Array.isArray(detail) ? detail[0] : detail;

                if (config && typeof config === 'object') {
                    this.config = {
                        ...this.config,
                        ...config,
                        success: { ...this.config.success, ...(config.success ?? {}) },
                        error: { ...this.config.error, ...(config.error ?? {}) },
                    };
                }
            },

            add(detail) {
                const data = normalize(detail);

                if (! data) {
                    return;
                }

                const style = this.config[data.type];
                const seconds = Number(data.seconds ?? this.config.seconds) || 4;

                const toast = {
                    id: nextId++,
                    type: data.type,
                    message: data.message,
                    title: data.title ?? style.title,
                    bg: data.bg ?? style.bg,
                    text: data.text ?? style.text,
                    duration: seconds * 1000,
                    remaining: seconds * 1000,
                    startedAt: 0,
                    timer: null,
                    paused: false,
                    visible: false,
                    leaving: false,
                };

                this.toasts.push(toast);

                const active = this.toasts.filter((item) => ! item.leaving);

                if (active.length > MAX_TOASTS) {
                    active.slice(0, active.length - MAX_TOASTS).forEach((item) => this.dismiss(item));
                }

                this.$nextTick(() => {
                    const added = this.find(toast.id);

                    if (added && ! added.leaving) {
                        added.visible = true;
                        this.start(added);
                    }
                });
            },

            find(id) {
                return this.toasts.find((item) => item.id === id);
            },

            start(toast) {
                toast.startedAt = Date.now();
                toast.paused = false;
                clearTimeout(toast.timer);
                toast.timer = setTimeout(() => this.dismiss(toast), toast.remaining);
            },

            pause(toast) {
                if (toast.paused || ! toast.visible) {
                    return;
                }

                clearTimeout(toast.timer);
                toast.timer = null;
                toast.remaining = Math.max(0, toast.remaining - (Date.now() - toast.startedAt));
                toast.paused = true;
            },

            resume(toast) {
                if (toast.paused && toast.visible) {
                    this.start(toast);
                }
            },

            dismiss(toast) {
                if (! toast || toast.leaving) {
                    return;
                }

                clearTimeout(toast.timer);
                toast.timer = null;
                toast.leaving = true;
                toast.visible = false;

                setTimeout(() => {
                    this.toasts = this.toasts.filter((item) => item.id !== toast.id);
                }, LEAVE_MS);
            },
        }));
    });
}
