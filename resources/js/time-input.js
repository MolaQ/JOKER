/**
 * Pola godziny zawsze w formacie 24-godzinnym (x-time-input, x-datetime-input). Wbudowane pola przeglądarki
 * pokazują AM/PM, gdy system jest po angielsku, więc godzinę wpisuje się tu jako tekst „GG:MM”.
 * Wartość trafia do właściwości Livewire (przez $entangle) w tym samym formacie co dotąd:
 * „10:45” albo „2026-11-15T10:45”.
 */
const clean = (text) => {
    const digits = String(text ?? '').replace(/\D/g, '').slice(0, 4);

    return digits.length > 2 ? `${digits.slice(0, 2)}:${digits.slice(2)}` : digits;
};

const normalizeTime = (text) => {
    const match = String(text ?? '').trim().match(/^(\d{1,2})(?::?(\d{2}))?$/);

    if (!match || Number(match[1]) > 23 || Number(match[2] ?? 0) > 59) {
        return null;
    }

    return `${match[1].padStart(2, '0')}:${match[2] ?? '00'}`;
};

const timeField = {
    mask(event) {
        const typed = event.target.value;
        // Po dwóch cyfrach sam dopisuje dwukropek; kasowanie działa normalnie.
        if (event.inputType && event.inputType.startsWith('delete')) {
            return;
        }
        event.target.value = clean(typed);
        this.time = event.target.value;
    },
};

export function registerTimeInputs() {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('time24', (value) => ({
            value,
            time: '',
            invalid: false,
            ...timeField,
            init() {
                this.time = this.value ? String(this.value).slice(0, 5) : '';
                this.$watch('value', (current) => {
                    if (document.activeElement !== this.$refs.time) {
                        this.time = current ? String(current).slice(0, 5) : '';
                    }
                });
            },
            commit() {
                if (this.time.trim() === '') {
                    this.invalid = false;
                    this.value = null;

                    return;
                }
                const time = normalizeTime(this.time);
                this.invalid = time === null;
                if (time) {
                    this.time = time;
                    this.value = time;
                }
            },
        }));

        window.Alpine.data('datetime24', (value) => ({
            value,
            date: '',
            time: '',
            invalid: false,
            ...timeField,
            init() {
                this.read(this.value);
                this.$watch('value', (current) => {
                    if (!this.$el.contains(document.activeElement)) {
                        this.read(current);
                    }
                });
            },
            read(current) {
                const [date, time] = String(current ?? '').replace(' ', 'T').split('T');
                this.date = date ?? '';
                this.time = time ? time.slice(0, 5) : '';
            },
            commit() {
                if (this.date === '' && this.time.trim() === '') {
                    this.invalid = false;
                    this.value = null;

                    return;
                }
                const time = this.time.trim() === '' ? '00:00' : normalizeTime(this.time);
                this.invalid = time === null || this.date === '';
                if (!this.invalid) {
                    this.time = time;
                    this.value = `${this.date}T${time}`;
                }
            },
        }));
    });
}
