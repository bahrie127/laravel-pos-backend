@extends('layouts.app')

@section('title', 'Dashboard')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Dashboard</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active">{{ now()->translatedFormat('l, d F Y') }}</div>
                </div>
            </div>

            <div class="section-body">
                <h2 class="section-title">Halo, {{ auth()->user()->name }} 👋</h2>
                <p class="section-lead">Berikut ringkasan aktivitas POS hari ini.</p>

                {{-- 4 Stat Cards --}}
                <div class="row">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-label">Pendapatan Hari Ini</div>
                            <div class="stat-value">{{ rupiah($stats['revenue_today']) }}</div>
                            @if (! is_null($stats['revenue_delta']))
                                <div class="stat-delta {{ $stats['revenue_delta'] >= 0 ? 'up' : 'down' }}">
                                    <i class="fas fa-arrow-{{ $stats['revenue_delta'] >= 0 ? 'up' : 'down' }}"></i>
                                    {{ abs($stats['revenue_delta']) }}% vs kemarin
                                </div>
                            @else
                                <div class="stat-delta text-muted">Belum ada data kemarin</div>
                            @endif
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-label">Transaksi Hari Ini</div>
                            <div class="stat-value">{{ number_format($stats['orders_today'], 0, ',', '.') }}</div>
                            <div class="stat-delta text-muted">Total order tercatat</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft">
                                <i class="fas fa-box-open"></i>
                            </div>
                            <div class="stat-label">Total Produk</div>
                            <div class="stat-value">{{ number_format($stats['total_products'], 0, ',', '.') }}</div>
                            <div class="stat-delta text-muted">Produk terdaftar</div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-label">Pengguna Aktif</div>
                            <div class="stat-value">{{ number_format($stats['active_users'], 0, ',', '.') }}</div>
                            <div class="stat-delta text-muted">Total akun</div>
                        </div>
                    </div>
                </div>

                {{-- Sales chart + Top products --}}
                <div class="row">
                    <div class="col-lg-8 col-md-12 col-12 col-sm-12">
                        <div class="card-clean">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0">Tren Penjualan 7 Hari Terakhir</h4>
                            </div>
                            <canvas id="salesChart" height="100"></canvas>
                        </div>
                    </div>

                    <div class="col-lg-4 col-md-12 col-12 col-sm-12">
                        <div class="card-clean">
                            <h4 class="mb-3">Top 5 Produk</h4>
                            @forelse ($topProducts as $i => $item)
                                <div class="d-flex align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <span class="badge badge-soft-primary mr-2" style="min-width:24px;">{{ $i + 1 }}</span>
                                    <div class="flex-grow-1" style="min-width:0;">
                                        <div class="font-weight-bold text-truncate">{{ $item->product->name ?? '—' }}</div>
                                        <div class="text-muted" style="font-size:12px;">
                                            {{ number_format($item->total_sold, 0, ',', '.') }} terjual · {{ rupiah($item->total_revenue) }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-box-open mb-2" style="font-size:32px;opacity:.4;"></i>
                                    <div>Belum ada penjualan.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Recent orders --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0">Pesanan Terbaru</h4>
                                <a href="{{ route('order.index') }}" class="text-primary" style="font-size:13px;">
                                    Lihat semua <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Order</th>
                                            <th>Kasir</th>
                                            <th>Item</th>
                                            <th class="text-right">Total</th>
                                            <th class="text-center">Pembayaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($recentOrders as $order)
                                            <tr>
                                                <td>{{ $order->transaction_time?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                                                <td>
                                                    <a href="{{ route('order.show', $order) }}" class="font-weight-bold text-primary">
                                                        #{{ $order->id }}
                                                    </a>
                                                </td>
                                                <td>{{ $order->kasir->name ?? '—' }}</td>
                                                <td>{{ $order->total_item }}</td>
                                                <td class="text-right font-weight-bold">{{ rupiah($order->total_price) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-soft-secondary">{{ strtoupper($order->payment_method ?? '—') }}</span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">Belum ada pesanan.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Low stock + Payment breakdown --}}
                <div class="row">
                    <div class="col-lg-6 col-md-12 col-12">
                        <div class="card-clean">
                            <h4 class="mb-3">
                                <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                                Stok Menipis
                            </h4>
                            @forelse ($lowStock as $product)
                                <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                                    <span>{{ $product->name }}</span>
                                    <span class="badge {{ $product->stock == 0 ? 'badge-soft-danger' : 'badge-soft-warning' }}">
                                        {{ $product->stock == 0 ? 'Habis' : $product->stock . ' tersisa' }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle text-success mb-2" style="font-size:32px;"></i>
                                    <div>Semua stok aman.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12 col-12">
                        <div class="card-clean">
                            <h4 class="mb-3">Pembayaran Hari Ini</h4>
                            @if ($paymentBreakdown->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-credit-card mb-2" style="font-size:32px;opacity:.4;"></i>
                                    <div>Belum ada transaksi hari ini.</div>
                                </div>
                            @else
                                <canvas id="paymentChart" height="160"></canvas>
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
        // Sales trend chart
        (function () {
            const ctx = document.getElementById('salesChart');
            if (!ctx) return;
            const trend = @json($salesTrend);
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [{
                        label: 'Pendapatan',
                        data: trend.map(t => t.total),
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#3B82F6',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { display: false },
                    tooltips: {
                        callbacks: {
                            label: function (item) {
                                return 'Rp ' + Number(item.yLabel).toLocaleString('id-ID');
                            }
                        }
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function (v) {
                                    return 'Rp ' + Number(v).toLocaleString('id-ID');
                                }
                            }
                        }]
                    }
                }
            });
        })();

        // Payment method donut chart
        (function () {
            const ctx = document.getElementById('paymentChart');
            if (!ctx) return;
            const payments = @json($paymentBreakdown);
            const palette = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'];
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: payments.map(p => (p.payment_method || 'Lainnya').toUpperCase()),
                    datasets: [{
                        data: payments.map(p => Number(p.total)),
                        backgroundColor: palette.slice(0, payments.length),
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' },
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                const label = data.labels[item.index];
                                const value = data.datasets[0].data[item.index];
                                return label + ': Rp ' + Number(value).toLocaleString('id-ID');
                            }
                        }
                    }
                }
            });
        })();
    </script>
@endpush
