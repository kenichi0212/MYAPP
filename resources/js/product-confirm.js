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

    const form = document.getElementById('product-confirm-form');
    const feedback = document.getElementById('submit-feedback');
    const submitButton = document.getElementById('product-confirm-submit');

    if (! form || ! feedback) {
        return;
    }

    const showFeedback = (message, isError) => {
        feedback.textContent = message;
        feedback.classList.remove('hidden', 'bg-success/10', 'text-success', 'bg-danger/10', 'text-danger');
        feedback.classList.add(...(isError ? ['bg-danger/10', 'text-danger'] : ['bg-success/10', 'text-success']));
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (! form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const payload = {
            store_id: formData.get('store_id'),
            jan_code: formData.get('jan_code'),
            product_name: formData.get('product_name'),
            maker_name: formData.get('maker_name'),
            name_source: formData.get('name_source'),
            expiry_date: formData.get('expiry_date'),
            quantity: formData.get('quantity'),
            is_zero_report: zeroReportCheckbox?.checked ?? false,
        };

        submitButton?.setAttribute('disabled', 'disabled');

        try {
            const response = await fetch(form.dataset.submitUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(payload),
            });

            if (response.ok) {
                showFeedback('登録しました。', false);
                form.reset();
            } else {
                const body = await response.json().catch(() => ({}));
                const message = body.message ?? '登録に失敗しました。入力内容をご確認ください。';
                showFeedback(message, true);
            }
        } catch (error) {
            showFeedback('通信エラーが発生しました。' + error.message, true);
        } finally {
            submitButton?.removeAttribute('disabled');
        }
    });
});
