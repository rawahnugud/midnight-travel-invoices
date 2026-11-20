// Size the printed mark by its ink. Keep the original image for PDF rendering.
(() => {
    const image = document.querySelector('.invoice-print-stamp-img');
    if (!image) return;

    function fitStamp() {
        if (!image.naturalWidth || !image.naturalHeight) return;
        const scale = Math.min(1, 768 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext('2d');
        if (!context) return;
        context.fillStyle = 'white';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        let pixels;
        try {
            pixels = context.getImageData(0, 0, canvas.width, canvas.height).data;
        } catch {
            return; // A cross-origin image can still print at its original fit.
        }

        let left = canvas.width, right = -1, top = canvas.height, bottom = -1;
        for (let y = 0; y < canvas.height; y++) {
            for (let x = 0; x < canvas.width; x++) {
                const i = (y * canvas.width + x) * 4;
                if (Math.min(pixels[i], pixels[i + 1], pixels[i + 2]) >= 243) continue;
                left = Math.min(left, x);
                right = Math.max(right, x);
                top = Math.min(top, y);
                bottom = Math.max(bottom, y);
            }
        }
        if (right < left) return;

        // Include a small border around the ink and center rectangular seals too.
        const side = Math.max(right - left + 1, bottom - top + 1) * 1.06;
        image.style.width = (100 * canvas.width / side) + '%';
        image.style.left = (50 - 50 * (left + right + 1) / side) + '%';
        image.style.top = (50 - 50 * (top + bottom + 1) / side) + '%';
        image.parentElement.classList.add('stamp-fitted');
    }

    if (image.complete) fitStamp();
    else image.addEventListener('load', fitStamp, { once: true });
})();
