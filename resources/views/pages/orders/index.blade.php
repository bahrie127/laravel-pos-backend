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
                <h2 class="section-title">Orders</h2>



                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>All Orders</h4>
                            </div>Orders
                            <div class="card-body">

                                {{-- <div class="float-right">
                                    <form method="GET" action="{{ route('product.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div> --}}

                                <div class="clearfix mb-3"></div>

                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <tr>

                                            <th>Transaction Time</th>
                                            <th>Total Price</th>
                                            <th>Total Item</th>
                                            <th>Kasir</th>
                                        </tr>
                                        @foreach ($orders as $order)
                                            <tr>

                                                <td><a
                                                        href="{{ route('order.show', $order->id) }}">{{ $order->transaction_time }}</a>
                                                </td>
                                                <td>
                                                    {{ $order->total_price }}
                                                </td>
                                                <td>
                                                    {{ $order->total_item }}
                                                </td>
                                                <td>
                                                    {{ $order->kasir->name }}

                                                </td>

                                            </tr>
                                        @endforeach


                                    </table>
                                </div>
                                <div class="float-right">
                                    {{ $orders->withQueryString()->links() }}
                                </div>
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
