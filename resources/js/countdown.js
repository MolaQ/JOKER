/**
 * Zegar odliczający do meczu w stylu wyświetlacza siedmiosegmentowego.
 * Cyfry to SVG z 7 segmentów; przy zmianie cyfry nowe segmenty „zapalają się” z błyskiem,
 * a przy starcie wyświetlacz robi krótki test segmentów jak prawdziwa tablica wyników.
 */

// Segmenty: a (góra), b (prawy górny), c (prawy dolny), d (dół), e (lewy dolny), f (lewy górny), g (środek).
const SEGMENT_SHAPES = {
    a: '10,4 46,4 40,10 16,10',
    b: '48,6 48,46 42,42 42,12',
    c: '48,54 48,94 42,88 42,58',
    d: '10,96 46,96 40,90 16,90',
    e: '8,54 14,58 14,88 8,94',
    f: '8,6 14,12 14,42 8,46',
    g: '10,50 16,46 40,46 46,50 40,54 16,54',
};

const DIGITS = {
    0: 'abcdef',
    1: 'bc',
    2: 'abdeg',
    3: 'abcdg',
    4: 'bcfg',
    5: 'acdfg',
    6: 'acdefg',
    7: 'abc',
    8: 'abcdefg',
    9: 'abcdfg',
};

const SVG_NS = 'http://www.w3.org/2000/svg';

function createDigit() {
    const svg = document.createElementNS(SVG_NS, 'svg');
    svg.setAttribute('viewBox', '0 0 56 100');
    svg.setAttribute('class', 'seg-digit');
    svg.setAttribute('aria-hidden', 'true');

    for (const [name, points] of Object.entries(SEGMENT_SHAPES)) {
        const polygon = document.createElementNS(SVG_NS, 'polygon');
        polygon.setAttribute('points', points);
        polygon.dataset.segment = name;
        svg.appendChild(polygon);
    }

    return svg;
}

function setDigit(svg, value) {
    if (svg.dataset.value === String(value)) {
        return;
    }

    const lit = DIGITS[value] ?? '';
    svg.dataset.value = String(value);

    svg.querySelectorAll('polygon').forEach((segment) => {
        const shouldLight = lit.includes(segment.dataset.segment);
        const wasLit = segment.classList.contains('is-on');

        segment.classList.toggle('is-on', shouldLight);

        if (shouldLight && !wasLit) {
            segment.classList.remove('is-igniting');
            // Wymuszenie ponownego uruchomienia animacji.
            void segment.getBoundingClientRect();
            segment.classList.add('is-igniting');
        }
    });
}

function buildDisplay(el) {
    const groups = [
        ['days', 'dni'],
        ['hours', 'godz'],
        ['minutes', 'min'],
        ['seconds', 'sek'],
    ];
    const digits = {};

    el.textContent = '';
    el.classList.add('seg-display');

    groups.forEach(([key, label], index) => {
        const group = document.createElement('div');
        group.className = 'seg-group';

        const row = document.createElement('div');
        row.className = 'seg-row';
        digits[key] = [createDigit(), createDigit()];
        digits[key].forEach((digit) => row.appendChild(digit));

        const caption = document.createElement('span');
        caption.className = 'seg-label';
        caption.textContent = label;

        group.append(row, caption);
        el.appendChild(group);

        if (index < groups.length - 1) {
            const colon = document.createElement('span');
            colon.className = 'seg-colon';
            colon.setAttribute('aria-hidden', 'true');
            el.appendChild(colon);
        }
    });

    return digits;
}

export function initCountdowns() {
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const target = new Date(el.dataset.countdown).getTime();

        if (Number.isNaN(target)) {
            return;
        }

        const digits = buildDisplay(el);
        const live = el.parentElement?.querySelector('[data-countdown-text]');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const show = (values) => {
            for (const [key, value] of Object.entries(values)) {
                const text = String(Math.min(value, 99)).padStart(2, '0');
                setDigit(digits[key][0], Number(text[0]));
                setDigit(digits[key][1], Number(text[1]));
            }
        };

        const tick = () => {
            const diff = target - Date.now();

            if (diff <= 0) {
                show({ days: 0, hours: 0, minutes: 0, seconds: 0 });
                el.classList.add('is-live');
                if (live) {
                    live.textContent = 'Gramy dziś!';
                }
                return false;
            }

            const days = Math.floor(diff / 86400000);
            const hours = Math.floor((diff % 86400000) / 3600000);
            const minutes = Math.floor((diff % 3600000) / 60000);
            const seconds = Math.floor((diff % 60000) / 1000);

            show({ days, hours, minutes, seconds });
            el.classList.toggle('is-blink', seconds % 2 === 0);

            if (live) {
                live.textContent = `Do meczu: ${days} dni, ${hours} godz., ${minutes} min`;
            }

            return true;
        };

        const start = () => {
            if (tick()) {
                const timer = setInterval(() => {
                    if (!tick()) {
                        clearInterval(timer);
                    }
                }, 1000);
            }
        };

        if (reducedMotion) {
            start();
            return;
        }

        // Test segmentów: wszystkie „8”, potem zegar rusza.
        el.classList.add('is-booting');
        show({ days: 88, hours: 88, minutes: 88, seconds: 88 });
        setTimeout(() => {
            el.classList.remove('is-booting');
            start();
        }, 900);
    });
}
