import { BrowserMultiFormatReader, NotFoundException } from '@zxing/library';

document.addEventListener('DOMContentLoaded', () => {
    const videoEl = document.getElementById('scanner-video');

    if (! videoEl) {
        return;
    }

    const statusEl = document.getElementById('scanner-status');
    const resultEl = document.getElementById('scanner-result');
    const resultInput = document.getElementById('scanner-result-input');
    const retryButton = document.getElementById('scanner-retry');

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
                setStatus('カメラを利用できませんでした。カメラへのアクセスを許可してください。（' + error.message + '）');
            });
    };

    retryButton?.addEventListener('click', () => {
        if (resultEl) {
            resultEl.closest('[data-scanner-result-wrapper]')?.classList.add('hidden');
        }
        startScanning();
    });

    startScanning();

    window.addEventListener('beforeunload', () => {
        reader.reset();
    });
});
