import { BrowserMultiFormatReader, NotFoundException } from '@zxing/library';

const JAN_CODE_PATTERN = /^\d{8}$|^\d{13}$/;

document.addEventListener('DOMContentLoaded', () => {
    const videoEl = document.getElementById('scanner-video');

    if (! videoEl) {
        return;
    }

    const statusEl = document.getElementById('scanner-status');
    const resultEl = document.getElementById('scanner-result');
    const resultInput = document.getElementById('scanner-result-input');
    const retryButton = document.getElementById('scanner-retry');
    const manualToggle = document.getElementById('manual-input-toggle');
    const manualForm = document.getElementById('manual-input-form');
    const manualInput = document.getElementById('manual-jan-code');
    const manualSubmit = document.getElementById('manual-jan-submit');
    const manualError = document.getElementById('manual-jan-error');

    const reader = new BrowserMultiFormatReader();
    let stopped = false;

    const setStatus = (message) => {
        if (statusEl) {
            statusEl.textContent = message;
        }
    };

    const handleResult = (janCode) => {
        stopped = true;
        reader.reset();
        setStatus('読み取りに成功しました。');

        if (resultInput) {
            resultInput.value = janCode;
        }

        if (resultEl) {
            resultEl.textContent = janCode;
            resultEl.closest('[data-scanner-result-wrapper]')?.classList.remove('hidden');
        }
    };

    const startScanning = () => {
        stopped = false;
        setStatus('カメラを起動しています…');

        reader
            .decodeFromConstraints(
                { video: { facingMode: 'environment' } },
                videoEl,
                (result, error) => {
                    if (stopped) {
                        return;
                    }

                    if (result) {
                        handleResult(result.getText());
                        return;
                    }

                    if (error && ! (error instanceof NotFoundException)) {
                        setStatus('読み取り中にエラーが発生しました: ' + error.message);
                    } else {
                        setStatus('バーコードをカメラに近づけてください。');
                    }
                }
            )
            .catch((error) => {
                setStatus('カメラを利用できませんでした。カメラへのアクセスを許可するか、JANコードを手入力してください。（' + error.message + '）');
                showManualForm();
            });
    };

    const showManualForm = () => {
        manualForm?.classList.remove('hidden');
    };

    retryButton?.addEventListener('click', () => {
        if (resultEl) {
            resultEl.closest('[data-scanner-result-wrapper]')?.classList.add('hidden');
        }
        startScanning();
    });

    manualToggle?.addEventListener('click', () => {
        manualForm?.classList.toggle('hidden');
    });

    manualSubmit?.addEventListener('click', () => {
        const janCode = (manualInput?.value ?? '').trim();

        if (! JAN_CODE_PATTERN.test(janCode)) {
            manualError?.classList.remove('hidden');
            return;
        }

        manualError?.classList.add('hidden');
        handleResult(janCode);
    });

    startScanning();

    window.addEventListener('beforeunload', () => {
        reader.reset();
    });
});
