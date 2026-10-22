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
