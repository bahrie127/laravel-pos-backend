@extends('layouts.app')

@section('title', 'Produk')

@section('main')
    <div class="main-content">
        <section class="section">
            <div class="section-header">
                <h1>Produk</h1>
                <div class="section-header-button">
                    @can('create', App\Models\Product::class)
                        <a href="{{ route('product.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus mr-1"></i> Tambah Produk
                        </a>
                    @endcan
                </div>
                <div class="section-header-breadcrumb">
                    <div class="breadcrumb-item active"><a href="{{ route('home') }}">Dashboard</a></div>
                    <div class="breadcrumb-item">Produk</div>
                </div>
            </div>

            <div class="section-body">
                <div class="row">
                    <div class="col-12">
                        @include('layouts.alert')
                    </div>
                </div>

                {{-- Filter --}}
                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            <form method="GET" action="{{ route('product.index') }}">
                                <div class="form-row">
                                    <div class="col-md-4 form-group">
                                        <label class="text-muted" style="font-size:12px;">Cari nama</label>
                                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari produk...">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="text-muted" style="font-size:12px;">Kategori</label>
                                        <select name="category_id" class="form-control">
                                            <option value="">Semua kategori</option>
                                            @foreach ($categories as $c)
                                                <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="text-muted" style="font-size:12px;">Stok</label>
                                        <select name="stock_filter" class="form-control">
                                            <option value="">Semua stok</option>
                                            <option value="low" @selected(request('stock_filter') === 'low')>Menipis (1-4)</option>
                                            <option value="out" @selected(request('stock_filter') === 'out')>Habis (0)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 form-group d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="{{ route('product.index') }}" class="btn btn-outline-secondary">Reset</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Bulk action toolbar (hidden by default) --}}
                @can('create', App\Models\Product::class)
                    <div class="row" id="bulk-toolbar-row" style="display:none;">
                        <div class="col-12">
                            <div class="card-clean d-flex justify-content-between align-items-center"
                                 style="background:#EFF6FF;border-color:#BFDBFE;">
                                <div>
                                    <strong id="bulk-count">0</strong> produk terpilih
                                </div>
                                <form method="POST" action="{{ route('product.bulk-destroy') }}" id="bulk-delete-form" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <div id="bulk-ids-container"></div>
                                    <button type="submit" class="btn btn-danger btn-sm confirm-delete"
                                            data-title="Hapus produk terpilih?"
                                            data-text="Aksi ini akan menghapus semua produk yang terpilih.">
                                        <i class="fas fa-trash mr-1"></i> Hapus Terpilih
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan

                <div class="row">
                    <div class="col-12">
                        <div class="card-clean">
                            @if ($products->count() > 0)
                                <div class="table-responsive">
                                    <table class="table-striped table">
                                        <thead>
                                            <tr>
                                                @can('create', App\Models\Product::class)
                                                    <th style="width:36px;">
                                                        <input type="checkbox" id="select-all" title="Pilih semua">
                                                    </th>
                                                @endcan
                                                <th>Foto</th>
                                                <th><x-sort-link column="name" :current="$sort" :direction="$direction">Nama</x-sort-link></th>
                                                <th>Kategori</th>
                                                <th class="text-right"><x-sort-link column="price" :current="$sort" :direction="$direction">Harga</x-sort-link></th>
                                                <th class="text-center"><x-sort-link column="stock" :current="$sort" :direction="$direction">Stok</x-sort-link></th>
                                                <th class="text-center">Best Seller</th>
                                                <th><x-sort-link column="created_at" :current="$sort" :direction="$direction">Dibuat</x-sort-link></th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($products as $product)
                                                <tr>
                                                    @can('create', App\Models\Product::class)
                                                        <td>
                                                            <input type="checkbox" class="row-select" value="{{ $product->id }}">
                                                        </td>
                                                    @endcan
                                                    <td>
                                                        @if ($product->image)
                                                            <img src="{{ $product->image_url }}"
                                                                alt="{{ $product->name }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                                                        @else
                                                            <div style="width:56px;height:56px;border-radius:8px;background:#f3f4f6;display:flex;align-items:center;justify-content:center;color:#9ca3af;">
                                                                <i class="fas fa-image"></i>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td class="font-weight-bold">{{ $product->name }}</td>
                                                    <td>{{ $product->category->name ?? $product->category ?? '—' }}</td>
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
                                                    <td class="text-center">
                                                        @if ($product->is_best_seller)
                                                            <i class="fas fa-star text-warning" title="Best Seller"></i>
                                                        @endif
                                                    </td>
                                                    <td>{{ formatDate($product->created_at, 'd M Y') }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-center">
                                                            @can('update', $product)
                                                                <a href='{{ route('product.edit', $product->id) }}'
                                                                    class="btn btn-sm btn-info btn-icon">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                            @can('delete', $product)
                                                                <form action="{{ route('product.destroy', $product->id) }}"
                                                                    method="POST" class="ml-2">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-sm btn-danger btn-icon confirm-delete"
                                                                        data-title="Hapus produk?"
                                                                        data-text="Produk '{{ $product->name }}' akan dihapus permanen.">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted" style="font-size:13px;">
                                        Menampilkan {{ $products->firstItem() }}–{{ $products->lastItem() }}
                                        dari {{ $products->total() }} produk
                                    </div>
                                    <div>{{ $products->links() }}</div>
                                </div>
                            @else
                                <x-empty-state
                                    icon="box-open"
                                    title="Belum ada produk"
                                    description="Tambahkan produk pertama untuk mulai berjualan."
                                    :action-label="auth()->user()->can('create', App\Models\Product::class) ? 'Tambah Produk' : null"
                                    :action-url="auth()->user()->can('create', App\Models\Product::class) ? route('product.create') : null"
                                />
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('select-all');
            const rows = document.querySelectorAll('.row-select');
            const toolbar = document.getElementById('bulk-toolbar-row');
            const countEl = document.getElementById('bulk-count');
            const idsContainer = document.getElementById('bulk-ids-container');

            function refresh() {
                if (!toolbar) return;
                const selected = Array.from(rows).filter(r => r.checked).map(r => r.value);
                if (selected.length > 0) {
                    toolbar.style.display = '';
                    countEl.textContent = selected.length;
                    idsContainer.innerHTML = selected.map(id =>
                        `<input type="hidden" name="ids[]" value="${id}">`
                    ).join('');
                } else {
                    toolbar.style.display = 'none';
                    idsContainer.innerHTML = '';
                }
            }

            if (selectAll) {
                selectAll.addEventListener('change', e => {
                    rows.forEach(r => r.checked = e.target.checked);
                    refresh();
                });
            }
            rows.forEach(r => r.addEventListener('change', refresh));
        })();
    </script>
@endpush
