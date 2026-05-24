@extends('layouts.app')

@section('title', 'Pesanan')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Pesanan</h1>
                <div class="section-header-button">
                    <a href="{{ route('order.export', request()->query()) }}" class="btn btn-outline-success">
                        <i class="fas fa-file-excel mr-1"></i> Export Excel
                    </a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Pesanan</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                {{-- Summary stats --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary-soft">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-label">Total Pesanan (filter)</div>
                            <div class="stat-value">{{ number_format($totalOrders, 0, ',', '.') }}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card">
                            <div class="stat-icon bg-success-soft">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="stat-label">Total Pendapatan (filter)</div>
                            <div class="stat-value">{{ rupiah($totalRevenue) }}</div>
                        </div>
                    </div>
                </div>

                {{-- Filter --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('order.index') }}">
                                <div class="form-row">
                                    <div class="col-md-4 form-group">
                                        <label class="text-muted" style="font-size:12px;">Cari order # / customer</label>
                                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari...">
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="text-muted" style="font-size:12px;">Dari tanggal</label>
                                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="text-muted" style="font-size:12px;">Sampai</label>
                                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="text-muted" style="font-size:12px;">Pembayaran</label>
                                        <select name="payment_method" class="form-control">
                                            <option value="">Semua</option>
                                            @foreach ($paymentMethods as $pm)
                                                <option value="{{ $pm }}" @selected(request('payment_method') === $pm)>{{ strtoupper($pm) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group">
                                        <label class="text-muted" style="font-size:12px;">Status</label>
                                        <select name="status" class="form-control">
                                            <option value="">Semua</option>
                                            <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                                            <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                                            <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                                            <option value="refunded" @selected(request('status') === 'refunded')>Refund</option>
                                        </select>
                                    </div>
                                    @if (auth()->user()?->isAdmin())
                                        <div class="col-md-3 form-group">
                                            <label class="text-muted" style="font-size:12px;">Kasir</label>
                                            <select name="kasir_id" class="form-control">
                                                <option value="">Semua kasir</option>
                                                @foreach ($kasirList as $k)
                                                    <option value="{{ $k->id }}" @selected(request('kasir_id') == $k->id)>{{ $k->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                    <div class="col-md-3 form-group d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="{{ route('order.index') }}" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            @if ($orders->count() > 0)
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Waktu</th>
                                                <th>Customer</th>
                                                <th>Kasir</th>
                                                <th class="text-center">Item</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-center">Pembayaran</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('order.show', $order->id) }}" class="font-weight-bold text-primary">
                                                            {{ $order->order_number ?? '#' . $order->id }}
                                                        </a>
                                                    </td>
                                                    <td>{{ formatDate($order->transaction_time, 'd M Y H:i') }}</td>
                                                    <td>{{ $order->customer_name ?? '—' }}</td>
                                                    <td>{{ $order->kasir->name ?? '—' }}</td>
                                                    <td class="text-center">{{ $order->total_item }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($order->total_price) }}</td>
                                                    <td class="text-center">
                                                        <span class="badge badge-soft-secondary">{{ strtoupper($order->payment_method ?? '—') }}</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center" style="gap:4px;">
                                                            <a href="{{ route('order.show', $order->id) }}" class="btn btn-sm btn-info btn-icon" title="Lihat">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="{{ route('order.receipt', $order->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary btn-icon" title="Cetak Struk">
                                                                <i class="fas fa-print"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted" style="font-size:13px;">
                                        Menampilkan {{ $orders->firstItem() }}–{{ $orders->lastItem() }}
                                        dari {{ $orders->total() }}
                                    </div>
                                    <div>{{ $orders->links() }}</div>
                                </div>
                            @else
                                <x-empty-state
                                    icon="receipt"
                                    title="Belum ada pesanan"
                                    description="Pesanan dari aplikasi kasir akan muncul di sini."
                                />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
