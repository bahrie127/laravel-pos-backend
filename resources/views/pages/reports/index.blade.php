@extends('layouts.app')

@section('title', 'Laporan')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Laporan</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Laporan</div>
                </div>
            </div>

            <div class="section-body">
                <h2 class="section-title">Pilih Laporan</h2>
                <p class="section-lead">Klik kartu untuk lihat detail atau langsung ekspor.</p>

                <div class="row">
                    <div class="col-md-4">
                        <a href="{{ route('reports.summary') }}" class="card-clean d-block text-reset text-decoration-none">
                            <div style="width:56px;height:56px;border-radius:12px;background:#DBEAFE;color:#2563EB;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px;">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                            <h4 class="mb-1">Ringkasan Penjualan</h4>
                            <p class="text-muted m-0">Total revenue, transaksi, rata-rata. Breakdown payment & performa kasir.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('reports.product-sales') }}" class="card-clean d-block text-reset text-decoration-none">
                            <div style="width:56px;height:56px;border-radius:12px;background:#D1FAE5;color:#047857;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px;">
                                <i class="fas fa-cube"></i>
                            </div>
                            <h4 class="mb-1">Penjualan per Produk</h4>
                            <p class="text-muted m-0">Ranking produk terlaris, qty terjual, kontribusi % omset.</p>
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="{{ route('reports.close-cashier') }}" class="card-clean d-block text-reset text-decoration-none">
                            <div style="width:56px;height:56px;border-radius:12px;background:#FEF3C7;color:#B45309;display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:12px;">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <h4 class="mb-1">Tutup Kasir</h4>
                            <p class="text-muted m-0">Setoran per kasir per shift, breakdown per metode pembayaran.</p>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
