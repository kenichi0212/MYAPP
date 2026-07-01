import { BrowserMultiFormatReader, NotFoundException } from '@zxing/library';

const JAN_CODE_PATTERN = /^\d{8}$|^\d{13}$/;

const SOURCE_LABELS = { master: '自社マスタ', api: '外部API取得', manual: '手入力' };
const SOURCE_CLASSES = {
    master: 'bg-success/10 text-success border-success',
    api: 'bg-warning/10 text-warning border-warning',
    manual: 'bg-gray-100 text-gray-600 border-gray-300',
};

document.addEventListener('DOMContentLoaded', () => {
    const videoEl              = document.getElementById('scanner-video');
    if (! videoEl) return;

    // --- スキャナー関連 ---
    const scannerSection       = document.getElementById('scanner-section');
    const statusEl             = document.getElementById('scanner-status');
    const manualToggle         = document.getElementById('manual-input-toggle');
    const manualForm           = document.getElementById('manual-input-form');
    const manualInput          = document.getElementById('manual-jan-code');
    const manualSubmit         = document.getElementById('manual-jan-submit');
    const manualError          = document.getElementById('manual-jan-error');

    // --- 商品フォーム関連 ---
    const productFormSection   = document.getElementById('product-form-section');
    const productJanDisplay    = document.getElementById('product-jan-display');
    const nameSourceBadge      = document.getElementById('name-source-badge');
    const productNotFoundMsg   = document.getElementById('product-not-found-msg');
    const form                 = document.getElementById('product-confirm-form');
    const formJanCode          = document.getElementById('form-jan-code');
    const formNameSource       = document.getElementById('form-name-source');
    const productNameInput     = document.getElementById('product-name-input');
    const makerNameInput       = document.getElementById('maker-name-input');
    const expiryDateInput      = document.getElementById('expiry-date-input');
    const expiryDateError      = document.getElementById('expiry-date-error');
    const quantityInput        = document.getElementById('quantity-input');
    const submitButton         = document.getElementById('product-confirm-submit');
    const feedback             = document.getElementById('submit-feedback');
    const backToScanBtn        = document.getElementById('back-to-scan-btn');
    const storeSelect          = document.getElementById('store-id-select');
    const storeNameInput       = document.getElementById('store-name-input');
    const storeNameError       = document.getElementById('store-name-error');
    const storeMap             = window.__storeMap ?? {};

    // --- 重複ダイアログ ---
    const duplicateDialog          = document.getElementById('duplicate-dialog');
    const existingQuantityDisplay  = document.getElementById('existing-quantity-display');

    const reader = new BrowserMultiFormatReader();
    let stopped = false;
    let scannedJanCode = null;

    // ─── ヘルパー ───────────────────────────────────────────────

    const csrfToken = () =>
        document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    const setStatus = (msg) => { if (statusEl) statusEl.textContent = msg; };

    const showScanner = () => {
        scannerSection.classList.remove('hidden');
        productFormSection.classList.add('hidden');
        videoEl.classList.remove('hidden');
        feedback.classList.add('hidden');
    };

    const showProductForm = () => {
        scannerSection.classList.add('hidden');
        productFormSection.classList.remove('hidden');
    };

    const showFeedback = (message, isError) => {
        feedback.textContent = message;
        feedback.classList.remove('hidden', 'bg-success/10', 'text-success', 'bg-danger/10', 'text-danger');
        feedback.classList.add(...(isError
            ? ['bg-danger/10', 'text-danger']
            : ['bg-success/10', 'text-success']));
    };

    const showDuplicateDialog = (existingQty) => {
        if (existingQuantityDisplay) existingQuantityDisplay.textContent = existingQty;
        duplicateDialog?.classList.remove('hidden');
    };

    const hideDuplicateDialog = () => duplicateDialog?.classList.add('hidden');

    // ─── スキャナー ───────────────────────────────────────────────

    const startScanning = () => {
        stopped = false;
        scannedJanCode = null;
        setStatus('カメラを起動しています…');

        reader
            .decodeFromConstraints(
                { video: { facingMode: 'environment' } },
                videoEl,
                (result, error) => {
                    if (stopped) return;

                    if (result) {
                        handleScanResult(result.getText());
                        return;
                    }

                    if (error && ! (error instanceof NotFoundException)) {
                        setStatus('読み取り中にエラーが発生しました。再読取するか、JANコードを手入力してください。');
                    } else {
                        setStatus('バーコードをカメラに近づけてください。');
                    }
                }
            )
            .catch(() => {
                setStatus('カメラを利用できませんでした。カメラへのアクセスを許可するか、JANコードを手入力してください。');
                manualForm?.classList.remove('hidden');
            });
    };

    // スキャン成功：動画を隠して即座に商品情報を取得・フォーム表示
    const handleScanResult = async (janCode) => {
        stopped = true;
        reader.reset();
        scannedJanCode = janCode;
        setStatus('読み取りに成功しました。商品情報を取得しています…');
        videoEl.classList.add('hidden');
        await loadProductAndShowForm(janCode);
    };

    // 「再スキャンに戻る」（フォームから）
    backToScanBtn?.addEventListener('click', () => {
        showScanner();
        startScanning();
    });

    // ─── 手入力 ───────────────────────────────────────────────────

    manualToggle?.addEventListener('click', () => {
        manualForm?.classList.toggle('hidden');
    });

    manualSubmit?.addEventListener('click', async () => {
        const janCode = (manualInput?.value ?? '').trim();
        if (! JAN_CODE_PATTERN.test(janCode)) {
            manualError?.classList.remove('hidden');
            return;
        }
        manualError?.classList.add('hidden');
        await handleScanResult(janCode);
    });

    // ─── 商品情報取得＆フォーム表示 ───────────────────────────────

    const loadProductAndShowForm = async (janCode) => {
        const lookupUrl = form.dataset.lookupUrl + '?jan_code=' + encodeURIComponent(janCode);
        let productData;

        try {
            const res = await fetch(lookupUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                },
            });
            if (! res.ok) throw new Error();
            productData = await res.json();
        } catch {
            setStatus('商品情報の取得に失敗しました。再度お試しください。');
            showScanner();
            return;
        }

        // JAN・取得元バッジ
        formJanCode.value = janCode;
        productJanDisplay.textContent = janCode;

        const source = productData.product?.name_source ?? 'manual';
        formNameSource.value = source;
        nameSourceBadge.textContent = '取得元：' + (SOURCE_LABELS[source] ?? '手入力');
        nameSourceBadge.className = 'text-xs font-semibold px-2 py-1 rounded-full border '
            + (SOURCE_CLASSES[source] ?? SOURCE_CLASSES.manual);

        // フォーム値
        productNameInput.value = productData.product?.product_name ?? '';
        makerNameInput.value = productData.product?.maker_name ?? '';

        // 該当なしメッセージ
        productNotFoundMsg.classList.toggle('hidden', productData.found !== false);

        // 入力値リセット
        expiryDateInput.value = '';
        expiryDateInput.setCustomValidity('');
        expiryDateError.classList.add('hidden');
        quantityInput.value = '';
        quantityInput.readOnly = false;
        feedback.classList.add('hidden');
        submitButton?.removeAttribute('disabled');

        showProductForm();
    };

    // ─── フォームバリデーション ────────────────────────────────────

    const validateExpiryDate = () => {
        const today = new Date().toISOString().slice(0, 10);
        const isPast = expiryDateInput.value !== '' && expiryDateInput.value < today;
        expiryDateError.classList.toggle('hidden', ! isPast);
        expiryDateInput.setCustomValidity(isPast ? '賞味期限に過去の日付は登録できません。' : '');
    };

    expiryDateInput?.addEventListener('input', validateExpiryDate);
    expiryDateInput?.addEventListener('blur', validateExpiryDate);

    // 店舗名入力 → hidden store_id を更新
    const resolveStoreId = () => {
        const name = storeNameInput?.value.trim() ?? '';
        const id   = storeMap[name];
        if (storeSelect) storeSelect.value = id ?? '';
        storeNameError?.classList.toggle('hidden', !! id || name === '');
        return !! id;
    };
    storeNameInput?.addEventListener('change', resolveStoreId);
    storeNameInput?.addEventListener('blur',   resolveStoreId);



    // ─── 登録送信 ─────────────────────────────────────────────────

    const buildPayload = (quantityMode = null) => {
        const payload = {
            store_id: storeSelect?.value,
            jan_code: formJanCode.value,
            product_name: productNameInput.value,
            maker_name: makerNameInput.value,
            name_source: formNameSource.value,
            expiry_date: expiryDateInput.value,
            quantity: quantityInput.value,
            is_zero_report: false,
        };
        if (quantityMode) payload.quantity_mode = quantityMode;
        return payload;
    };

    const postCheckLog = (payload) =>
        fetch(form.dataset.submitUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify(payload),
        });

    const handleSubmit = async (quantityMode = null) => {
        if (! resolveStoreId()) {
            storeNameInput?.focus();
            return;
        }
        submitButton?.setAttribute('disabled', 'disabled');

        try {
            const response = await postCheckLog(buildPayload(quantityMode));

            if (response.status === 409) {
                const body = await response.json().catch(() => ({}));
                showDuplicateDialog(body.existing_quantity ?? 0);
                return;
            }

            if (response.ok) {
                showFeedback('登録しました。次の商品をスキャンしてください。', false);
                setTimeout(() => {
                    showScanner();
                    startScanning();
                }, 1500);
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

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (! form.checkValidity()) {
            form.reportValidity();
            return;
        }
        await handleSubmit();
    });

    // ─── 起動 ─────────────────────────────────────────────────────

    startScanning();

    window.addEventListener('beforeunload', () => { reader.reset(); });
});
