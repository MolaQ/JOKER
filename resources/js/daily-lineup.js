/**
 * Skład dnia na stronie głównej: grafika 1080×1350 skalowana do szerokości ramki,
 * „Pobierz obrazek” zapisuje ją w pełnym rozmiarze, „Udostępnij” wysyła obrazek przez menu telefonu
 * (gdy przeglądarka tego nie umie, obrazek się pobiera).
 */
const WIDTH = 1080;
const HEIGHT = 1350;

function fitFrames() {
    document.querySelectorAll('[data-daily-lineup-frame]').forEach((frame) => {
        const stage = frame.querySelector('[data-daily-lineup]');

        if (! stage || frame.dataset.fitted) {
            return;
        }

        frame.dataset.fitted = '1';
        const fit = () => { stage.style.transform = `scale(${frame.clientWidth / WIDTH})`; };
        new ResizeObserver(fit).observe(frame);
        fit();
    });
}

async function renderPng(stage) {
    const { toBlob } = await import('html-to-image');

    return toBlob(stage, { width: WIDTH, height: HEIGHT, pixelRatio: 1, style: { transform: 'none' } });
}

function download(blob, name) {
    const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: name });
    link.click();
    setTimeout(() => URL.revokeObjectURL(link.href), 10000);
}

async function handle(button) {
    const stage = document.querySelector(button.dataset.dailyLineupTarget || '[data-daily-lineup]');

    if (! stage || button.disabled) {
        return;
    }

    const label = button.textContent;
    button.disabled = true;
    button.textContent = button.dataset.busyLabel || label;

    try {
        const blob = await renderPng(stage);
        const name = button.dataset.fileName || 'sklad-dnia.png';
        const file = new File([blob], name, { type: 'image/png' });

        if (button.dataset.dailyLineup === 'share' && navigator.canShare?.({ files: [file] })) {
            await navigator.share({ files: [file], title: button.dataset.shareTitle || '' }).catch(() => {});
        } else {
            download(blob, name);
        }
    } catch (error) {
        window.toast?.(button.dataset.errorMessage || 'Nie udało się przygotować obrazka.', 'error');
        console.error(error);
    } finally {
        button.disabled = false;
        button.textContent = label;
    }
}

export function initDailyLineup() {
    fitFrames();
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-daily-lineup="download"], [data-daily-lineup="share"]');

        if (button) {
            event.preventDefault();
            handle(button);
        }
    });
}
