(() => {
    'use strict';
    const form = document.querySelector('[data-upload-form]');
    if (!form || !window.XMLHttpRequest || !window.FormData) return;
    const status = form.querySelector('[data-upload-status]');
    const message = form.querySelector('[data-upload-message]');
    const progress = form.querySelector('[data-upload-progress]');
    const details = form.querySelector('[data-upload-details]');
    const errors = form.querySelector('[data-upload-errors]');
    let busy = false;
    const mb = (bytes) => (bytes / (1024 * 1024)).toFixed(1);
    const guard = (event) => {
        if (busy) { event.preventDefault(); event.returnValue = ''; }
    };
    window.addEventListener('beforeunload', guard);

    form.addEventListener('submit', (event) => {
        if (busy) { event.preventDefault(); return; }
        const files = [...form.querySelectorAll('input[type="file"]')].flatMap((input) => [...input.files]);
        if (!files.length) return; // Keep normal submission when no upload is selected.
        event.preventDefault();
        const data = new FormData(form);
        const controls = [...form.elements].map((control) => [control, control.disabled]);
        busy = true;
        controls.forEach(([control]) => { control.disabled = true; });
        form.setAttribute('aria-busy', 'true');
        status.hidden = false;
        errors.hidden = true;
        errors.replaceChildren();
        message.textContent = 'Mengupload… 0%';
        progress.value = 0;
        details.textContent = `File: ${files.map((file) => file.name).join(', ')} (${mb(files.reduce((sum, file) => sum + file.size, 0))} MB)`;
        const xhr = new XMLHttpRequest();
        const restore = () => {
            busy = false;
            form.removeAttribute('aria-busy');
            controls.forEach(([control, disabled]) => { control.disabled = disabled; });
        };
        const fail = (text, validation = []) => {
            restore();
            message.textContent = text;
            details.textContent = 'File yang dipilih tetap tersedia pada form.';
            validation.forEach((text) => {
                const item = document.createElement('li');
                item.textContent = text;
                errors.append(item);
            });
            errors.hidden = !validation.length;
            status.scrollIntoView({ behavior: 'smooth', block: 'center' });
        };
        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) {
                progress.removeAttribute('value');
                message.textContent = 'Mengupload…';
                return;
            }
            const percentage = Math.min(100, Math.floor(event.loaded / event.total * 100));
            progress.value = percentage;
            message.textContent = percentage === 100 ? 'Upload 100% — menunggu penyimpanan…' : `Mengupload… ${percentage}%`;
            details.textContent = `${mb(event.loaded)} MB dari ${mb(event.total)} MB terkirim. Jangan tutup halaman.`;
        });
        xhr.upload.addEventListener('load', () => {
            progress.value = 100;
            message.textContent = 'Upload 100% — menunggu penyimpanan…';
            details.textContent = 'File sudah terkirim. Server sedang memeriksa dan menyimpan data.';
        });
        xhr.addEventListener('load', () => {
            let response;
            try { response = JSON.parse(xhr.responseText); } catch (_) { response = null; }
            if (xhr.status >= 200 && xhr.status < 300 && typeof response?.redirect === 'string') {
                const target = new URL(response.redirect, window.location.href);
                if (target.origin === window.location.origin) {
                    busy = false;
                    message.textContent = 'Data berhasil disimpan.';
                    window.location.assign(target.href);
                    return;
                }
            }
            if (xhr.status === 422) {
                fail('Data belum disimpan. Periksa isian berikut.', Object.values(response?.errors || {}).flat());
            } else if (xhr.status === 413) {
                fail('Upload ditolak karena melewati batas ukuran server.');
            } else if ([401, 419].includes(xhr.status) || /\/admin\/login(?:[/?]|$)/.test(xhr.responseURL || '')) {
                fail('Sesi login berakhir. Login kembali sebelum mengupload.');
            } else {
                fail('Server belum mengonfirmasi penyimpanan. Periksa daftar data sebelum mencoba lagi.');
            }
        });
        xhr.addEventListener('error', () => fail('Koneksi terputus. Periksa daftar data sebelum mencoba lagi.'));
        xhr.addEventListener('abort', () => fail('Upload dihentikan.'));
        xhr.addEventListener('timeout', () => fail('Waktu tunggu habis. Periksa daftar data sebelum mencoba lagi.'));
        try {
            xhr.open('POST', form.action);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(data);
        } catch (_) {
            fail('Upload tidak dapat dimulai. Periksa koneksi lalu coba lagi.');
        }
    });
})();
