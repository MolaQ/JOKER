import { initAdminApp } from './admin-app';
import { initAdminTables } from './admin-tables';
import { registerCalendarDetails } from './calendar-details';
import { registerConfirmModal } from './confirm-modal';
import { initCountdowns } from './countdown';
import { initDailyLineup } from './daily-lineup';
import { initLiveBar } from './live-bar';
import { initThumbnailExport } from './thumbnail-export';
import { initPageStats } from './page-stats';
import { initPush } from './push';
import { registerPhotoCropper } from './photo-cropper';
import { registerRichEditor } from './rich-editor';
import { initSearch } from './search';
import { registerSearchSelect } from './search-select';
import { initShareCards } from './share-card';
import { initTheme } from './theme';
import { initTableSort } from './table-sort';
import { registerTimeInputs } from './time-input';
import { registerToasts } from './toasts';

registerPhotoCropper();
registerRichEditor();
registerToasts();
registerConfirmModal();
registerCalendarDetails();
registerSearchSelect();
registerTimeInputs();

initSearch();
initPageStats();
initPush();
initAdminTables();
initAdminApp();
initTableSort();
initDailyLineup();
initThumbnailExport();
initShareCards();
initTheme();
initLiveBar();

// Pojawianie się sekcji przy przewijaniu.
const revealObserver = 'IntersectionObserver' in window
    ? new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -10% 0px' })
    : null;

document.querySelectorAll('[data-reveal]').forEach((el) => {
    if (revealObserver) {
        revealObserver.observe(el);
    } else {
        el.classList.add('is-visible');
    }
});

// Menu mobilne.
const menuButton = document.querySelector('[data-menu-toggle]');
const menu = document.querySelector('[data-menu]');

if (menuButton && menu) {
    menuButton.addEventListener('click', () => {
        const open = menu.classList.toggle('hidden') === false;
        menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.body.classList.toggle('overflow-hidden', open);
    });
}

// Nagłówek: tło po przewinięciu.
const header = document.querySelector('[data-header]');

if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 24);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

// Paski przewijane w bok (np. górny pasek i filtry na telefonie): zanikająca krawędź pokazuje, że jest dalej.
const fadeStrip = (strip) => {
    strip.toggleAttribute('data-more-start', strip.scrollLeft > 4);
    strip.toggleAttribute('data-more-end', strip.scrollLeft + strip.clientWidth < strip.scrollWidth - 4);
};
const fadeStrips = () => document.querySelectorAll('[data-topbar-scroll], [data-scroll-fade]').forEach(fadeStrip);
fadeStrips();
window.addEventListener('resize', fadeStrips, { passive: true });
document.addEventListener('scroll', (event) => event.target instanceof Element && event.target.matches('[data-topbar-scroll], [data-scroll-fade]') && fadeStrip(event.target), { capture: true, passive: true });
document.addEventListener('livewire:init', () => window.Livewire.hook('morphed', () => requestAnimationFrame(fadeStrips)));

// Słaby internet na hali i wygasła sesja: zamiast cichej porażki albo angielskiego okienka jasny komunikat po polsku.
document.addEventListener('livewire:init', () => {
    const texts = document.querySelector('[data-connection-texts]')?.dataset ?? {};
    window.Livewire.interceptRequest(({ onFailure, onError }) => {
        onFailure(() => window.toast?.(texts.offline || 'Brak połączenia z internetem. Zmiana NIE została zapisana, spróbuj ponownie.', 'error'));
        onError(({ response, preventDefault }) => {
            if (response.status === 419) {
                preventDefault();
                window.toast?.(texts.expired || 'Strona była długo otwarta. Odświeżam ją, wpisz zmianę jeszcze raz.', 'error');
                window.setTimeout(() => window.location.reload(), 2500);
            }
        });
    });
});

// Slider na stronie głównej.
const slider = document.querySelector('[data-hero-slider]');

if (slider) {
    // Pasek „Najbliższy mecz” leży na zdjęciu: tekst i przyciski slajdu zawsze kończą się nad nim,
    // nawet gdy licznik zejdzie do drugiej linii i pasek urośnie.
    const panel = slider.querySelector('[data-hero-panel]');
    if (panel && 'ResizeObserver' in window) {
        new ResizeObserver(() => slider.style.setProperty('--hero-panel', `${panel.offsetHeight}px`)).observe(panel);
    }

    const slides = [...slider.querySelectorAll('[data-slide]')];
    const dots = [...slider.querySelectorAll('[data-dot]')];
    let current = 0;
    let timer = null;

    const show = (index) => {
        current = (index + slides.length) % slides.length;
        slides.forEach((slide, i) => {
            slide.classList.toggle('opacity-100', i === current);
            slide.classList.toggle('opacity-0', i !== current);
            slide.setAttribute('aria-hidden', i === current ? 'false' : 'true');
        });
        dots.forEach((dot, i) => dot.classList.toggle('is-active', i === current));
    };

    const start = () => {
        if (slides.length > 1) {
            timer = setInterval(() => show(current + 1), 6500);
        }
    };

    dots.forEach((dot, i) => dot.addEventListener('click', () => {
        clearInterval(timer);
        show(i);
        start();
    }));

    start();
}

// Odliczanie do najbliższego meczu.
initCountdowns();
