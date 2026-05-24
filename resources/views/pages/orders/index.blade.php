@extends('layouts.app')

@section('title', 'Orders')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Orders</h1>

                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Orders</a></div>
                    <div class="breadcrumb-item">All Orders</div>
                </div>
            </div>
            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">

                            <div class="card-body">



                                <div class="clearfix mb-3"></div>

                                @if ($orders->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <tr>
                                                <th>Order</th>
                                                <th>Waktu</th>
                                                <th class="text-right">Total</th>
                                                <th class="text-center">Item</th>
                                                <th>Kasir</th>
                                                <th class="text-center">Pembayaran</th>
                                            </tr>
                                            @foreach ($orders as $order)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('order.show', $order->id) }}" class="font-weight-bold text-primary">
                                                            #{{ $order->id }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $order->transaction_time?->translatedFormat('d M Y H:i') ?? '—' }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($order->total_price) }}</td>
                                                    <td class="text-center">{{ $order->total_item }}</td>
                                                    <td>{{ $order->kasir->name ?? '—' }}</td>
                                                    <td class="text-center">
                                                        <span class="badge badge-soft-secondary">{{ strtoupper($order->payment_method ?? '—') }}</span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                    <div class="float-right">
                                        {{ $orders->withQueryString()->links() }}
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
