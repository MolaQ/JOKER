/**
 * Grafiki do mediów społecznościowych: wynik meczu ligowego, mecz turniejowy, wyniki minisiatkówki, relacja.
 *
 * Przycisk z data-share-card (komponent <x-share-card>) niesie dane i wygląd (kolory, format, podpis, herb)
 * z ustawień „Grafiki do mediów społecznościowych”. Grafika powstaje w przeglądarce na <canvas>,
 * a okno podglądu pozwala ją udostępnić (telefon: Facebook, Instagram, Messenger…) albo pobrać.
 */

const SIZES = { square: [1080, 1080], portrait: [1080, 1350], story: [1080, 1920] };

const DISPLAY = '"Oswald Variable", "Arial Narrow", sans-serif';
const SANS = '"Inter Variable", system-ui, sans-serif';

function loadImage(src) {
    return new Promise((resolve) => {
        if (!src) {
            resolve(null);
            return;
        }

        const image = new Image();
        image.crossOrigin = 'anonymous';
        image.onload = () => resolve(image);
        image.onerror = () => resolve(null);
        image.src = src;
    });
}

async function ensureFonts() {
    if (!document.fonts) {
        return;
    }

    await Promise.all([
        document.fonts.load(`700 64px ${DISPLAY}`),
        document.fonts.load(`500 32px ${SANS}`),
        document.fonts.load(`700 32px ${SANS}`),
    ]).catch(() => {});
}

/** Tekst dopasowany do szerokości: zmniejsza czcionkę, aż się zmieści. */
function fitText(ctx, text, maxWidth, size, weight, family, min = 24) {
    let current = size;
    ctx.font = `${weight} ${current}px ${family}`;

    while (current > min && ctx.measureText(text).width > maxWidth) {
        current -= 2;
        ctx.font = `${weight} ${current}px ${family}`;
    }

    return current;
}

/** Zawija tekst do maxLines linii, ostatnią skraca wielokropkiem. */
function wrapLines(ctx, text, maxWidth, maxLines) {
    const words = String(text || '').split(/\s+/).filter(Boolean);
    const lines = [];
    let line = '';

    for (const word of words) {
        const test = line ? `${line} ${word}` : word;

        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    }

    if (line) {
        lines.push(line);
    }

    if (lines.length > maxLines) {
        lines.length = maxLines;
        let last = lines[maxLines - 1];

        while (last.length > 1 && ctx.measureText(`${last}…`).width > maxWidth) {
            last = last.slice(0, -1);
        }

        lines[maxLines - 1] = `${last.trimEnd()}…`;
    }

    return lines;
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

/** Herb albo logo w białym kółku; bez logo inicjały. */
function drawCrest(ctx, image, name, cx, cy, radius) {
    ctx.save();
    ctx.beginPath();
    ctx.arc(cx, cy, radius, 0, Math.PI * 2);
    ctx.fillStyle = '#ffffff';
    ctx.shadowColor = 'rgba(0,0,0,0.35)';
    ctx.shadowBlur = 30;
    ctx.fill();
    ctx.restore();

    if (image) {
        const box = radius * 1.45;
        const scale = Math.min(box / image.width, box / image.height);
        const w = image.width * scale;
        const h = image.height * scale;
        ctx.drawImage(image, cx - w / 2, cy - h / 2, w, h);
        return;
    }

    const initials = String(name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
    ctx.fillStyle = '#12141a';
    ctx.font = `700 ${Math.round(radius * 0.8)}px ${DISPLAY}`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(initials, cx, cy);
}

/** Ikonka zawodnika (głowa i ramiona), jak na stronie minisiatkówki. */
function drawPlayerIcon(ctx, x, y, size, color) {
    const s = size / 24;
    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.arc(x + 12 * s, y + 7.2 * s, 4.6 * s, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.moveTo(x + 3.5 * s, y + 22 * s);
    ctx.bezierCurveTo(x + 3.5 * s, y + 17 * s, x + 7.3 * s, y + 13.4 * s, x + 12 * s, y + 13.4 * s);
    ctx.bezierCurveTo(x + 16.7 * s, y + 13.4 * s, x + 20.5 * s, y + 17 * s, x + 20.5 * s, y + 22 * s);
    ctx.closePath();
    ctx.fill();
}

function drawBackground(ctx, W, H, theme) {
    const gradient = ctx.createLinearGradient(0, 0, W * 0.4, H);
    gradient.addColorStop(0, theme.bg);
    gradient.addColorStop(1, theme.bgTo);
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, W, H);

    // Siatka w tle, jak na stronie.
    ctx.save();
    ctx.strokeStyle = 'rgba(255,255,255,0.05)';
    ctx.lineWidth = 2;

    for (let x = -H; x < W; x += 54) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x + H, H);
        ctx.stroke();
    }

    ctx.restore();

    ctx.fillStyle = theme.accent;
    ctx.fillRect(0, 0, W, 14);
}

function drawHeader(ctx, W, card, logo, top) {
    const theme = card.theme;
    drawCrest(ctx, logo, theme.club, 110, top + 50, 50);

    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#ffffff';
    fitText(ctx, String(theme.club || '').toUpperCase(), W - 260, 46, 700, DISPLAY);
    ctx.fillText(String(theme.club || '').toUpperCase(), 185, top + 44);

    ctx.fillStyle = 'rgba(255,255,255,0.7)';
    fitText(ctx, card.eyebrow || '', W - 260, 30, 500, SANS, 20);
    ctx.fillText(card.eyebrow || '', 185, top + 86);
}

function drawFooter(ctx, W, H, card) {
    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = 'rgba(255,255,255,0.6)';
    ctx.font = `500 28px ${SANS}`;
    ctx.fillText(card.theme.footer || '', W / 2, H - 50);
}

async function drawMatch(ctx, W, H, card) {
    const [logo, home, away] = await Promise.all([loadImage(card.theme.logo), loadImage(card.home?.logo), loadImage(card.away?.logo)]);
    const centerY = H / 2 + 10;

    drawHeader(ctx, W, card, logo, 70);

    if (card.phase) {
        ctx.font = `700 34px ${DISPLAY}`;
        const label = String(card.phase).toUpperCase();
        const width = Math.min(ctx.measureText(label).width + 64, W - 120);
        ctx.fillStyle = card.theme.accent;
        roundRect(ctx, (W - width) / 2, centerY - 330, width, 64, 32);
        ctx.fill();
        ctx.fillStyle = '#12141a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        fitText(ctx, label, width - 40, 34, 700, DISPLAY, 20);
        ctx.fillText(label, W / 2, centerY - 297);
    }

    const crestY = centerY - 110;
    drawCrest(ctx, home, card.home?.name, W * 0.22, crestY, 105);
    drawCrest(ctx, away, card.away?.name, W * 0.78, crestY, 105);

    ctx.textAlign = 'center';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = card.theme.accent;
    fitText(ctx, card.score, 330, 170, 700, DISPLAY, 80);
    ctx.fillText(card.score, W / 2, crestY + 60);

    // Nazwy drużyn: do dwóch linii pod herbami.
    ctx.fillStyle = '#ffffff';
    ctx.font = `700 44px ${DISPLAY}`;
    [[card.home?.name, W * 0.22], [card.away?.name, W * 0.78]].forEach(([name, x]) => {
        fitText(ctx, String(name || '').toUpperCase(), 9999, 44, 700, DISPLAY);
        const lines = wrapLines(ctx, String(name || '').toUpperCase(), 400, 2);
        lines.forEach((line, index) => ctx.fillText(line, x, crestY + 170 + index * 50));
    });

    let y = crestY + 300;

    if (card.sets) {
        ctx.fillStyle = 'rgba(255,255,255,0.85)';
        fitText(ctx, card.sets, W - 160, 36, 500, SANS, 22);
        ctx.fillText(card.sets, W / 2, y);
        y += 70;
    }

    if (card.badge) {
        ctx.font = `700 32px ${SANS}`;
        const width = ctx.measureText(card.badge).width + 60;
        ctx.fillStyle = 'rgba(255,255,255,0.12)';
        roundRect(ctx, (W - width) / 2, y - 42, width, 60, 30);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.fillText(card.badge, W / 2, y);
        y += 60;
    }

    if (card.date) {
        ctx.fillStyle = 'rgba(255,255,255,0.6)';
        ctx.font = `500 30px ${SANS}`;
        ctx.fillText(card.date, W / 2, Math.min(y + 20, H - 110));
    }

    drawFooter(ctx, W, H, card);
}

async function drawMini(ctx, W, H, card) {
    const logo = await loadImage(card.theme.logo);
    drawHeader(ctx, W, card, logo, 70);

    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#ffffff';
    ctx.font = `700 72px ${DISPLAY}`;
    const titleLines = wrapLines(ctx, String(card.title || '').toUpperCase(), W - 140, 2);
    let y = 300;
    titleLines.forEach((line) => {
        ctx.fillText(line, 70, y);
        y += 80;
    });

    ctx.fillStyle = 'rgba(255,255,255,0.7)';
    ctx.font = `500 32px ${SANS}`;
    ctx.fillText(card.date || '', 70, y);
    y += 50;

    const rows = card.rows || [];
    const available = H - y - 130;
    const rowHeight = Math.min(170, Math.floor(available / Math.max(rows.length, 1)));
    const medal = { 1: '#f5c542', 2: '#c9d1dc', 3: '#d08a4c' };

    rows.forEach((row) => {
        const box = rowHeight - 18;
        const hasPlayers = !!row.players && box >= 80;
        const groupSize = Math.round(Math.min(26, box * 0.2));
        const nameSize = Math.round(Math.min(46, box * (hasPlayers ? 0.34 : 0.4)));
        const smallSize = Math.round(Math.min(24, box * 0.19));
        roundRect(ctx, 60, y, W - 120, box, Math.min(28, box / 3));
        ctx.fillStyle = 'rgba(255,255,255,0.08)';
        ctx.fill();

        const midY = y + box / 2;
        const circle = Math.min(46, (box - 22) / 2);
        ctx.beginPath();
        ctx.arc(60 + 30 + circle, midY, circle, 0, Math.PI * 2);
        ctx.fillStyle = medal[row.place] || (row.place === '★' ? card.theme.accent : 'rgba(255,255,255,0.2)');
        ctx.fill();
        ctx.fillStyle = medal[row.place] || row.place === '★' ? '#12141a' : '#ffffff';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = `700 ${Math.round(circle * 1.1)}px ${DISPLAY}`;
        ctx.fillText(String(row.place), 90 + circle, midY + 2);

        const textX = 90 + circle * 2 + 30;
        const textWidth = W - 120 - textX;
        const groupY = midY - box * (hasPlayers ? 0.27 : 0.17);
        const nameY = midY + box * (hasPlayers ? 0.08 : 0.22);
        ctx.textAlign = 'left';
        ctx.textBaseline = 'alphabetic';

        let iconX = textX;
        const iconSize = Math.round(groupSize * 1.15);
        for (let i = 0; i < (row.icons || 0); i++) {
            drawPlayerIcon(ctx, iconX, groupY - iconSize + 4, iconSize, row.color || '#f5b301');
            iconX += iconSize + 2;
        }

        const groupX = iconX > textX ? iconX + 10 : textX;
        ctx.fillStyle = 'rgba(255,255,255,0.75)';
        fitText(ctx, String(row.group || '').toUpperCase(), textWidth - (groupX - textX), groupSize, 700, SANS, 14);
        ctx.fillText(String(row.group || '').toUpperCase(), groupX, groupY);

        ctx.fillStyle = '#ffffff';
        fitText(ctx, row.name || '', textWidth, nameSize, 700, DISPLAY, 20);
        ctx.fillText(row.name || '', textX, nameY);

        if (hasPlayers) {
            ctx.fillStyle = 'rgba(255,255,255,0.65)';
            ctx.font = `500 ${smallSize}px ${SANS}`;
            const lines = wrapLines(ctx, row.players, textWidth, box >= 140 ? 2 : 1);
            lines.forEach((line, index) => ctx.fillText(line, textX, midY + box * 0.33 + index * (smallSize + 4)));
        }

        y += rowHeight;
    });

    drawFooter(ctx, W, H, card);
}

async function drawArticle(ctx, W, H, card) {
    const [logo, photo] = await Promise.all([loadImage(card.theme.logo), loadImage(card.image)]);

    if (photo) {
        const scale = Math.max(W / photo.width, H / photo.height);
        const w = photo.width * scale;
        const h = photo.height * scale;
        ctx.drawImage(photo, (W - w) / 2, (H - h) / 2, w, h);

        const shade = ctx.createLinearGradient(0, H * 0.25, 0, H);
        shade.addColorStop(0, 'rgba(10,12,18,0)');
        shade.addColorStop(0.55, 'rgba(10,12,18,0.75)');
        shade.addColorStop(1, 'rgba(10,12,18,0.95)');
        ctx.fillStyle = shade;
        ctx.fillRect(0, 0, W, H);

        const top = ctx.createLinearGradient(0, 0, 0, 260);
        top.addColorStop(0, 'rgba(10,12,18,0.7)');
        top.addColorStop(1, 'rgba(10,12,18,0)');
        ctx.fillStyle = top;
        ctx.fillRect(0, 0, W, 260);
        ctx.fillStyle = card.theme.accent;
        ctx.fillRect(0, 0, W, 14);
    }

    drawHeader(ctx, W, card, logo, 70);

    ctx.textAlign = 'left';
    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#ffffff';
    ctx.font = `700 84px ${DISPLAY}`;
    let lines = wrapLines(ctx, String(card.title || '').toUpperCase(), W - 140, 4);
    if (lines.length > 3) {
        ctx.font = `700 70px ${DISPLAY}`;
        lines = wrapLines(ctx, String(card.title || '').toUpperCase(), W - 140, 4);
    }

    const lineHeight = parseInt(ctx.font.match(/\d+/)[0], 10) + 8;
    let y = H - 150 - lines.length * lineHeight;

    ctx.fillStyle = card.theme.accent;
    ctx.fillRect(70, y - 20, 120, 10);
    y += lineHeight - 10;
    ctx.fillStyle = '#ffffff';
    lines.forEach((line) => {
        ctx.fillText(line, 70, y);
        y += lineHeight;
    });

    ctx.fillStyle = 'rgba(255,255,255,0.7)';
    ctx.font = `500 30px ${SANS}`;
    ctx.fillText(card.date || '', 70, H - 95);

    ctx.textAlign = 'right';
    ctx.fillStyle = 'rgba(255,255,255,0.6)';
    ctx.font = `500 28px ${SANS}`;
    ctx.fillText(card.theme.footer || '', W - 70, H - 95);
}

export async function renderShareCard(card) {
    const [W, H] = SIZES[card.theme?.format] || SIZES.square;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');

    await ensureFonts();
    drawBackground(ctx, W, H, card.theme);

    if (card.kind === 'mini') {
        await drawMini(ctx, W, H, card);
    } else if (card.kind === 'article') {
        await drawArticle(ctx, W, H, card);
    } else {
        await drawMatch(ctx, W, H, card);
    }

    return canvas;
}

function toBlob(canvas) {
    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

function openPreview(card, canvas, blob) {
    const file = new File([blob], `${card.file || 'grafika'}.png`, { type: 'image/png' });
    const url = URL.createObjectURL(blob);
    const canShareFile = !!(navigator.canShare && navigator.canShare({ files: [file] }));

    const dialog = document.createElement('dialog');
    dialog.className = 'share-card-dialog m-auto w-[min(92vw,30rem)] rounded-3xl bg-white p-0 shadow-2xl backdrop:bg-black/70';
    dialog.innerHTML = `
        <div class="p-4 sm:p-5">
            <img src="${url}" alt="" class="w-full rounded-2xl shadow ring-1 ring-zinc-200">
            <div class="mt-4 grid gap-2 ${canShareFile ? 'grid-cols-2' : 'grid-cols-1'}">
                ${canShareFile ? '<button type="button" data-act="share" class="btn-primary justify-center">Udostępnij</button>' : ''}
                <a href="${url}" download="${file.name}" class="inline-flex items-center justify-center rounded-full px-4 py-2.5 text-sm font-semibold ring-1 ring-zinc-300 hover:bg-zinc-50">Pobierz obraz</a>
            </div>
            <button type="button" data-act="copy" class="mt-2 w-full rounded-full px-4 py-2 text-sm font-medium text-zinc-600 hover:bg-zinc-50">Kopiuj link do strony</button>
            <button type="button" data-act="close" class="mt-1 w-full rounded-full px-4 py-2 text-sm text-zinc-500 hover:bg-zinc-50">Zamknij</button>
        </div>`;

    document.body.appendChild(dialog);
    dialog.addEventListener('close', () => {
        URL.revokeObjectURL(url);
        dialog.remove();
    });
    dialog.addEventListener('click', async (event) => {
        if (event.target === dialog) {
            dialog.close();
            return;
        }

        const act = event.target.closest('[data-act]')?.dataset.act;

        if (act === 'close') {
            dialog.close();
        } else if (act === 'share') {
            try {
                await navigator.share({ files: [file], title: card.title || card.eyebrow || '', text: card.url || '' });
            } catch (error) {
                // Użytkownik zamknął okno udostępniania.
            }
        } else if (act === 'copy') {
            try {
                await navigator.clipboard.writeText(card.url || location.href);
                window.toast?.('Link skopiowany.');
            } catch (error) {
                window.prompt('Link do strony:', card.url || location.href);
            }
        }
    });
    dialog.showModal();
}

export function initShareCards() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-share-card]');

        if (!button) {
            return;
        }

        event.preventDefault();
        button.setAttribute('aria-busy', 'true');
        button.disabled = true;

        try {
            const card = JSON.parse(button.dataset.shareCard);
            const canvas = await renderShareCard(card);
            const blob = await toBlob(canvas);
            openPreview(card, canvas, blob);
        } catch (error) {
            window.toast?.('Nie udało się przygotować grafiki.', 'error');
        } finally {
            button.removeAttribute('aria-busy');
            button.disabled = false;
        }
    });
}
