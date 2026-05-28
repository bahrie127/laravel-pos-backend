@props([
    'action',
    'range',
    'kasirList' => null,
    'categoryList' => null,
    'paymentMethods' => null,
    'statusList' => null,
    'showKasir' => false,
    'showCategory' => false,
    'showPayment' => false,
    'showStatus' => false,
    'exportType' => null,
])

@php
    $presets = \App\Support\DateRangeResolver::PRESETS;
    $current = $range->preset;
    $payments = $paymentMethods ?? ['Tunai', 'QRIS', 'Transfer', 'Kartu'];
    $statuses = $statusList ?? [
        'paid' => 'Lunas',
        'pending' => 'Pending',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Refund',
    ];
@endphp

<div class="card-clean reports-filter-bar mb-3">
    <form method="GET" action="{{ $action }}" id="reports-filter-form">
        {{-- Preset chips --}}
        <div class="chip-row">
            <span class="chip-label">Periode:</span>
            @foreach ($presets as $key => $label)
                <button type="submit" name="preset" value="{{ $key }}"
                    class="chip {{ $current === $key ? 'active' : '' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <input type="hidden" name="preset" value="{{ $current }}">

        {{-- Controls --}}
        <div class="controls-row">
            <div class="rf-field" id="date-from-wrap" style="{{ $current === 'custom' ? '' : 'display:none;' }}">
                <label>Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from', $range->from->toDateString()) }}" class="form-control">
            </div>
            <div class="rf-field" id="date-to-wrap" style="{{ $current === 'custom' ? '' : 'display:none;' }}">
                <label>Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to', $range->to->toDateString()) }}" class="form-control">
            </div>

            @if ($showKasir && $kasirList)
                <div class="rf-field">
                    <label>Kasir</label>
                    <select name="kasir_id" class="form-control">
                        <option value="">Semua Kasir</option>
                        @foreach ($kasirList as $k)
                            <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($showCategory && $categoryList)
                <div class="rf-field">
                    <label>Kategori</label>
                    <select name="category_id" class="form-control">
                        <option value="">Semua Kategori</option>
                        @foreach ($categoryList as $c)
                            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($showPayment)
                <div class="rf-field">
                    <label>Pembayaran</label>
                    <select name="payment_method" class="form-control">
                        <option value="">Semua</option>
                        @foreach ($payments as $pm)
                            <option value="{{ $pm }}" @selected(request('payment_method') == $pm)>{{ $pm }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($showStatus)
                <div class="rf-field">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">Semua</option>
                        @foreach ($statuses as $val => $label)
                            <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="rf-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter mr-1"></i> Terapkan
                </button>

                @if ($exportType)
                    @php
                        $params = array_merge(request()->except(['_token']), [
                            'preset' => $current,
                        ]);
                    @endphp
                    <div class="dropdown">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-download mr-1"></i> Download
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge(['type' => $exportType, 'format' => 'xlsx'], $params)) }}">
                                <i class="fas fa-file-excel text-success mr-2"></i> Excel (.xlsx)
                            </a>
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge(['type' => $exportType, 'format' => 'csv'], $params)) }}">
                                <i class="fas fa-file-csv text-info mr-2"></i> CSV
                            </a>
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge(['type' => $exportType, 'format' => 'pdf'], $params)) }}" target="_blank">
                                <i class="fas fa-file-pdf text-danger mr-2"></i> PDF
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="javascript:window.print();">
                                <i class="fas fa-print text-muted mr-2"></i> Cetak
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="rf-meta">
            Menampilkan data: <strong>{{ formatDate($range->from, 'd M Y') }}</strong>
            sampai <strong>{{ formatDate($range->to, 'd M Y') }}</strong>
            <span class="mx-1">·</span>
            Bandingkan dengan periode sebelumnya:
            <strong>{{ formatDate($range->previousFrom, 'd M Y') }}</strong>
            — <strong>{{ formatDate($range->previousTo, 'd M Y') }}</strong>
        </div>
    </form>
</div>

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('reports-filter-form');
            if (!form) return;
            const dateFrom = document.getElementById('date-from-wrap');
            const dateTo = document.getElementById('date-to-wrap');
            const presetInput = form.querySelector('input[type=hidden][name=preset]');

            form.querySelectorAll('button[name=preset]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    if (btn.value === 'custom') {
                        e.preventDefault();
                        dateFrom.style.display = '';
                        dateTo.style.display = '';
                        presetInput.value = 'custom';
                        btn.removeAttribute('name');
                    }
                });
            });
        })();
    </script>
@endpush
