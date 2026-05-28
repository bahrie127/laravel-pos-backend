@extends('layouts.app')

@section('title', 'Laporan')

@php
    $groups = [
        'Sales' => [
            ['route' => 'reports.summary', 'icon' => 'chart-bar', 'bg' => '#DBEAFE', 'color' => '#2563EB',
             'title' => 'Ringkasan Penjualan', 'desc' => 'Total revenue, transaksi, rata-rata, breakdown payment & performa kasir.'],
            ['route' => 'reports.sales-analytics', 'icon' => 'chart-line', 'bg' => '#E0E7FF', 'color' => '#4338CA',
             'title' => 'Sales Analytics', 'desc' => 'Tren overlay periode, peak hours, day-of-week, top kategori, dengan delta vs periode sebelumnya.'],
            ['route' => 'reports.product-sales', 'icon' => 'cube', 'bg' => '#D1FAE5', 'color' => '#047857',
             'title' => 'Penjualan per Produk', 'desc' => 'Ranking produk terlaris, qty terjual, kontribusi % omset.'],
        ],
        'Operations' => [
            ['route' => 'reports.close-cashier', 'icon' => 'cash-register', 'bg' => '#FEF3C7', 'color' => '#B45309',
             'title' => 'Tutup Kasir', 'desc' => 'Setoran per kasir per periode, breakdown per metode pembayaran.'],
            ['route' => 'cash-session.index', 'icon' => 'cash-register', 'bg' => '#FEE2E2', 'color' => '#B91C1C',
             'title' => 'Cash Sessions (Shift)', 'desc' => 'Daftar semua shift, variance, force-close untuk shift yang lupa ditutup.'],
        ],
        'Marketing' => [
            ['route' => 'reports.promo-usage', 'icon' => 'tags', 'bg' => '#FCE7F3', 'color' => '#BE185D',
             'title' => 'Pemakaian Promo', 'desc' => 'Berapa kali promo dipakai, total diskon, & breakdown per voucher.'],
        ],
        'Inventory' => [
            ['route' => 'reports.inventory', 'icon' => 'warehouse', 'bg' => '#FFEDD5', 'color' => '#C2410C',
             'title' => 'Laporan Stok', 'desc' => 'Nilai stok per produk, low-stock alert, total terjual & terakhir terjual.'],
        ],
    ];
@endphp

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
                <p class="section-lead">Klik kartu untuk lihat detail, filter, dan download (Excel / CSV / PDF).</p>

                @foreach ($groups as $groupName => $items)
                    <div class="mb-2 mt-4 d-flex align-items-center" style="gap:10px;">
                        <h5 class="m-0" style="font-weight:600;color:#374151;">{{ $groupName }}</h5>
                        <div style="flex:1;height:1px;background:#E5E7EB;"></div>
                    </div>
                    <div class="row">
                        @foreach ($items as $r)
                            <div class="col-md-4 col-lg-3 mb-3">
                                @if (\Illuminate\Support\Facades\Route::has($r['route']))
                                    <a href="{{ route($r['route']) }}" class="card-clean d-block text-reset text-decoration-none h-100">
                                        <div style="width:48px;height:48px;border-radius:10px;background:{{ $r['bg'] }};color:{{ $r['color'] }};display:inline-flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:10px;">
                                            <i class="fas fa-{{ $r['icon'] }}"></i>
                                        </div>
                                        <h5 class="mb-1" style="font-size:15px;">{{ $r['title'] }}</h5>
                                        <p class="text-muted m-0" style="font-size:12px;">{{ $r['desc'] }}</p>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
