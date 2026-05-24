@extends('layouts.app')

@section('title', 'Ringkasan Penjualan')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Ringkasan Penjualan</h1>
                <div class="section-header-button">
                    <a href="{{ route('reports.export', ['type' => 'summary', 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'kasir_id' => request('kasir_id')]) }}"
                        class="btn btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Ringkasan</div>
                </div>
            </div>

            <div class="section-body">
                <p class="text-muted">
                    Periode: <strong>{{ formatDate($from, 'd M Y') }}</strong> &mdash;
                    <strong>{{ formatDate($to, 'd M Y') }}</strong>
                </p>

                <x-reports-filter action="{{ route('reports.summary') }}" :kasir-list="$kasirList" :show-kasir="true" :from="$from" :to="$to" />

                {{-- Stat cards --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft"><i class="fas fa-wallet"></i></div>
                            <div class="stat-label">Total Pendapatan</div>
                            <div class="stat-value">{{ rupiah($summary['total_revenue']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft"><i class="fas fa-receipt"></i></div>
                            <div class="stat-label">Total Transaksi</div>
                            <div class="stat-value">{{ number_format($summary['total_orders'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft"><i class="fas fa-cube"></i></div>
                            <div class="stat-label">Item Terjual</div>
                            <div class="stat-value">{{ number_format($summary['total_items'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft"><i class="fas fa-chart-line"></i></div>
                            <div class="stat-label">Rata-rata / Trx</div>
                            <div class="stat-value">{{ rupiah($summary['avg_per_order']) }}</div>
                        </div>
                    </div>
                </div>

                {{-- Daily revenue table --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card-clean">
                            <h4 class="mb-3">Pendapatan per Hari</h4>
                            @if ($dailyRevenue->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead><tr><th>Tanggal</th><th class="text-center">Orders</th><th class="text-right">Total</th></tr></thead>
                                        <tbody>
                                            @foreach ($dailyRevenue as $r)
                                                <tr>
                                                    <td>{{ formatDate($r->date, 'd M Y') }}</td>
                                                    <td class="text-center">{{ $r->orders }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($r->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted m-0">Tidak ada transaksi di rentang ini.</p>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card-clean">
                            <h4 class="mb-3">Per Metode Pembayaran</h4>
                            @if ($paymentBreakdown->count() > 0)
                                <table class="table table-sm">
                                    <thead><tr><th>Pembayaran</th><th class="text-center">Orders</th><th class="text-right">Total</th></tr></thead>
                                    <tbody>
                                        @foreach ($paymentBreakdown as $p)
                                            <tr>
                                                <td><span class="badge badge-soft-secondary">{{ strtoupper($p->payment_method ?? '—') }}</span></td>
                                                <td class="text-center">{{ $p->orders }}</td>
                                                <td class="text-right font-weight-bold">{{ rupiah($p->total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-muted m-0">Belum ada data.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Kasir performance --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <h4 class="mb-3">Performa Kasir</h4>
                            @if ($kasirPerformance->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr><th>Kasir</th><th class="text-center">Transaksi</th><th class="text-right">Total Pendapatan</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($kasirPerformance as $r)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $r->kasir_name }}</td>
                                                    <td class="text-center">{{ $r->order_count }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($r->total_revenue) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted m-0">Belum ada data.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
