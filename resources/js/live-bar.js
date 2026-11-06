/**
 * Pasek „Na żywo” (partials/live-bar): kilka trwających meczów zmienia się co 4 sekundy, a co 20 sekund
 * pasek pobiera się na nowo bez przeładowania strony. Zmieniony wynik na chwilę się podświetla.
 */
const ROTATE_MS = 4000;
const REFRESH_MS = 20000;

let index = 0;

const bar = () => document.querySelector('[data-live-bar]');

const show = (element) => {
    const slides = [...element.querySelectorAll('[data-live-slide]')];
    if (!slides.length) return;
    index %= slides.length;
    slides.forEach((slide, position) => { slide.hidden = position !== index; });
};

const scores = (element) => Object.fromEntries(
    [...element.querySelectorAll('[data-live-score]')].map((score) => [score.dataset.liveScore, score.textContent.trim()]),
);

const refresh = async () => {
    const current = bar();
    if (!current || document.hidden || !current.dataset.src) return;

    try {
        const response = await fetch(current.dataset.src, { headers: { Accept: 'text/html' }, cache: 'no-store' });
        if (!response.ok) return;

        const template = document.createElement('template');
        template.innerHTML = (await response.text()).trim();
        const fresh = template.content.querySelector('[data-live-bar]');

        if (!fresh) {
            current.remove();
            return;
        }

        if (fresh.textContent.replace(/\s+/g, ' ') === current.textContent.replace(/\s+/g, ' ')) return;

        const before = scores(current);
        Object.entries(scores(fresh)).forEach(([key, value]) => {
            if (key in before && before[key] !== value) {
                fresh.querySelector(`[data-live-score="${key}"]`)?.classList.add('live-flash');
            }
        });

        show(fresh);
        current.replaceWith(fresh);
    } catch {
        // Bez sieci pasek zostaje taki, jaki był.
    }
};

export function initLiveBar() {
    if (!bar()) return;

    setInterval(() => {
        const element = bar();
        if (!element || document.hidden) return;
        index += 1;
        show(element);
    }, ROTATE_MS);

    setInterval(refresh, REFRESH_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
}
