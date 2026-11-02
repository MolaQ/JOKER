/*
 * Panel jak aplikacja: cienki pasek ładowania u góry przy przejściu na inną stronę
 * i przy dłuższym zapisie w Livewire (pojawia się dopiero po chwili, żeby nie migał).
 */
export function initAdminApp() {
    const bar = document.querySelector('[data-admin-progress] > div');

    if (! bar) {
        return;
    }

    // Przejście między stronami: nagłówek i dolne menu stoją, treść wjeżdża. Nazwy tylko na czas
    // przejścia, bo stała nazwa zamyka elementy w osobnej warstwie (okna chowałyby się pod menu).
    const nameParts = (on) => [['[data-admin-header]', 'admin-header'], ['[data-admin-main]', 'admin-main'], ['[data-admin-bottom-nav]', 'admin-bottom-nav']]
        .forEach(([selector, name]) => document.querySelector(selector)?.style.setProperty('view-transition-name', on ? name : ''));

    window.addEventListener('pageswap', (event) => event.viewTransition && nameParts(true));
    window.addEventListener('pagereveal', (event) => {
        if (event.viewTransition) {
            nameParts(true);
            event.viewTransition.finished.finally(() => nameParts(false));
        }
    });

    let timer = null;
    let active = 0;

    const start = (delay = 0) => {
        active++;
        clearTimeout(timer);
        timer = setTimeout(() => {
            bar.style.transition = 'none';
            bar.style.width = '0%';
            bar.style.opacity = '1';
            bar.getBoundingClientRect();
            bar.style.transition = 'width 8s cubic-bezier(0.1, 0.7, 0.1, 1), opacity 0.2s';
            bar.style.width = '85%';
        }, delay);
    };

    const done = () => {
        active = Math.max(0, active - 1);

        if (active > 0) {
            return;
        }

        clearTimeout(timer);

        if (bar.style.opacity !== '1') {
            return;
        }

        bar.style.transition = 'width 0.2s ease-out, opacity 0.3s 0.2s';
        bar.style.width = '100%';
        bar.style.opacity = '0';
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href]');

        if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey
            || link.target === '_blank' || link.hasAttribute('download') || link.origin !== location.origin
            || (link.pathname === location.pathname && link.search === location.search && link.hash)) {
            return;
        }

        start();
    });

    document.addEventListener('submit', (event) => {
        if (! event.defaultPrevented && ! event.target.hasAttribute('wire:submit') && ! event.target.hasAttribute('wire:submit.prevent')) {
            start();
        }
    });

    window.addEventListener('pageshow', () => {
        active = 1;
        done();
    });

    document.addEventListener('livewire:init', () => {
        window.Livewire.hook('request', ({ respond, fail }) => {
            start(250);
            respond(() => done());
            fail(() => done());
        });
    });
}
