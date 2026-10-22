// Tables are laid out as grids on wide screens and stacked cards on phones.
// Copy each column header onto its cells so the card layout can label them.
document.querySelectorAll('.t-min').forEach((table) => {
    const head = table.querySelector('.t-head');

    if (!head) {
        return;
    }

    const labels = Array.from(head.children, (cell) => ({
        text: cell.textContent.trim(),
        // Brand names like "BrainCoin" keep their own spelling in the uppercase label.
        keepCase: cell.querySelector('.brand-name') !== null,
    }));

    table.querySelectorAll('.t-row').forEach((row) => {
        Array.from(row.children).forEach((cell, i) => {
            if (labels[i]?.text) {
                cell.dataset.label = labels[i].text;

                if (labels[i].keepCase) {
                    cell.dataset.labelKeepCase = '';
                }
            }
        });
    });
});

// Alpine component behind <x-file-picker>: drag and drop, file preview,
// and the same type/size limits the server enforces, checked before upload.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('filePicker', ({ maxMb, extensions, types }) => ({
        file: null,
        error: '',
        dragging: false,

        pick(file) {
            this.error = '';
            this.file = null;

            if (!file) {
                return;
            }

            const ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';

            if (!extensions.includes(ext)) {
                this.error = (window.ERP_I18N?.fileTypes ?? 'Faqat :types fayl yuklash mumkin.').replace(':types', types);
                this.$refs.input.value = '';
                return;
            }

            if (file.size > maxMb * 1024 * 1024) {
                this.error = (window.ERP_I18N?.fileSize ?? 'Fayl hajmi :max MB dan oshmasligi kerak.').replace(':max', maxMb);
                this.$refs.input.value = '';
                return;
            }

            const mb = file.size / 1024 / 1024;

            this.file = {
                name: file.name,
                ext,
                size: mb >= 1 ? `${mb.toFixed(1)} MB` : `${Math.max(1, Math.round(file.size / 1024))} KB`,
            };
        },

        drop(event) {
            this.dragging = false;

            const files = event.dataTransfer.files;

            if (!files.length) {
                return;
            }

            this.$refs.input.files = files;
            this.pick(files[0]);
        },

        clear() {
            this.$refs.input.value = '';
            this.file = null;
            this.error = '';
        },
    }));

    window.Alpine.data('oneSubmit', oneSubmit);
});

// Stops a second click from sending the same form twice.
function oneSubmit() {
    return {
        submitting: false,

        guard(event) {
            if (this.submitting) {
                event.preventDefault();
                return;
            }

            this.submitting = true;
        },
    };
}

// Calendar behind <x-date-input>: Uzbek month and day names, weeks from Monday,
// shown as dd.mm.yyyy while the form sends yyyy-mm-dd.
// Month and weekday names come from the server (layouts/partials/i18n-js); Uzbek is the fallback.
const UZ_MONTHS = window.ERP_I18N?.months ?? ['Yanvar', 'Fevral', 'Mart', 'Aprel', 'May', 'Iyun', 'Iyul', 'Avgust', 'Sentabr', 'Oktabr', 'Noyabr', 'Dekabr'];
const UZ_WEEKDAYS = window.ERP_I18N?.weekdays ?? ['Du', 'Se', 'Ch', 'Pa', 'Ju', 'Sh', 'Ya'];

const pad2 = (n) => String(n).padStart(2, '0');
const toIso = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
const fromIso = (iso) => {
    const [y, m, d] = iso.split('-').map(Number);
    return new Date(y, m - 1, d);
};

function datePicker({ id, value, min, max, autoSubmit }) {
    const start = value ? fromIso(value) : (max && toIso(new Date()) > max ? fromIso(max) : new Date());

    return {
        id,
        value: value || '',
        min,
        max,
        open: false,
        viewYear: start.getFullYear(),
        viewMonth: start.getMonth(),
        weekdays: UZ_WEEKDAYS,
        todayIso: toIso(new Date()),

        get label() {
            if (!this.value) {
                return '';
            }

            const [y, m, d] = this.value.split('-');

            return `${d}.${m}.${y}`;
        },

        get monthName() {
            return UZ_MONTHS[this.viewMonth];
        },

        get years() {
            const now = new Date().getFullYear();
            const from = Math.min(this.min ? Number(this.min.slice(0, 4)) : now - 80, this.viewYear);
            const to = Math.max(this.max ? Number(this.max.slice(0, 4)) : now + 5, this.viewYear);

            return Array.from({ length: to - from + 1 }, (_, i) => to - i);
        },

        get cells() {
            const first = new Date(this.viewYear, this.viewMonth, 1);
            const offset = (first.getDay() + 6) % 7; // Monday = 0
            const cells = [];

            for (let i = 0; i < 42; i++) {
                const date = new Date(this.viewYear, this.viewMonth, 1 - offset + i);
                const iso = toIso(date);

                cells.push({
                    iso,
                    day: date.getDate(),
                    inMonth: date.getMonth() === this.viewMonth,
                    today: iso === this.todayIso,
                    disabled: this.isDisabled(iso),
                    aria: `${(window.ERP_I18N?.dayMonth ?? ':day-:month').replace(':day', date.getDate()).replace(':month', (window.ERP_I18N?.monthsGenitive ?? UZ_MONTHS.map((m) => m.toLowerCase()))[date.getMonth()])} ${date.getFullYear()}`,
                });
            }

            return cells;
        },

        isDisabled(iso) {
            return (this.min && iso < this.min) || (this.max && iso > this.max);
        },

        toggle() {
            this.open = !this.open;

            if (this.open && this.value) {
                const d = fromIso(this.value);
                this.viewYear = d.getFullYear();
                this.viewMonth = d.getMonth();
            }
        },

        shift(months) {
            const d = new Date(this.viewYear, this.viewMonth + months, 1);
            this.viewYear = d.getFullYear();
            this.viewMonth = d.getMonth();
        },

        select(iso) {
            if (iso && this.isDisabled(iso)) {
                return;
            }

            this.value = iso;
            this.open = false;

            if (iso) {
                const d = fromIso(iso);
                this.viewYear = d.getFullYear();
                this.viewMonth = d.getMonth();
            }

            if (autoSubmit) {
                this.$nextTick(() => this.$root.closest('form')?.requestSubmit());
            }
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('datePicker', datePicker);
});

// The browser's validation bubbles ("Please fill out this field") come in the browser's own language.
// Replace their text with the interface language; the checks themselves stay native.
(() => {
    const messages = () => window.ERP_I18N?.validity ?? {};

    document.addEventListener('invalid', (event) => {
        const field = event.target;
        const state = field?.validity;

        if (!state) {
            return;
        }

        const text = messages();
        let message = '';

        if (state.valueMissing) {
            message = field.type === 'file' ? text.file
                : field.tagName === 'SELECT' ? text.select
                    : field.type === 'checkbox' ? text.checkbox
                        : text.required;
        } else if (state.typeMismatch && field.type === 'email') {
            message = text.email;
        } else if (state.tooShort) {
            message = (text.tooShort ?? '').replace(':min', field.minLength);
        } else if (state.rangeUnderflow) {
            message = (text.min ?? '').replace(':min', field.min);
        } else if (state.rangeOverflow) {
            message = (text.max ?? '').replace(':max', field.max);
        } else if (state.patternMismatch) {
            message = text.pattern;
        } else if (state.badInput || state.stepMismatch) {
            message = text.number;
        }

        if (message) {
            field.setCustomValidity(message);
        }
    }, true);

    // A custom message would otherwise keep the field invalid after the user fixes it.
    const clear = (event) => event.target?.setCustomValidity?.('');
    document.addEventListener('input', clear, true);
    document.addEventListener('change', clear, true);
})();
