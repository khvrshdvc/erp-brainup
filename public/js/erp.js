// Tables are laid out as grids on wide screens and stacked cards on phones.
// Copy each column header onto its cells so the card layout can label them.
document.querySelectorAll('.t-min').forEach((table) => {
    const head = table.querySelector('.t-head');

    if (!head) {
        return;
    }

    const labels = Array.from(head.children, (cell) => cell.textContent.trim());

    table.querySelectorAll('.t-row').forEach((row) => {
        Array.from(row.children).forEach((cell, i) => {
            if (labels[i]) {
                cell.dataset.label = labels[i];
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
                this.error = `Faqat ${types} fayl yuklash mumkin.`;
                this.$refs.input.value = '';
                return;
            }

            if (file.size > maxMb * 1024 * 1024) {
                this.error = `Fayl hajmi ${maxMb} MB dan oshmasligi kerak.`;
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
