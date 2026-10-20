/**
 * Statystyki odwiedzin bez ciasteczek: czas aktywnego oglądania strony, jak daleko ją przewinięto
 * i które zakładki otwierano (elementy z role="tab" albo data-track="Nazwa").
 */
export function initPageStats() {
    const token = document.querySelector('meta[name="pv"]')?.content;

    if (! token || ! navigator.sendBeacon) {
        return;
    }

    let visibleSince = document.visibilityState === 'visible' ? Date.now() : null;
    let seconds = 0;
    let maxScroll = 0;

    const scrollDepth = () => {
        const doc = document.documentElement;
        const scrollable = doc.scrollHeight - window.innerHeight;
        const depth = scrollable <= 0 ? 100 : Math.round((window.scrollY / scrollable) * 100);
        maxScroll = Math.max(maxScroll, Math.min(100, depth));
    };

    const send = (url, data) => {
        const form = new FormData();
        form.append('pv', token);
        Object.entries(data).forEach(([key, value]) => form.append(key, value));
        navigator.sendBeacon(url, form);
    };

    const flush = () => {
        if (visibleSince) {
            seconds += (Date.now() - visibleSince) / 1000;
            visibleSince = null;
        }

        send('/statystyki/wyswietlenie', { seconds: Math.round(seconds), scroll: maxScroll });
    };

    scrollDepth();
    window.addEventListener('scroll', scrollDepth, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flush();
        } else {
            visibleSince = Date.now();
        }
    });

    window.addEventListener('pagehide', flush);

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-track], [role="tab"]');

        if (tab) {
            const label = (tab.dataset.track || tab.textContent || '').replace(/\s+/g, ' ').trim();

            if (label) {
                send('/statystyki/zakladka', { label });
            }
        }
    });
}
