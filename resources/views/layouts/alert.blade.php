@php
    $toasts = [];

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        if ($msg = session($type)) {
            $toasts[] = ['type' => $type, 'message' => $msg];
        }
    }

    if (session('status')) {
        $toasts[] = ['type' => 'success', 'message' => session('status')];
    }

    if ($errors->any() && !session('error')) {
        $toasts[] = ['type' => 'error', 'message' => $errors->first()];
    }
@endphp

@if (!empty($toasts))
    @push('scripts')
        <script>
            (function () {
                var fire = function () {
                    if (typeof window.AppToast !== 'function') {
                        return setTimeout(fire, 50);
                    }
                    var queue = @json($toasts);
                    queue.forEach(function (t, i) {
                        setTimeout(function () { window.AppToast(t.type, t.message); }, i * 250);
                    });
                };
                fire();
            })();
        </script>
    @endpush
@endif
