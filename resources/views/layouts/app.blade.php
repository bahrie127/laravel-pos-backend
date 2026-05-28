<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') &mdash; {{ config('app.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('library/bootstrap/dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
        integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @stack('style')

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    {{-- Anti-flash dark mode init: apply theme sebelum body render --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('app-theme');
                if (t === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
</head>

<body>
    <div id="app">
        <div class="main-wrapper">
            @include('components.header')

            @include('components.sidebar')

            @yield('main')

            @include('components.footer')
        </div>
    </div>

    <script src="{{ asset('library/jquery/dist/jquery.min.js') }}"></script>
    <script src="{{ asset('library/popper.js/dist/umd/popper.js') }}"></script>
    <script src="{{ asset('library/tooltip.js/dist/umd/tooltip.js') }}"></script>
    <script src="{{ asset('library/bootstrap/dist/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('library/jquery.nicescroll/dist/jquery.nicescroll.min.js') }}"></script>
    <script src="{{ asset('library/moment/min/moment.min.js') }}"></script>
    <script src="{{ asset('js/stisla.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Global toast helper — pakai di mana saja: AppToast('success', 'pesan')
        window.AppToast = function (type, message, options) {
            if (typeof Swal === 'undefined') return;
            type = ['success', 'error', 'warning', 'info', 'question'].indexOf(type) >= 0 ? type : 'info';
            Swal.fire(Object.assign({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: { popup: 'app-toast-popup' },
            }, options || {}));
        };
        // Legacy alias dari Phase 1
        window.toast = function (message, type) { return window.AppToast(type || 'success', message); };

        // Global confirm helper — return Promise<boolean>
        window.AppConfirm = function (opts) {
            opts = opts || {};
            return Swal.fire({
                title: opts.title || 'Apakah Anda yakin?',
                text: opts.text || '',
                icon: opts.icon || 'warning',
                showCancelButton: true,
                confirmButtonColor: opts.confirmColor || '#EF4444',
                cancelButtonColor: '#6B7280',
                confirmButtonText: opts.confirmText || 'Ya, lanjutkan',
                cancelButtonText: opts.cancelText || 'Batal',
                reverseButtons: true,
            }).then(function (r) { return r.isConfirmed; });
        };
    </script>

    @stack('scripts')

    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
