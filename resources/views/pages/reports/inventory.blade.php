@extends('layouts.app')

@section('title', 'Laporan Stok')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Laporan Stok</h1>
                <div class="section-header-button">
                    @php
                        $exportParams = ['type' => 'inventory', 'category_id' => $categoryId, 'stock_status' => $stockStatus];
                    @endphp
                    <div class="dropdown d-inline-block">
                        <button type="button" class="btn btn-outline-success dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-download mr-1"></i> Download
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'xlsx'])) }}">
                                <i class="fas fa-file-excel text-success mr-2"></i> Excel
                            </a>
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'csv'])) }}">
                                <i class="fas fa-file-csv text-info mr-2"></i> CSV
                            </a>
                            <a class="dropdown-item" href="{{ route('reports.export', array_merge($exportParams, ['format' => 'pdf'])) }}" target="_blank">
                                <i class="fas fa-file-pdf text-danger mr-2"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Stok</div>
                </div>
            </div>

            <div class="section-body">
                {{-- Filter --}}
                <div class="card-clean mb-3">
                    <form method="GET" action="{{ route('reports.inventory') }}" class="form-row">
                        <div class="col-md-3 form-group m-0">
                            <label class="text-muted" style="font-size:12px;">Kategori</label>
                            <select name="category_id" class="form-control form-control-sm">
                                <option value="">Semua Kategori</option>
                                @foreach ($categoryList as $c)
                                    <option value="{{ $c->id }}" @selected($categoryId == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 form-group m-0">
                            <label class="text-muted" style="font-size:12px;">Status Stok</label>
                            <select name="stock_status" class="form-control form-control-sm">
                                <option value="">Semua Status</option>
                                <option value="in" @selected($stockStatus === 'in')>Aman (≥5)</option>
                                <option value="low" @selected($stockStatus === 'low')>Menipis (1–4)</option>
                                <option value="out" @selected($stockStatus === 'out')>Habis (0)</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group m-0 d-flex align-items-end" style="gap:6px;">
                            <button class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                            <a href="{{ route('reports.inventory') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                {{-- Stats --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft"><i class="fas fa-cubes"></i></div>
                            <div class="stat-label">Jumlah SKU</div>
                            <div class="stat-value">{{ number_format($totals['sku_count'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft"><i class="fas fa-warehouse"></i></div>
                            <div class="stat-label">Total Unit</div>
                            <div class="stat-value">{{ number_format($totals['total_units'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft"><i class="fas fa-coins"></i></div>
                            <div class="stat-label">Nilai Stok</div>
                            <div class="stat-value">{{ rupiah($totals['total_value']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="stat-label">Perlu Restock</div>
                            <div class="stat-value">{{ $totals['out_count'] + $totals['low_count'] }}</div>
                            <div class="text-muted" style="font-size:11px;">
                                {{ $totals['out_count'] }} habis · {{ $totals['low_count'] }} menipis
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            @if ($products->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Produk</th>
                                                <th>Kategori</th>
                                                <th class="text-center">Stok</th>
                                                <th class="text-right">Harga</th>
                                                <th class="text-right">Nilai Stok</th>
                                                <th class="text-center">Total Terjual</th>
                                                <th>Terakhir Terjual</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($products as $p)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $p->name }}</td>
                                                    <td>{{ $p->cat_name }}</td>
                                                    <td class="text-center">{{ $p->stock }}</td>
                                                    <td class="text-right">{{ rupiah($p->price) }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($p->stock * $p->price) }}</td>
                                                    <td class="text-center">{{ $p->total_sold }}</td>
                                                    <td>{{ formatDate($p->last_sold_at, 'd M Y', 'Belum terjual') }}</td>
                                                    <td class="text-center">
                                                        @if ($p->stock <= 0)
                                                            <span class="badge badge-soft-danger">Habis</span>
                                                        @elseif ($p->stock < 5)
                                                            <span class="badge badge-soft-warning">Menipis</span>
                                                        @else
                                                            <span class="badge badge-soft-success">Aman</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-box-open fa-3x mb-3" style="opacity:0.3;"></i>
                                    <p>Tidak ada produk yang cocok dengan filter ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
