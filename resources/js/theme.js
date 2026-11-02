/*
 * Przełącznik motywu Jasny / Ciemny / Auto (x-theme-switch). Wybór zapamiętuje przeglądarka;
 * „Auto” idzie za ustawieniem telefonu lub komputera i zmienia się razem z nim (np. wieczorem).
 */
const media = window.matchMedia('(prefers-color-scheme: dark)');

const stored = () => {
    try {
        return localStorage.getItem('theme') || 'light';
    } catch (e) {
        return 'light';
    }
};

const apply = (theme) => {
    const root = document.documentElement;
    const dark = theme === 'dark' || (theme === 'auto' && media.matches);
    const change = () => {
        root.classList.toggle('dark', dark);
        root.dataset.themeChoice = theme;
        document.querySelectorAll('[data-theme-set]').forEach((button) => button.setAttribute('aria-pressed', button.dataset.themeSet === theme ? 'true' : 'false'));
    };

    // Płynne przejście między motywami tam, gdzie przeglądarka to umie.
    if (document.startViewTransition && root.classList.contains('dark') !== dark && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.startViewTransition(change);
    } else {
        change();
    }
};

export function initTheme() {
    apply(stored());

    document.addEventListener('click', (event) => {
        const button = event.target.closest?.('[data-theme-set]');

        if (! button) {
            return;
        }

        try {
            localStorage.setItem('theme', button.dataset.themeSet);
        } catch (e) {}

        apply(button.dataset.themeSet);
    });

    media.addEventListener('change', () => stored() === 'auto' && apply('auto'));
    document.addEventListener('livewire:navigated', () => apply(stored()));
}
