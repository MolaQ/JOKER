/**
 * Miniatury transmisji (/miniatury): ramki skalują grafikę 1920×1080 do szerokości,
 * „Pobierz PNG” zapisuje ją w pełnym rozmiarze.
 */
const WIDTH = 1920;
const HEIGHT = 1080;

function fit(frame) {
    const scaler = frame.querySelector('[data-ytt-scaler]');

    if (scaler) {
        scaler.style.transform = `scale(${frame.clientWidth / WIDTH})`;
    }
}

/** Grafika VS (wektorowa, ok. 280 kB) jest pobierana raz i wstawiana w miniatury, żeby nie wysyłać jej przy każdej zmianie w edytorze. */
let vsArt = null;

async function fillVsArt() {
    const slots = [...document.querySelectorAll('[data-ytt-vs-art]')].filter((slot) => ! slot.firstElementChild);

    if (! slots.length) {
        return;
    }

    vsArt ??= fetch(slots[0].dataset.yttVsArt).then((response) => response.text());
    const markup = await vsArt;
    slots.forEach((slot) => { slot.innerHTML = markup; });
}

function watchFrames() {
    fillVsArt();
    document.querySelectorAll('[data-ytt-frame]').forEach((frame) => {
        if (! frame.dataset.fitted) {
            frame.dataset.fitted = '1';
            new ResizeObserver(() => fit(frame)).observe(frame);
        }
        fit(frame);
    });
}

async function download(button) {
    const stage = document.querySelector(button.dataset.yttDownload);

    if (! stage || button.disabled) {
        return;
    }

    const label = button.textContent;
    button.disabled = true;
    button.textContent = button.dataset.busyLabel || label;

    try {
        const { toBlob } = await import('html-to-image');
        await document.fonts.ready;
        const blob = await toBlob(stage, { width: WIDTH, height: HEIGHT, pixelRatio: 1, cacheBust: true });
        const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: button.dataset.fileName || 'miniatura.png' });
        link.click();
        setTimeout(() => URL.revokeObjectURL(link.href), 10000);
    } catch (error) {
        window.toast?.(button.dataset.errorMessage || 'Nie udało się przygotować obrazka.', 'error');
        console.error(error);
    } finally {
        button.disabled = false;
        button.textContent = label;
    }
}

export function initThumbnailExport() {
    watchFrames();
    document.addEventListener('livewire:navigated', watchFrames);
    // Po każdej zmianie w edytorze Livewire podmienia ramki: przeskaluj je od nowa.
    const hook = () => window.Livewire?.hook('commit', ({ succeed }) => succeed(() => requestAnimationFrame(watchFrames)));
    window.Livewire ? hook() : document.addEventListener('livewire:init', hook);
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-ytt-download]');

        if (button) {
            event.preventDefault();
            download(button);
        }
    });
}
