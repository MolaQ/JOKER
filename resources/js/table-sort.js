// Sortowanie tabel bez przeładowania: tabela z data-sortable sortuje się kliknięciem nagłówka
// (pierwsze kliknięcie rosnąco, drugie malejąco, trzecie wraca do kolejności z serwera).
// Nagłówek z data-nosort nie sortuje; komórka może podać własną wartość w data-sort.
// Listy dzielone na strony sortuje serwer (App\Livewire\Concerns\SortsTable).

const collator = new Intl.Collator('pl', { numeric: true, sensitivity: 'base' });

function cellValue(row, index) {
    let column = 0;

    for (const cell of row.children) {
        if (column === index) {
            return (cell.dataset.sort ?? cell.textContent).trim().replace(/\s+/g, ' ');
        }

        column += cell.colSpan || 1;
    }

    return '';
}

function parse(value) {
    if (value === '' || value === '—' || value === '-') {
        return null;
    }

    const date = value.match(/^(\d{1,2})\.(\d{1,2})\.(\d{4})/);

    if (date) {
        return Number(date[3] + date[2].padStart(2, '0') + date[1].padStart(2, '0'));
    }

    // Stosunek, np. sety 6:2 albo małe punkty 228:225.
    const ratio = value.match(/^(\d+)\s*:\s*(\d+)$/);

    if (ratio) {
        return Number(ratio[2]) === 0 ? Number(ratio[1]) * 1000 + 1e9 : Number(ratio[1]) / Number(ratio[2]);
    }

    const number = value.replace(/\s/g, '').replace(',', '.').match(/^[+-]?\d+(\.\d+)?%?$/);

    return number ? parseFloat(number[0]) : value;
}

function compare(a, b) {
    if (a === null || b === null) {
        return a === b ? 0 : (a === null ? 1 : -1);
    }

    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }

    return collator.compare(String(a), String(b));
}

function columnIndex(th) {
    let index = 0;

    for (const cell of th.parentElement.children) {
        if (cell === th) {
            return index;
        }

        index += cell.colSpan || 1;
    }

    return -1;
}

function sortTable(table, th) {
    const index = columnIndex(th);
    const next = { none: 'ascending', ascending: 'descending', descending: 'none' }[th.getAttribute('aria-sort') ?? 'none'];

    table.querySelectorAll(':scope > thead th[aria-sort]').forEach((cell) => cell.removeAttribute('aria-sort'));

    if (next !== 'none') {
        th.setAttribute('aria-sort', next);
    }

    table.querySelectorAll(':scope > tbody').forEach((tbody) => {
        const rows = [...tbody.children];

        rows.forEach((row, i) => {
            row.dataset.sortOrigin ??= String(i);
        });

        const sorted = rows
            .map((row) => ({ row, value: parse(cellValue(row, index)) }))
            .sort((a, b) => {
                if (next === 'none') {
                    return Number(a.row.dataset.sortOrigin) - Number(b.row.dataset.sortOrigin);
                }

                // Puste wartości zawsze na końcu, niezależnie od kierunku.
                if (a.value === null || b.value === null) {
                    return compare(a.value, b.value);
                }

                const result = compare(a.value, b.value);

                return next === 'ascending' ? result : -result;
            });

        sorted.forEach(({ row }) => tbody.appendChild(row));
    });
}

export function initTableSort() {
    document.addEventListener('click', (event) => {
        const th = event.target.closest('table[data-sortable] > thead th:not([data-nosort])');

        if (! th || event.target.closest('a, button, input, select')) {
            return;
        }

        sortTable(th.closest('table'), th);
    });

    document.addEventListener('keydown', (event) => {
        const th = event.target.closest?.('table[data-sortable] > thead th:not([data-nosort])');

        if (th && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault();
            sortTable(th.closest('table'), th);
        }
    });

    // Nagłówki dostępne z klawiatury.
    const prepare = () => document.querySelectorAll('table[data-sortable] > thead th:not([data-nosort]):not([tabindex])').forEach((th) => {
        th.tabIndex = 0;
        th.title ||= 'Sortuj';
    });

    prepare();
    document.addEventListener('livewire:navigated', prepare);
    let scheduled = false;
    new MutationObserver(() => {
        if (! scheduled) {
            scheduled = true;
            requestAnimationFrame(() => {
                scheduled = false;
                prepare();
            });
        }
    }).observe(document.body, { childList: true, subtree: true });
}
