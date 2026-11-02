// Tabele panelu na telefonie zamieniają się w zwarte listy (resources/css/app.css); każda komórka dostaje
// nazwę swojej kolumny w data-label. Livewire podmienia wiersze i zdejmuje etykiety, więc są dopisywane na bieżąco.
const SELECTOR = '.admin-panel main table:not([data-scroll-table])';

function setFlag(cell, name, on) {
    if (cell.hasAttribute(name) !== on) {
        cell.toggleAttribute(name, on);
    }
}

function labelTable(table) {
    const headers = [];

    table.querySelectorAll(':scope > thead > tr:last-child > th').forEach((th) => {
        const label = th.dataset.label ?? (th.querySelector('.sr-only') ? '' : th.textContent.trim().replace(/\s+/g, ' '));

        for (let i = 0; i < (th.colSpan || 1); i++) {
            headers.push(label);
        }
    });

    if (! headers.length) {
        return;
    }

    table.querySelectorAll(':scope > tbody > tr').forEach((row) => {
        let index = 0;
        let primary = null;

        [...row.children].forEach((cell) => {
            const label = cell.colSpan > 1 ? '' : (headers[index] ?? '');
            const text = cell.textContent.trim().replace(/\s+/g, ' ');

            if (cell.getAttribute('data-label') !== label) {
                cell.setAttribute('data-label', label);
            }

            // Zwarta lista na telefonie: tytułem wiersza jest pierwsza komórka z tekstem (nie numer ani ikonka),
            // a krótkie wartości (liczby, „—”) zostają z nazwą kolumny, żeby było wiadomo, co znaczą.
            if (! primary && label !== '' && text.length > 3 && ! /^[#\d\s.,:\/-]+$/.test(text) && cell !== row.lastElementChild) {
                primary = cell;
            }

            setFlag(cell, 'data-short', label !== '' && text.length > 0 && text.length <= 4);
            setFlag(cell, 'data-blank', ['—', '-', 'brak', ''].includes(text.toLowerCase()) && ! cell.querySelector('input, select, button, a, img, svg'));
            index += cell.colSpan || 1;
        });

        [...row.children].forEach((cell) => setFlag(cell, 'data-primary', cell === primary));
    });
}

export function initAdminTables() {
    const main = document.querySelector('.admin-panel main');

    if (! main) {
        return;
    }

    const labelAll = () => main.querySelectorAll(SELECTOR).forEach(labelTable);
    let scheduled = false;

    labelAll();
    new MutationObserver(() => {
        if (! scheduled) {
            scheduled = true;
            requestAnimationFrame(() => {
                scheduled = false;
                labelAll();
            });
        }
    }).observe(main, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-label', 'data-primary', 'data-short', 'data-blank'] });
}
