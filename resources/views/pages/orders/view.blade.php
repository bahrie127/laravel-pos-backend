@extends('layouts.app')

@section('title', 'Order Detail')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Order Detail</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Orders</a></div>
                    <div class="breadcrumb-item">Order Detail</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-lg-8 col-12">
                        <div class="card-clean">
                            <h4 class="mb-3">Item Pesanan</h4>
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
                                                            <img src="{{ asset('storage/products/' . $item->product->image) }}"
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
                    </div>

                    <div class="col-lg-4 col-12">
                        <div class="card-clean mb-3">
                            <h4 class="mb-3">Ringkasan</h4>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Total Item</span>
                                <span>{{ $order->total_item }} item</span>
                            </div>
                            <div class="d-flex justify-content-between py-2 border-bottom">
                                <span class="text-muted">Pembayaran</span>
                                <span class="badge badge-soft-secondary">{{ strtoupper($order->payment_method ?? '—') }}</span>
                            </div>
                            <div class="d-flex justify-content-between py-3 mt-2" style="font-size:18px;font-weight:700;">
                                <span>Total</span>
                                <span>{{ rupiah($order->total_price) }}</span>
                            </div>
                        </div>

                        <div class="card-clean">
                            <h4 class="mb-3">Informasi</h4>
                            <div class="mb-2">
                                <div class="text-muted" style="font-size:12px;">Waktu Transaksi</div>
                                <div class="font-weight-bold">{{ $order->transaction_time?->translatedFormat('l, d F Y H:i') ?? '—' }}</div>
                            </div>
                            <div>
                                <div class="text-muted" style="font-size:12px;">Kasir</div>
                                <div class="font-weight-bold">{{ $order->kasir->name ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <!-- JS Libraies -->
    <script src="{{ asset('library/selectric/public/jquery.selectric.min.js') }}"></script>

    <!-- Page Specific JS File -->
    <script src="{{ asset('js/page/features-posts.js') }}"></script>
@endpush
