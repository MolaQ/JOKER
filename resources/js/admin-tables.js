// Tabele panelu na telefonie zamieniają się w karty (resources/css/app.css); każda komórka dostaje
// nazwę swojej kolumny w data-label. Livewire podmienia wiersze, więc etykiety są dopisywane na bieżąco.
const SELECTOR = '.admin-panel main table:not([data-scroll-table])';

function labelTable(table) {
    const headers = [];

    table.querySelectorAll(':scope > thead > tr:last-child > th').forEach((th) => {
        const label = th.querySelector('.sr-only') ? '' : th.textContent.trim().replace(/\s+/g, ' ');

        for (let i = 0; i < (th.colSpan || 1); i++) {
            headers.push(label);
        }
    });

    if (! headers.length) {
        return;
    }

    table.querySelectorAll(':scope > tbody > tr').forEach((row) => {
        let index = 0;

        [...row.children].forEach((cell) => {
            const label = cell.colSpan > 1 ? '' : (headers[index] ?? '');

            if (cell.getAttribute('data-label') !== label) {
                cell.setAttribute('data-label', label);
            }

            index += cell.colSpan || 1;
        });
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
    }).observe(main, { childList: true, subtree: true });
}
