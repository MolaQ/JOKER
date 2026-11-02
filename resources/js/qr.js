import qrcode from 'qrcode-generator';

// Kody QR na kartkach do druku: <div data-qr="https://..."> dostaje obrazek SVG z kodem.
document.querySelectorAll('[data-qr]').forEach((element) => {
    const qr = qrcode(0, 'M');
    qr.addData(element.dataset.qr);
    qr.make();
    element.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
    element.querySelector('svg')?.setAttribute('aria-label', element.dataset.qr);
});
