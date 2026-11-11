/**
 * Okno szczegółów meczów z kafelka kalendarza: wyrasta z klikniętego kafelka, mecze wjeżdżają jeden po drugim.
 * Dane meczów przychodzą z kafelka (zdarzenie match-details), więc otwarcie nie pyta serwera.
 */
export function registerCalendarDetails() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('calendarDetails', () => ({
            tile: null,
            origin: null,

            open({ tile, el }) {
                this.tile = tile;
                this.origin = el;
                document.documentElement.classList.add('overflow-hidden');

                this.$nextTick(() => {
                    const panel = this.$refs.panel;
                    panel?.querySelector('[data-close]')?.focus({ preventScroll: true });

                    if (! panel || ! el || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    // Okno startuje w miejscu i rozmiarze kafelka, potem rośnie do pełnego rozmiaru.
                    const from = el.getBoundingClientRect();
                    const to = panel.getBoundingClientRect();
                    const dx = from.left + from.width / 2 - (to.left + to.width / 2);
                    const dy = from.top + from.height / 2 - (to.top + to.height / 2);

                    panel.animate([
                        { transform: `translate(${dx}px, ${dy}px) scale(${from.width / to.width}, ${from.height / to.height})`, opacity: 0.3, borderRadius: '12px' },
                        { transform: 'none', opacity: 1, borderRadius: '24px' },
                    ], { duration: 460, easing: 'cubic-bezier(.2, .85, .25, 1)' });
                });
            },

            close() {
                const panel = this.$refs.panel;
                const finish = () => {
                    this.tile = null;
                    document.documentElement.classList.remove('overflow-hidden');
                    this.origin?.focus?.({ preventScroll: true });
                };

                if (! panel || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    finish();

                    return;
                }

                panel.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: 'scale(.94)' }], { duration: 180, easing: 'ease-in', fill: 'forwards' }).onfinish = finish;
            },

            initials(name) {
                return String(name || '').split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
            },

            resultClass(match) {
                return {
                    win: 'text-emerald-600',
                    loss: 'text-red-600',
                    derby: 'text-sky-club-dark',
                }[match.outcome] ?? 'text-zinc-500';
            },
        }));
    });
}
