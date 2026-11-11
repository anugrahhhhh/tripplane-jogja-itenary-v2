// Perilaku UX bersama untuk semua halaman (markup di partials/ux.blade.php).
//
// - data-confirm="pesan" pada <form>: dialog konfirmasi yang aksesibel
//   (pengganti window.confirm; teks dibaca sebagai data, bukan kode JS).
// - Kunci tombol submit setelah form POST dikirim, supaya klik ganda tidak
//   membuat data dobel. Pakai data-no-submit-lock untuk mengecualikan.
// - data-warn-unsaved pada <form>: peringatan bila meninggalkan halaman
//   dengan isian yang belum disimpan.
// - Fokus otomatis ke field pertama yang error + hubungkan pesan error.
// - Banner saat perangkat offline.

const UNLOCK_AFTER_MS = 10000;

function setupConfirmDialog() {
    const dialog = document.getElementById('dialog-konfirmasi');

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;

        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const submitter = event.submitter;

        // Fallback bila <dialog> tidak didukung / partial tidak ada.
        if (!dialog || typeof dialog.showModal !== 'function') {
            if (window.confirm(form.dataset.confirm)) {
                form.dataset.confirmed = '1';
                form.requestSubmit(submitter);
            }
            return;
        }

        dialog.querySelector('#dialog-konfirmasi-pesan').textContent = form.dataset.confirm;
        dialog.querySelector('#dialog-konfirmasi-judul').textContent = form.dataset.confirmTitle || 'Yakin ingin melanjutkan?';
        dialog.querySelector('[data-confirm-ok]').textContent = form.dataset.confirmButton || 'Ya, lanjutkan';
        dialog.returnValue = '';

        dialog.addEventListener('close', function onClose() {
            dialog.removeEventListener('close', onClose);

            if (dialog.returnValue === 'confirm') {
                form.dataset.confirmed = '1';
                form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
            } else if (submitter) {
                submitter.focus();
            }
        });

        dialog.showModal();
    }, true);

    // Klik di luar kotak dialog = batal.
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });
}

function setupSubmitLock() {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (event.defaultPrevented || !(form instanceof HTMLFormElement)) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'post') return;
        if ('noSubmitLock' in form.dataset || form.target === '_blank') return;

        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');

        // Ditunda supaya nilai tombol submitter tetap ikut terkirim.
        setTimeout(() => {
            const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')];
            buttons.forEach((button) => {
                if (button.disabled) return;
                button.disabled = true;
                button.dataset.lockedBySubmit = '1';
            });

            // Jaga-jaga bila respons berupa unduhan (halaman tidak berpindah).
            setTimeout(() => unlock(form), UNLOCK_AFTER_MS);
        }, 0);
    });

    // Kembali lewat tombol Back (bfcache): aktifkan lagi tombolnya.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) document.querySelectorAll('form[data-submitting]').forEach(unlock);
    });
}

function unlock(form) {
    delete form.dataset.submitting;
    form.removeAttribute('aria-busy');
    form.querySelectorAll('[data-locked-by-submit]').forEach((button) => {
        button.disabled = false;
        delete button.dataset.lockedBySubmit;
    });
}

function setupUnsavedWarning() {
    const forms = document.querySelectorAll('form[data-warn-unsaved]');

    if (!forms.length) return;

    forms.forEach((form) => {
        const markDirty = () => { form.dataset.dirty = '1'; };
        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
    });

    window.addEventListener('beforeunload', (event) => {
        const dirty = [...forms].some((form) => form.dataset.dirty === '1' && form.dataset.submitting !== '1');

        if (dirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
}

function setupFieldErrors() {
    const invalid = [...document.querySelectorAll('[aria-invalid="true"]')];

    invalid.forEach((field, index) => {
        // Cari pesan error terdekat (field dan pesannya berada di wadah yang sama).
        let container = field.parentElement;
        for (let depth = 0; container && depth < 3; depth++, container = container.parentElement) {
            const message = container.querySelector('[data-input-error]');
            if (message) {
                message.id ||= `${field.id || field.name || 'field'}-error-${index}`.replace(/[^\w-]/g, '-');
                const describedBy = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
                describedBy.add(message.id);
                field.setAttribute('aria-describedby', [...describedBy].join(' '));
                break;
            }
        }
    });

    const first = invalid.find((field) => field.offsetParent !== null);

    if (first) {
        first.focus({ preventScroll: true });
        first.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
    }
}

function setupOfflineBanner() {
    const banner = document.getElementById('banner-offline');

    if (!banner) return;

    const update = () => { banner.hidden = navigator.onLine; };
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    update();
}

export default function setupUx() {
    setupConfirmDialog();
    setupSubmitLock();

    const ready = () => {
        setupUnsavedWarning();
        setupFieldErrors();
        setupOfflineBanner();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
    } else {
        ready();
    }
}
