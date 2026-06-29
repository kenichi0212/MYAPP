document.addEventListener('DOMContentLoaded', () => {
    const zeroReportCheckbox = document.getElementById('is-zero-report-input');
    const quantityInput = document.getElementById('quantity-input');

    if (! zeroReportCheckbox || ! quantityInput) {
        return;
    }

    zeroReportCheckbox.addEventListener('change', () => {
        if (zeroReportCheckbox.checked) {
            quantityInput.value = '0';
            quantityInput.readOnly = true;
        } else {
            quantityInput.readOnly = false;
        }
    });
});
