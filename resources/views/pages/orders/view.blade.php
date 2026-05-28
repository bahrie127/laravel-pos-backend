@extends('layouts.app')

@section('title', 'Detail Pesanan ' . $order->order_number)

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>{{ $order->order_number ?? '#' . $order->id }}</h1>
                <div class="section-header-button">
                    <a href="{{ route('order.receipt', $order->id) }}" target="_blank" class="btn btn-outline-secondary mr-2">
                        <i class="fas fa-print mr-1"></i> Cetak Struk
                    </a>
                    <a href="{{ route('order.invoice-pdf', $order->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-file-pdf mr-1"></i> Invoice PDF
                    </a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="{{ route('order.index') }}">Pesanan</a></div>
                    <div class="breadcrumb-item">{{ $order->order_number ?? '#' . $order->id }}</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                <div class="row align-items-start">
                    <div class="col-lg-8 col-12">
                        <div class="card-clean" style="height:auto;">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h4 class="m-0">Item Pesanan</h4>
                                <span class="badge {{ $order->statusBadgeClass() }}">{{ $order->statusLabel() }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-right">Harga</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orderItems as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if ($item->product?->image)
                                                            <img src="{{ $item->product->image_url }}"
                                                                style="width:40px;height:40px;object-fit:cover;border-radius:6px;margin-right:8px;">
                                                        @endif
                                                        <span class="font-weight-bold">{{ $item->product->name ?? '—' }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-right">{{ rupiah($item->product->price ?? 0) }}</td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-right font-weight-bold">{{ rupiah($item->total_price) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($order->notes)
                            <div class="card-clean mt-3" style="height:auto;">
                                <h5 class="mb-2">Catatan</h5>
                                <p class="m-0 text-muted">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-4 col-12">
                        <div class="card-clean mb-3" style="height:auto;">
                            <h4 class="mb-3">Ringkasan</h4>
                            <div class="d-flex justify-content-between py-2">
                                <span class="text-muted">Subtotal</span>
                                <span>{{ rupiah($order->subtotal ?? $order->total_price) }}</span>
                            </div>
                            @if ($order->discount > 0)
                                <div class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Diskon</span>
                                    <span class="text-danger">-{{ rupiah($order->discount) }}</span>
                                </div>
                            @endif
                            @if ($order->tax > 0)
                                <div class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Pajak</span>
                                    <span>{{ rupiah($order->tax) }}</span>
                                </div>
                            @endif
                            <div class="d-flex justify-content-between py-3 border-top" style="font-size:18px;font-weight:700;">
                                <span>Total</span>
                                <span>{{ rupiah($order->total_price) }}</span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted" style="font-size:13px;">Pembayaran</span>
                                <span class="badge badge-soft-secondary">{{ strtoupper($order->payment_method ?? '—') }}</span>
                            </div>
                            @if ($order->amount_paid > 0)
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted" style="font-size:13px;">Dibayar</span>
                                    <span>{{ rupiah($order->amount_paid) }}</span>
                                </div>
                                <div class="d-flex justify-content-between py-1">
                                    <span class="text-muted" style="font-size:13px;">Kembalian</span>
                                    <span>{{ rupiah($order->change_amount) }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="card-clean" style="height:auto;">
                            <h4 class="mb-3">Informasi</h4>
                            <div class="mb-2">
                                <div class="text-muted" style="font-size:12px;">Waktu Transaksi</div>
                                <div class="font-weight-bold">{{ formatDate($order->transaction_time, 'l, d F Y H:i') }}</div>
                            </div>
                            <div class="mb-2">
                                <div class="text-muted" style="font-size:12px;">Kasir</div>
                                <div class="font-weight-bold">{{ $order->kasir->name ?? '—' }}</div>
                            </div>
                            @if ($order->customer_name)
                                <div class="mb-2">
                                    <div class="text-muted" style="font-size:12px;">Customer</div>
                                    <div class="font-weight-bold">{{ $order->customer_name }}</div>
                                </div>
                            @endif
                            <div>
                                <div class="text-muted" style="font-size:12px;">Total Item</div>
                                <div class="font-weight-bold">{{ $order->total_item }} item</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
