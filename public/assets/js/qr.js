(function () {
    'use strict';

    function buildPayload(data) {
        if (!data || !data.id || !data.sig) {
            throw new Error('Dados insuficientes para gerar o QR Code.');
        }

        const params = new URLSearchParams();
        params.set('i', data.id);
        params.set('s', data.sig);
        return params.toString();
    }

    function renderQr(element, data, options = {}) {
        if (!element) return null;
        element.innerHTML = '';
        const payloadText = options.text || buildPayload(data);
        return new QRCode(element, Object.assign({ text: payloadText, width: 256, height: 256, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M }, options));
    }

    function downloadQr(element, data, filename = 'tcc-qr.png', size = 512) {
        if (!data) return Promise.reject(new Error('No QR data'));

        return new Promise((resolve, reject) => {
            const tmp = document.createElement('div');
            tmp.style.position = 'fixed';
            tmp.style.left = '-9999px';
            tmp.style.width = size + 'px';
            tmp.style.height = size + 'px';
            document.body.appendChild(tmp);

            const qr = renderQr(tmp, data, { width: size, height: size });

            const cleanup = () => {
                tmp.remove();
                if (qr && typeof qr.clear === 'function') {
                    try { qr.clear(); } catch (error) {}
                }
            };

            const rejectExport = (error) => {
                cleanup();
                reject(error);
            };

            const exportImage = (source) => {
                try {
                    const quietZone = Math.ceil(size / 16);
                    const output = document.createElement('canvas');
                    output.width = size + quietZone * 2;
                    output.height = size + quietZone * 2;

                    const context = output.getContext('2d');
                    if (!context) {
                        throw new Error('Não foi possível preparar a imagem do QR.');
                    }

                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, output.width, output.height);
                    context.drawImage(source, quietZone, quietZone, size, size);

                    const a = document.createElement('a');
                    a.href = output.toDataURL('image/png');
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    cleanup();
                    resolve(true);
                } catch (error) {
                    rejectExport(error);
                }
            };

            requestAnimationFrame(() => {
                const source = tmp.querySelector('canvas') || tmp.querySelector('img');
                if (!source) {
                    rejectExport(new Error('Não foi possível gerar imagem do QR.'));
                    return;
                }

                if (source.tagName === 'IMG' && !source.complete) {
                    source.onload = () => exportImage(source);
                    source.onerror = () => rejectExport(new Error('Não foi possível carregar a imagem do QR.'));
                    return;
                }

                exportImage(source);
            });
        });
    }

    window.tccQr = { buildPayload, renderQr, downloadQr };
})();
