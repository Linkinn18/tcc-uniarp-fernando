(function () {
    'use strict';

    function drawMatrix(element, matrix, size, colorDark = '#000000', colorLight = '#ffffff') {
        if (!element || !matrix || typeof matrix.getModuleCount !== 'function' || typeof matrix.isDark !== 'function') {
            throw new Error('Não foi possível desenhar o QR Code.');
        }

        const moduleCount = matrix.getModuleCount();
        const quietZoneModules = 4;
        const moduleSize = Math.max(1, Math.ceil(size / (moduleCount + quietZoneModules * 2)));
        const outputSize = (moduleCount + quietZoneModules * 2) * moduleSize;
        const canvas = document.createElement('canvas');
        canvas.width = outputSize;
        canvas.height = outputSize;

        const context = canvas.getContext('2d');
        if (!context) {
            throw new Error('Não foi possível preparar a imagem do QR.');
        }

        context.fillStyle = colorLight;
        context.fillRect(0, 0, outputSize, outputSize);
        context.fillStyle = colorDark;

        for (let row = 0; row < moduleCount; row += 1) {
            for (let column = 0; column < moduleCount; column += 1) {
                if (matrix.isDark(row, column)) {
                    context.fillRect(
                        (column + quietZoneModules) * moduleSize,
                        (row + quietZoneModules) * moduleSize,
                        moduleSize,
                        moduleSize
                    );
                }
            }
        }

        element.innerHTML = '';
        element.appendChild(canvas);

        return canvas;
    }

    function signatureToQrValue(signature) {
        return String(signature || '').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
    }

    function buildPayload(data) {
        if (!data || !data.id || !data.sig) {
            throw new Error('Dados insuficientes para gerar o QR Code.');
        }

        const params = new URLSearchParams();
        params.set('i', data.id);
        params.set('s', signatureToQrValue(data.sig));
        return params.toString();
    }

    function renderQr(element, data, options = {}) {
        if (!element) return null;
        element.innerHTML = '';
        const payloadText = options.text || buildPayload(data);
        const qrElement = document.createElement('div');
        const qr = new QRCode(qrElement, Object.assign({ text: payloadText, width: 256, height: 256, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M }, options));
        const matrix = qr && qr._oQRCode;
        const size = Number(options.width) || Number(options.height) || 256;
        drawMatrix(element, matrix, size, options.colorDark || '#000000', options.colorLight || '#ffffff');
        return qr;
    }

    function downloadQr(element, data, filename = 'tcc-qr.png', size = 1024) {
        if (!data) return Promise.reject(new Error('No QR data'));

        return new Promise((resolve, reject) => {
            const qrElement = document.createElement('div');
            const qr = renderQr(qrElement, data, { width: size, height: size });

            const cleanup = () => {
                if (qr && typeof qr.clear === 'function') {
                    try { qr.clear(); } catch (error) {}
                }
            };

            try {
                const matrix = qr && qr._oQRCode;
                const output = drawMatrix(qrElement, matrix, size);

                const a = document.createElement('a');
                a.href = output.toDataURL('image/png');
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                a.remove();

                cleanup();
                resolve(true);
            } catch (error) {
                cleanup();
                reject(error);
            }
        });
    }

    window.tccQr = { buildPayload, renderQr, downloadQr, signatureToQrValue };
})();
