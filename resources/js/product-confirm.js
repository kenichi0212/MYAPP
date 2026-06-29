document.addEventListener('DOMContentLoaded', () => {
    const zeroReportCheckbox = document.getElementById('is-zero-report-input');
    const quantityInput = document.getElementById('quantity-input');
    const expiryDateInput = document.getElementById('expiry-date-input');
    const expiryDateError = document.getElementById('expiry-date-error');

    if (zeroReportCheckbox && quantityInput) {
        zeroReportCheckbox.addEventListener('change', () => {
            if (zeroReportCheckbox.checked) {
                quantityInput.value = '0';
                quantityInput.readOnly = true;
            } else {
                quantityInput.readOnly = false;
            }
        });
    }

    if (expiryDateInput && expiryDateError) {
        const validateExpiryDate = () => {
            const today = new Date().toISOString().slice(0, 10);
            const isPast = expiryDateInput.value !== '' && expiryDateInput.value < today;

            expiryDateError.classList.toggle('hidden', ! isPast);
            expiryDateInput.setCustomValidity(isPast ? '賞味期限に過去の日付は登録できません。' : '');
        };

        expiryDateInput.addEventListener('input', validateExpiryDate);
        expiryDateInput.addEventListener('blur', validateExpiryDate);
    }
});
