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

    // Dark mode toggle
    var themeBtn = document.getElementById('theme-toggle');
    var themeIcon = document.getElementById('theme-toggle-icon');

    function applyThemeIcon(theme) {
        if (!themeIcon) return;
        themeIcon.classList.remove('fa-moon', 'fa-sun');
        themeIcon.classList.add(theme === 'dark' ? 'fa-sun' : 'fa-moon');
    }

    // Sync icon on load
    var currentTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    applyThemeIcon(currentTheme);

    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            var next = isDark ? 'light' : 'dark';
            if (next === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.removeAttribute('data-theme');
            }
            try { localStorage.setItem('app-theme', next); } catch (e) {}
            applyThemeIcon(next);
        });
    }
})();
