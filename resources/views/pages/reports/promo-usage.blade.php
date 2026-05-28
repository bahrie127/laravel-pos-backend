@extends('layouts.app')

@section('title', 'Pemakaian Promo')

@php
    $typeLabel = ['percent' => 'Persen', 'rupiah' => 'Rupiah', 'b1g1' => 'B1G1'];
@endphp

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Pemakaian Promo</h1>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('reports.index') }}">Laporan</a></div>
                    <div class="breadcrumb-item">Pemakaian Promo</div>
                </div>
            </div>

            <div class="section-body">
                <x-reports-filter action="{{ route('reports.promo-usage') }}" :from="$from" :to="$to" />

                {{-- Stat cards --}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft"><i class="fas fa-receipt"></i></div>
                            <div class="stat-label">Trx Pakai Promo</div>
                            <div class="stat-value">{{ number_format($totals['orders_using_promo'], 0, ',', '.') }}</div>
                            <div class="text-muted" style="font-size:12px;">{{ $totals['promo_usage_rate'] }}% dari total transaksi</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning-soft"><i class="fas fa-tags"></i></div>
                            <div class="stat-label">Total Diskon Dipotong</div>
                            <div class="stat-value">{{ rupiah($totals['total_discount']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info-soft"><i class="fas fa-arrow-up"></i></div>
                            <div class="stat-label">Omset Kotor (sebelum diskon)</div>
                            <div class="stat-value">{{ rupiah($totals['gross_revenue']) }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft"><i class="fas fa-wallet"></i></div>
                            <div class="stat-label">Omset Bersih (setelah diskon)</div>
                            <div class="stat-value">{{ rupiah($totals['net_revenue']) }}</div>
                        </div>
                    </div>
                </div>

                {{-- Per-promo breakdown --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <h4 class="mb-3">Breakdown per Promo</h4>

                            @if ($perPromo->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Promo</th>
                                                <th>Kode</th>
                                                <th>Tipe</th>
                                                <th class="text-right">Nilai</th>
                                                <th class="text-center">Dipakai</th>
                                                <th class="text-right">Total Diskon</th>
                                                <th class="text-right">Net Revenue</th>
                                                <th class="text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($perPromo as $row)
                                                <tr>
                                                    <td class="font-weight-bold">{{ $row->name }}</td>
                                                    <td>
                                                        @if ($row->code)
                                                            <code style="background:#F3F4F6;padding:2px 6px;border-radius:4px;">{{ $row->code }}</code>
                                                        @else
                                                            <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td><span class="badge badge-soft-info">{{ $typeLabel[$row->type] ?? $row->type }}</span></td>
                                                    <td class="text-right">
                                                        @if ($row->type === 'percent') {{ $row->value }}%
                                                        @elseif ($row->type === 'rupiah') {{ rupiah($row->value) }}
                                                        @else <span class="text-muted">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">{{ $row->usage_count }}</td>
                                                    <td class="text-right text-warning font-weight-bold">{{ rupiah($row->total_discount) }}</td>
                                                    <td class="text-right">{{ rupiah($row->net_revenue) }}</td>
                                                    <td class="text-center">
                                                        @if ($row->active)
                                                            <span class="badge badge-soft-success">Aktif</span>
                                                        @else
                                                            <span class="badge badge-soft-secondary">Nonaktif</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            <tr style="background:#F9FAFB;font-weight:bold;">
                                                <td colspan="4" class="text-right">Total</td>
                                                <td class="text-center">{{ $perPromo->sum('usage_count') }}</td>
                                                <td class="text-right text-warning">{{ rupiah($perPromo->sum('total_discount')) }}</td>
                                                <td class="text-right">{{ rupiah($perPromo->sum('net_revenue')) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-tags fa-3x mb-3" style="opacity:0.3;"></i>
                                    <p>Belum ada transaksi yang memakai promo di periode ini.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Unused promos --}}
                @if ($unusedPromos->count() > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card-clean">
                                <h4 class="mb-2">Promo Tidak Terpakai</h4>
                                <p class="text-muted" style="font-size:13px;">Promo berikut belum pernah dipakai di periode ini — pertimbangkan untuk dipromosikan atau dinonaktifkan.</p>

                                <div class="d-flex flex-wrap" style="gap:8px;">
                                    @foreach ($unusedPromos as $p)
                                        <a href="{{ route('promo.edit', $p->id) }}" class="badge {{ $p->active ? 'badge-soft-warning' : 'badge-soft-secondary' }}"
                                            style="font-size:13px;padding:8px 12px;text-decoration:none;">
                                            {{ $p->name }}
                                            @if ($p->code) <code style="margin-left:4px;">{{ $p->code }}</code> @endif
                                            @if (! $p->active) <small>(nonaktif)</small> @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
