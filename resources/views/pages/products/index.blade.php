@extends('layouts.app')

@section('title', 'Products')

@push('style')
    <!-- CSS Libraries -->
    <link rel="stylesheet" href="{{ asset('library/selectric/public/selectric.css') }}">
@endpush

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Products</h1>
                <div class="section-header-button">
                    <a href="{{ route('product.create') }}" class="btn btn-primary">Add New</a>
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="#">Dashboard</a></div>
                    <div class="breadcrumb-item"><a href="#">Products</a></div>
                    <div class="breadcrumb-item">All Products</div>
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

                                <div class="float-right">
                                    <form method="GET" action="{{ route('product.index') }}">
                                        <div class="input-group">
                                            <input type="text" class="form-control" placeholder="Search" name="name">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                            </div>
                                        </div>
                                    </form>
                                </div>

                                <div class="clearfix mb-3"></div>

                                @if ($products->count() > 0)
                                    <div class="table-responsive">
                                        <table class="table-striped table">
                                            <tr>
                                                <th>Foto</th>
                                                <th>Nama</th>
                                                <th>Kategori</th>
                                                <th class="text-right">Harga</th>
                                                <th class="text-center">Stok</th>
                                                <th>Dibuat</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                            @foreach ($products as $product)
                                                <tr>
                                                    <td>
                                                        @if ($product->image)
                                                            <img src="{{ asset('storage/products/' . $product->image) }}"
                                                                alt="{{ $product->name }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                                                        @else
                                                            <div style="width:56px;height:56px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="font-weight-bold">{{ $product->name }}</td>
                                                    <td>{{ $product->category ?? '—' }}</td>
                                                    <td class="text-right font-weight-bold">{{ rupiah($product->price) }}</td>
                                                    <td class="text-center">
                                                        @if (! is_null($product->stock) && $product->stock == 0)
                                                            <span class="badge badge-soft-danger">Habis</span>
                                                        @elseif (! is_null($product->stock) && $product->stock < 5)
                                                            <span class="badge badge-soft-warning">{{ $product->stock }} tersisa</span>
                                                        @else
                                                            <span>{{ $product->stock ?? '—' }}</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ formatDate($product->created_at, 'd M Y') }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-center">
                                                            <a href='{{ route('product.edit', $product->id) }}'
                                                                class="btn btn-sm btn-info btn-icon">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>

                                                            <form action="{{ route('product.destroy', $product->id) }}"
                                                                method="POST" class="ml-2">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                    data-title="Hapus produk?"
                                                                    data-text="Produk '{{ $product->name }}' akan dihapus permanen.">
                                                                    <i class="fas fa-trash"></i> Hapus
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                    <div class="float-right">
                                        {{ $products->withQueryString()->links() }}
                                    </div>
                                @else
                                    <x-empty-state
                                        icon="box-open"
                                        title="Belum ada produk"
                                        description="Tambahkan produk pertama untuk mulai berjualan."
                                        :action-label="'Tambah Produk'"
                                        :action-url="route('product.create')"
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
