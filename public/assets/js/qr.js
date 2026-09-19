(function () {
    'use strict';

    function renderQr(element, data, options = {}) {
        if (!element) return null;
        element.innerHTML = '';
        return new QRCode(element, Object.assign({ text: JSON.stringify(data), width: 256, height: 256, colorDark: '#000000', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.L }, options));
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

            // wait for rendering
            requestAnimationFrame(() => {
                try {
                    let dataUrl = null;
                    const img = tmp.querySelector('img');
                    const canvas = tmp.querySelector('canvas');
                    if (img && img.src) {
                        dataUrl = img.src;
                    } else if (canvas && canvas.toDataURL) {
                        dataUrl = canvas.toDataURL('image/png');
                    }

                    if (!dataUrl) {
                        // cleanup and reject
                        tmp.remove();
                        if (qr && typeof qr.clear === 'function') try { qr.clear(); } catch (e) {}
                        return reject(new Error('Não foi possível gerar imagem do QR.'));
                    }

                    const a = document.createElement('a');
                    a.href = dataUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();

                    // cleanup
                    tmp.remove();
                    if (qr && typeof qr.clear === 'function') try { qr.clear(); } catch (e) {}
                    resolve(true);
                } catch (err) {
                    try { tmp.remove(); } catch (e) {}
                    if (qr && typeof qr.clear === 'function') try { qr.clear(); } catch (e) {}
                    reject(err);
                }
            });
        });
    }

    window.tccQr = { renderQr, downloadQr };
})();
