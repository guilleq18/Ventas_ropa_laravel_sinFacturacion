import QRCode from 'qrcode';

function triggerPrint(shouldPrint) {
    if (shouldPrint) {
        window.print();
    }
}

window.addEventListener('load', () => {
    const image = document.getElementById('fiscal_qr_image');

    if (!(image instanceof HTMLImageElement)) {
        return;
    }

    const qrUrl = (image.dataset.qrUrl || '').trim();
    const shouldPrint = image.dataset.autoPrint === 'true';

    if (qrUrl === '') {
        triggerPrint(shouldPrint);
        return;
    }

    QRCode.toDataURL(qrUrl, {
        width: 148,
        margin: 0,
    })
        .then((url) => {
            image.addEventListener('load', () => {
                image.hidden = false;
                image.classList.add('is-ready');
                triggerPrint(shouldPrint);
            }, { once: true });

            image.src = url;
        })
        .catch(() => {
            triggerPrint(shouldPrint);
        });
});
