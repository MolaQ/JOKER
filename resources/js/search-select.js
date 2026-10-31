/**
 * Lista do wyboru z wyszukiwarką (x-search-select): zamiast długiego <select> pole, w którym wpisuje się
 * fragment nazwy (bez polskich znaków też działa), a pod nim pasujące pozycje pogrupowane jak w <select>.
 * Wartość trafia do właściwości komponentu Livewire (przez $entangle).
 */
const normalize = (text) => String(text ?? '').toLocaleLowerCase('pl').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/ł/g, 'l');

export function registerSearchSelect() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('searchSelect', (value, options = [], emptyLabel = '') => ({
            value,
            options: options.map((option) => ({ ...option, search: normalize(`${option.label} ${option.group ?? ''}`) })),
            emptyLabel,
            query: '',
            open: false,
            active: 0,

            get selected() {
                return this.options.find((option) => String(option.value) === String(this.value ?? '')) ?? null;
            },

            get matches() {
                const words = normalize(this.query).split(/\s+/).filter(Boolean);

                return this.options.filter((option) => words.every((word) => option.search.includes(word))).slice(0, 200);
            },

            groupStarts(index) {
                const list = this.matches;

                return list[index].group && (index === 0 || list[index - 1].group !== list[index].group);
            },

            show() {
                this.open = true;
                this.active = 0;
            },

            choose(option) {
                this.value = option ? option.value : '';
                this.query = '';
                this.open = false;
            },

            move(step) {
                if (! this.open) {
                    this.show();

                    return;
                }

                const count = this.matches.length;

                if (count) {
                    this.active = (this.active + step + count) % count;
                    this.$nextTick(() => this.$refs.list?.querySelector('[data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
                }
            },

            pick() {
                if (this.open && this.matches[this.active]) {
                    this.choose(this.matches[this.active]);
                }
            },
        }));
    });
}
