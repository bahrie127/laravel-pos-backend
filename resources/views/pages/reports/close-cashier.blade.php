@extends('layouts.app')

@section('title', 'Tutup Kasir')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Tutup Kasir</h1>
                @if ($selectedKasir)
                    <div class="section-header-button">
                        <a href="{{ route('reports.export', ['type' => 'close-cashier', 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString(), 'kasir_id' => $selectedKasir->id]) }}"
                            class="btn btn-outline-success">
                            <i class="fas fa-file-excel mr-1"></i> Export
                        </a>
                    </div>
                @endif
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Tutup Kasir</div>
                </div>
            </div>

            <div class="section-body">
                <x-reports-filter action="{{ route('reports.close-cashier') }}" :kasir-list="$kasirList" :show-kasir="true" :from="$from" :to="$to" />

                @if ($selectedKasir)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon bg-primary-soft"><i class="fas fa-receipt"></i></div>
                                <div class="stat-label">Total Transaksi</div>
                                <div class="stat-value">{{ number_format($totals['count'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-card">
                                <div class="stat-icon bg-success-soft"><i class="fas fa-wallet"></i></div>
                                <div class="stat-label">Total Setoran</div>
                                <div class="stat-value">{{ rupiah($totals['revenue']) }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <h4 class="mb-3">
                                    Kasir: {{ $selectedKasir->name }}
                                    <span class="badge badge-soft-secondary ml-2">
                                        {{ formatDate($from, 'd M Y') }} &mdash; {{ formatDate($to, 'd M Y') }}
                                    </span>
                                </h4>

                                @if ($rows->count() > 0)
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Metode Pembayaran</th>
                                                <th class="text-center">Jumlah Order</th>
                                                <th class="text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($rows as $r)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-soft-secondary">{{ strtoupper($r->payment_method ?? '—') }}</span>
                                                    </td>
                                                    <td class="text-center">{{ $r->orders }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($r->total) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr style="border-top:2px solid #111;">
                                                <th>TOTAL</th>
                                                <th class="text-center">{{ $totals['count'] }}</th>
                                                <th class="text-right">{{ rupiah($totals['revenue']) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                @else
                                    <p class="text-muted m-0">Kasir ini tidak ada transaksi di rentang waktu yang dipilih.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <x-empty-state
                                    icon="cash-register"
                                    title="Pilih Kasir"
                                    description="Pilih kasir dari filter di atas untuk lihat tutup kasir." />
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
