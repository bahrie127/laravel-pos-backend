// POS FIC11 — custom JS

(function () {
    'use strict';

    // SweetAlert2 confirm-delete handler
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.confirm-delete');
        if (!btn) return;

        e.preventDefault();

        const form = btn.closest('form');
        const action = btn.dataset.action || (form ? form.action : null);
        if (!action && !form) return;

        const title = btn.dataset.title || 'Yakin ingin menghapus?';
        const text = btn.dataset.text || 'Aksi ini tidak dapat dibatalkan.';

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true,
        }).then(function (result) {
            if (result.isConfirmed) {
                if (form) {
                    form.submit();
                } else {
                    // Build form on the fly for data-action style
                    const f = document.createElement('form');
                    f.method = 'POST';
                    f.action = action;
                    const csrf = document.querySelector('meta[name="csrf-token"]');
                    if (csrf) {
                        const t = document.createElement('input');
                        t.type = 'hidden'; t.name = '_token'; t.value = csrf.content;
                        f.appendChild(t);
                    }
                    const m = document.createElement('input');
                    m.type = 'hidden'; m.name = '_method'; m.value = 'DELETE';
                    f.appendChild(m);
                    document.body.appendChild(f);
                    f.submit();
                }
            }
        });
    });

    // Toast helper exposed globally
    window.toast = function (message, type) {
        type = type || 'success';
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    };

    // Password toggle (data-password-toggle on a button pointing to data-target input id)
    document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = document.getElementById(btn.dataset.target);
            if (!target) return;
            target.type = target.type === 'password' ? 'text' : 'password';
            const icon = btn.querySelector('i');
            if (icon) icon.classList.toggle('fa-eye-slash');
        });
    });
})();
