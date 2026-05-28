@extends('layouts.app')

@section('title', 'Penjualan per Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Penjualan per Produk</h1>
                <div class="section-header-button">
                    <a href="{{ route('reports.export', ['type' => 'product-sales', 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()]) }}"
                        class="btn btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i> Export
                    </a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Per Produk</div>
                </div>
            </div>

            <div class="section-body">
                <x-reports-filter action="{{ route('reports.product-sales') }}" :from="$from" :to="$to" />

                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="m-0">Ranking Produk</h4>
                                <span class="text-muted">Total: <strong>{{ rupiah($totalRevenue) }}</strong></span>
                            </div>
                            @if ($items->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Produk</th>
                                                <th>Kategori</th>
                                                <th class="text-center">Qty</th>
                                                <th class="text-right">Pendapatan</th>
                                                <th class="text-right">% Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($items as $i => $r)
                                                <tr>
                                                    <td><span class="badge badge-soft-primary">{{ $i + 1 }}</span></td>
                                                    <td class="font-weight-bold">{{ $r->name }}</td>
                                                    <td>{{ $r->category ?? '—' }}</td>
                                                    <td class="text-center">{{ (int) $r->qty_sold }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($r->revenue) }}</td>
                                                    <td class="text-right">{{ $r->percentage }}%</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p class="text-muted m-0">Tidak ada penjualan di rentang ini.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
