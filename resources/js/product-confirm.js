document.addEventListener('DOMContentLoaded', () => {
    const zeroReportCheckbox = document.getElementById('is-zero-report-input');
    const quantityInput = document.getElementById('quantity-input');
    const expiryDateInput = document.getElementById('expiry-date-input');
    const expiryDateError = document.getElementById('expiry-date-error');
    const form = document.getElementById('product-confirm-form');
    const feedback = document.getElementById('submit-feedback');
    const submitButton = document.getElementById('product-confirm-submit');
    const duplicateDialog = document.getElementById('duplicate-dialog');
    const existingQuantityDisplay = document.getElementById('existing-quantity-display');

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

    if (! form || ! feedback) {
        return;
    }

    const showFeedback = (message, isError) => {
        feedback.textContent = message;
        feedback.classList.remove('hidden', 'bg-success/10', 'text-success', 'bg-danger/10', 'text-danger');
        feedback.classList.add(...(isError ? ['bg-danger/10', 'text-danger'] : ['bg-success/10', 'text-success']));
    };

    const buildPayload = (quantityMode = null) => {
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
        if (quantityMode) {
            payload.quantity_mode = quantityMode;
        }
        return payload;
    };

    const postCheckLog = (payload) =>
        fetch(form.dataset.submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(payload),
        });

    const showDuplicateDialog = (existingQuantity) => {
        if (existingQuantityDisplay) {
            existingQuantityDisplay.textContent = existingQuantity;
        }
        duplicateDialog?.classList.remove('hidden');
    };

    const hideDuplicateDialog = () => {
        duplicateDialog?.classList.add('hidden');
    };

    const handleSubmit = async (quantityMode = null) => {
        submitButton?.setAttribute('disabled', 'disabled');

        try {
            const response = await postCheckLog(buildPayload(quantityMode));

            if (response.status === 409) {
                const body = await response.json().catch(() => ({}));
                showDuplicateDialog(body.existing_quantity ?? 0);
                return; // ボタンはダイアログ解決まで無効のまま
            }

            if (response.ok) {
                showFeedback('登録しました。次の商品をスキャンしてください。', false);
                const redirectUrl = form.dataset.redirectUrl ?? '/barcode-scan';
                setTimeout(() => { window.location.href = redirectUrl; }, 1500);
            } else {
                const body = await response.json().catch(() => ({}));
                showFeedback(body.message ?? '登録に失敗しました。入力内容をご確認ください。', true);
            }
        } catch {
            showFeedback('通信エラーが発生しました。しばらく時間をおいて再度お試しください。', true);
        } finally {
            if (duplicateDialog?.classList.contains('hidden')) {
                submitButton?.removeAttribute('disabled');
            }
        }
    };

    document.getElementById('duplicate-add-btn')?.addEventListener('click', async () => {
        hideDuplicateDialog();
        await handleSubmit('add');
    });

    document.getElementById('duplicate-separate-btn')?.addEventListener('click', async () => {
        hideDuplicateDialog();
        await handleSubmit('separate');
    });

    document.getElementById('duplicate-cancel-btn')?.addEventListener('click', () => {
        hideDuplicateDialog();
        submitButton?.removeAttribute('disabled');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (! form.checkValidity()) {
            form.reportValidity();
            return;
        }

        await handleSubmit();
    });
});
