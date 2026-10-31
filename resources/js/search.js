// Wyszukiwarka całej strony: indeks pobierany raz, szukanie w przeglądarce (natychmiast).

const MAX_RESULTS = 30;

const normalize = (text) => (text || '')
    .toLowerCase()
    .replace(/ł/g, 'l')
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();

const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

let indexPromise = null;

const loadIndex = (url) => {
    indexPromise ??= fetch(url, { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((entries) => entries.map((entry) => ({
            ...entry,
            normTitle: normalize(entry.title),
            haystack: `${normalize(entry.title)} ${normalize(entry.subtitle)} ${normalize(entry.type)} ${entry.text}`,
        })))
        .catch(() => {
            indexPromise = null;
            return [];
        });

    return indexPromise;
};

const search = (entries, query) => {
    const terms = normalize(query).split(' ').filter(Boolean);

    if (!terms.length) {
        return [];
    }

    const results = [];

    for (const entry of entries) {
        let score = 0;
        let matched = true;

        for (const term of terms) {
            if (!entry.haystack.includes(term)) {
                matched = false;
                break;
            }

            if (entry.normTitle.startsWith(term)) score += 30;
            else if ((' ' + entry.normTitle).includes(' ' + term)) score += 20;
            else if (entry.normTitle.includes(term)) score += 10;
            else score += 1;
        }

        if (matched) {
            results.push({ entry, score });
        }
    }

    return results.sort((a, b) => b.score - a.score).slice(0, MAX_RESULTS).map((r) => r.entry);
};

// Podświetla wpisane słowa w tytule (z uwzględnieniem polskich znaków).
const highlight = (title, query) => {
    const terms = normalize(query).split(' ').filter((t) => t.length > 1);
    const norm = title.toLowerCase().replace(/ł/g, 'l').normalize('NFD').replace(/[̀-ͯ]/g, '');
    const marks = new Array(title.length).fill(false);

    terms.forEach((term) => {
        let from = 0;
        let at;
        while ((at = norm.indexOf(term, from)) !== -1) {
            for (let i = at; i < at + term.length && i < marks.length; i++) marks[i] = true;
            from = at + term.length;
        }
    });

    let html = '';
    let open = false;
    [...title].forEach((char, i) => {
        if (marks[i] && !open) { html += '<mark class="rounded bg-sky-club/20 px-0.5 text-inherit">'; open = true; }
        if (!marks[i] && open) { html += '</mark>'; open = false; }
        html += escapeHtml(char);
    });

    return open ? html + '</mark>' : html;
};

export function initSearch() {
    const dialog = document.querySelector('[data-search-dialog]');

    if (!dialog || typeof dialog.showModal !== 'function') {
        return; // Bez obsługi <dialog> zostaje zwykła strona /szukaj.
    }

    const input = dialog.querySelector('[data-search-input]');
    const list = dialog.querySelector('[data-search-results]');
    const empty = dialog.querySelector('[data-search-empty]');
    const allLink = dialog.querySelector('[data-search-all]');
    const indexUrl = dialog.dataset.indexUrl;
    const pageUrl = dialog.dataset.pageUrl;
    let active = -1;

    const items = () => [...list.querySelectorAll('a')];

    const setActive = (index) => {
        const links = items();
        active = Math.max(-1, Math.min(index, links.length - 1));
        links.forEach((link, i) => link.toggleAttribute('data-active', i === active));
        links[active]?.scrollIntoView({ block: 'nearest' });
    };

    const render = async () => {
        const query = input.value;
        const entries = await loadIndex(indexUrl);
        const results = search(entries, query);
        allLink.href = `${pageUrl}?q=${encodeURIComponent(query)}`;
        allLink.classList.toggle('hidden', !query.trim());
        empty.classList.toggle('hidden', !query.trim() || results.length > 0);

        // Grupy w kolejności najlepszego trafienia (najtrafniejsza grupa na górze).
        const groups = [...new Set(results.map((r) => r.type))]
            .map((type) => [type, results.filter((r) => r.type === type)]);

        list.innerHTML = groups.map(([type, group]) => `
            <li class="px-2 pt-4 pb-1 text-[11px] font-semibold tracking-widest text-zinc-400 uppercase">${type}</li>
            ${group.map((r) => `
                <li><a href="${escapeHtml(r.url)}" class="flex flex-col rounded-xl px-3 py-3 transition hover:bg-zinc-100 data-active:bg-sky-club/10">
                    <span class="font-semibold text-zinc-900">${highlight(r.title, query)}</span>
                    ${r.subtitle ? `<span class="text-sm text-zinc-500">${escapeHtml(r.subtitle)}</span>` : ''}
                </a></li>`).join('')}
        `).join('');
        setActive(-1);
    };

    const open = (event) => {
        event?.preventDefault();
        dialog.showModal();
        document.documentElement.classList.add('overflow-hidden');
        input.focus();
        input.select();
        loadIndex(indexUrl);
    };

    const close = () => dialog.close();

    dialog.addEventListener('close', () => document.documentElement.classList.remove('overflow-hidden'));
    dialog.addEventListener('click', (event) => { if (event.target === dialog) close(); });
    dialog.querySelectorAll('[data-search-close]').forEach((button) => button.addEventListener('click', close));

    document.querySelectorAll('[data-search-open]').forEach((trigger) => trigger.addEventListener('click', open));

    // Szybki skrót: "/" albo Ctrl/Cmd + K.
    document.addEventListener('keydown', (event) => {
        const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) || document.activeElement?.isContentEditable;
        if ((event.key === '/' && !typing) || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k')) {
            open(event);
        }
    });

    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(render, 60);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') { event.preventDefault(); setActive(active + 1); }
        if (event.key === 'ArrowUp') { event.preventDefault(); setActive(active - 1); }
        if (event.key === 'Enter') {
            event.preventDefault();
            const target = items()[active] ?? items()[0];
            window.location.href = target ? target.href : allLink.href;
        }
    });

    // Pobierz indeks w wolnej chwili, żeby pierwsze wyszukiwanie było natychmiastowe.
    const whenIdle = window.requestIdleCallback
        ? (callback) => window.requestIdleCallback(callback, { timeout: 4000 })
        : (callback) => setTimeout(callback, 2500);
    whenIdle(() => loadIndex(indexUrl));
}
