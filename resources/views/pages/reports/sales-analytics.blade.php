@extends('layouts.app')

@section('title', 'Sales Analytics')

@php
    $deltaBadge = function ($value) {
        if ($value === null) return '<span class="text-muted" style="font-size:11px;">— vs prev</span>';
        $cls = $value >= 0 ? 'text-success' : 'text-danger';
        $arrow = $value >= 0 ? '▲' : '▼';
        return '<span class="' . $cls . '" style="font-size:11px;font-weight:600;">' . $arrow . ' ' . abs($value) . '% vs prev</span>';
    };
@endphp

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Sales Analytics</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Sales Analytics</div>
                </div>
            </div>

            <div class="section-body">
                <x-reports-filter-bar
                    :action="route('reports.sales-analytics')"
                    :range="$range"
                    :kasir-list="$kasirList"
                    :show-kasir="true"
                    :show-payment="true"
                    export-type="sales-analytics"
                />

                {{-- Stat cards --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft"><i class="fas fa-wallet"></i></div>
                            <div class="stat-label">Total Pendapatan</div>
                            <div class="stat-value">{{ rupiah($stats['revenue']) }}</div>
                            <div>{!! $deltaBadge($delta['revenue']) !!}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft"><i class="fas fa-receipt"></i></div>
                            <div class="stat-label">Jumlah Order</div>
                            <div class="stat-value">{{ number_format($stats['orders'], 0, ',', '.') }}</div>
                            <div>{!! $deltaBadge($delta['orders']) !!}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-label">Rata-rata / Order</div>
                            <div class="stat-value">{{ rupiah($stats['avg_ticket']) }}</div>
                            <div>{!! $deltaBadge($delta['avg_ticket']) !!}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft"><i class="fas fa-cube"></i></div>
                            <div class="stat-label">Item Terjual</div>
                            <div class="stat-value">{{ number_format($stats['items'], 0, ',', '.') }}</div>
                            <div>{!! $deltaBadge($delta['items']) !!}</div>
                        </div>
                    </div>
                </div>

                {{-- Peak insight --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-clean d-flex align-items-center" style="gap:14px;">
                            <div style="width:48px;height:48px;border-radius:10px;background:#FEF3C7;color:#B45309;display:inline-flex;align-items:center;justify-content:center;font-size:22px;">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:12px;">Jam Tersibuk</div>
                                <div style="font-size:18px;font-weight:bold;">{{ $peakHour['hour'] ?? '—' }}</div>
                                <div class="text-muted" style="font-size:12px;">
                                    {{ $peakHour['orders'] ?? 0 }} order &middot; {{ rupiah($peakHour['total'] ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card-clean d-flex align-items-center" style="gap:14px;">
                            <div style="width:48px;height:48px;border-radius:10px;background:#DBEAFE;color:#2563EB;display:inline-flex;align-items:center;justify-content:center;font-size:22px;">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:12px;">Hari Tertinggi</div>
                                <div style="font-size:18px;font-weight:bold;">{{ $peakDay['day'] ?? '—' }}</div>
                                <div class="text-muted" style="font-size:12px;">
                                    {{ $peakDay['orders'] ?? 0 }} order &middot; {{ rupiah($peakDay['total'] ?? 0) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Trend chart --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0">Tren Pendapatan</h4>
                                <small class="text-muted">Garis biru = periode ini, abu-abu putus = periode sebelumnya</small>
                            </div>
                            <canvas id="trendChart" height="80"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Hourly + DoW --}}
                <div class="row">
                    <div class="col-md-7">
                        <div class="card-clean">
                            <h4 class="mb-3">Distribusi per Jam</h4>
                            <canvas id="hourlyChart" height="160"></canvas>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card-clean">
                            <h4 class="mb-3">Distribusi per Hari (Mingguan)</h4>
                            <canvas id="dowChart" height="160"></canvas>
                        </div>
                    </div>
                </div>

                {{-- Top categories + Payment method --}}
                <div class="row">
                    <div class="col-md-7">
                        <div class="card-clean">
                            <h4 class="mb-3">Top Kategori</h4>
                            @if ($topCategories->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Kategori</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-right">Pendapatan</th>
                                                <th>%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $maxRev = $topCategories->max('revenue') ?: 1; @endphp
                                            @foreach ($topCategories as $c)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $c->cat }}</td>
                                                    <td class="text-center">{{ $c->qty }}</td>
                                                    <td class="text-right">{{ rupiah($c->revenue) }}</td>
                                                    <td style="min-width:140px;">
                                                        <div style="background:#E5E7EB;height:8px;border-radius:4px;overflow:hidden;">
                                                            <div style="background:#3B82F6;height:100%;width:{{ round(($c->revenue/$maxRev)*100) }}%;"></div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted text-center py-4">Belum ada data.</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card-clean">
                            <h4 class="mb-3">Metode Pembayaran</h4>
                            @if ($paymentBreakdown->count() > 0)
                                <canvas id="paymentChart" height="160"></canvas>
                            @else
                                <p class="text-muted text-center py-4">Belum ada data.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script src="{{ asset('library/chart.js/dist/Chart.min.js') }}"></script>
<script>
    (function () {
        const fmt = v => 'Rp ' + new Intl.NumberFormat('id-ID').format(v);

        // === Trend chart (overlay) ===
        const currentLabels = @json($dailyCurrent->pluck('date'));
        const currentData = @json($dailyCurrent->pluck('total'));
        const previousData = @json($dailyPrevious->pluck('total'));
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: currentLabels,
                    datasets: [
                        {
                            label: 'Periode ini',
                            data: currentData,
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59,130,246,0.1)',
                            fill: true,
                            tension: 0.3,
                            borderWidth: 2,
                        },
                        {
                            label: 'Periode sebelumnya',
                            data: previousData,
                            borderColor: '#9CA3AF',
                            borderDash: [4, 4],
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: true, position: 'bottom' } },
                    scales: { y: { ticks: { callback: fmt } } },
                }
            });
        }

        // === Hourly bar ===
        const hourly = @json($hourlyFull);
        const hCtx = document.getElementById('hourlyChart');
        if (hCtx) {
            new Chart(hCtx, {
                type: 'bar',
                data: {
                    labels: hourly.map(h => h.hour),
                    datasets: [{
                        label: 'Pendapatan',
                        data: hourly.map(h => h.total),
                        backgroundColor: '#3B82F6',
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { ticks: { callback: fmt } } },
                }
            });
        }

        // === Day of week bar ===
        const dow = @json($dow);
        const dCtx = document.getElementById('dowChart');
        if (dCtx) {
            new Chart(dCtx, {
                type: 'bar',
                data: {
                    labels: dow.map(d => d.day),
                    datasets: [{
                        label: 'Pendapatan',
                        data: dow.map(d => d.total),
                        backgroundColor: '#10B981',
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { ticks: { callback: fmt } } },
                }
            });
        }

        // === Payment method pie ===
        const pay = @json($paymentBreakdown);
        const pCtx = document.getElementById('paymentChart');
        if (pCtx && pay.length > 0) {
            new Chart(pCtx, {
                type: 'doughnut',
                data: {
                    labels: pay.map(p => p.payment_method),
                    datasets: [{
                        data: pay.map(p => p.total),
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                    }],
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom' } },
                }
            });
        }
    })();
</script>
@endpush
