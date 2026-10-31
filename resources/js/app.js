import { initAdminTables } from './admin-tables';
import { registerConfirmModal } from './confirm-modal';
import { initCountdowns } from './countdown';
import { initDailyLineup } from './daily-lineup';
import { initPageStats } from './page-stats';
import { initPush } from './push';
import { registerPhotoCropper } from './photo-cropper';
import { registerRichEditor } from './rich-editor';
import { initSearch } from './search';
import { registerSearchSelect } from './search-select';
import { initShareCards } from './share-card';
import { initTableSort } from './table-sort';
import { registerToasts } from './toasts';

registerPhotoCropper();
registerRichEditor();
registerToasts();
registerConfirmModal();
registerSearchSelect();

initSearch();
initPageStats();
initPush();
initAdminTables();
initTableSort();
initDailyLineup();
initShareCards();

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

// Slider na stronie głównej.
const slider = document.querySelector('[data-hero-slider]');

if (slider) {
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
