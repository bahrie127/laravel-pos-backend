# Products Page Enhancement

## Current Issues

`resources/views/pages/products/index.blade.php`:
- Tabel manual HTML, kolom: Name, Category, Price, Photo, Created At, Action
- Search by name only (`GET ?name=`)
- Tidak ada kolom **stock**, **is_best_seller**, **description**
- Price tampil mentah (tanpa format Rupiah)
- Created at tampil raw datetime
- Image preview kecil `100px` thumbnail, tidak ada lightbox
- Tidak ada filter by category, sort by price/stock
- Tidak ada empty state
- Action: Edit + Delete (native confirm via JS bawaan `confirm-delete` class)
- Breadcrumb "Forms > Product" salah (harusnya Master Data > Produk)

`resources/views/pages/products/create.blade.php`:
- Form fields: Name, Price, Stock, Category, Photo
- Tidak ada field: `description`, `is_best_seller`, `category` (kolom string lama)
- Image upload: input file biasa, tanpa preview, tanpa drag-drop
- Tidak ada price formatter (rupiah mask)
- Tidak ada character counter di description
- Tombol submit generic "Submit"

## Target Enhancement

### Index page

```blade
@extends('layouts.app')
@section('title', __('Produk'))

@section('main')
<div class="space-y-4">
    {{-- Page header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold">{{ __('Produk') }}</h1>
            <p class="text-sm text-gray-500">{{ __(':total produk terdaftar', ['total' => $products->total()]) }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('product.export') }}" class="btn btn-secondary">
                <x-icon name="arrow-down-tray" class="w-4 h-4 mr-2"/> {{ __('Export') }}
            </a>
            <a href="{{ route('product.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="w-4 h-4 mr-2"/> {{ __('Tambah Produk') }}
            </a>
        </div>
    </div>
    
    {{-- Filter bar --}}
    <div class="bg-white rounded-xl shadow-sm border p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ request('q') }}" 
                   placeholder="{{ __('Cari nama produk...') }}"
                   class="form-input">
            
            <select name="category_id" class="form-select">
                <option value="">{{ __('Semua Kategori') }}</option>
                @foreach ($categories as $c)
                    <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            
            <select name="stock_filter" class="form-select">
                <option value="">{{ __('Semua Stok') }}</option>
                <option value="low" @selected(request('stock_filter') == 'low')>{{ __('Stok Menipis (<5)') }}</option>
                <option value="out" @selected(request('stock_filter') == 'out')>{{ __('Stok Habis (0)') }}</option>
            </select>
            
            <div class="flex gap-2">
                <button class="btn btn-primary flex-1">{{ __('Filter') }}</button>
                <a href="{{ route('product.index') }}" class="btn btn-ghost">{{ __('Reset') }}</a>
            </div>
        </form>
    </div>
    
    {{-- Bulk action toolbar (visible only when selected) --}}
    <div id="bulk-toolbar" class="hidden bg-primary-50 border border-primary-200 rounded-xl p-3 flex items-center justify-between">
        <span class="text-sm"><span id="selected-count">0</span> {{ __('produk terpilih') }}</span>
        <div class="flex gap-2">
            <button class="btn btn-sm btn-danger" id="bulk-delete">
                <x-icon name="trash" class="w-4 h-4 mr-1"/> {{ __('Hapus') }}
            </button>
        </div>
    </div>
    
    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        @if ($products->count() > 0)
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="p-3 w-10"><input type="checkbox" id="select-all"/></th>
                        <th class="p-3 text-left">{{ __('Produk') }}</th>
                        <th class="p-3 text-left">{{ __('Kategori') }}</th>
                        <th class="p-3 text-right">
                            <x-sort-link column="price">{{ __('Harga') }}</x-sort-link>
                        </th>
                        <th class="p-3 text-center">
                            <x-sort-link column="stock">{{ __('Stok') }}</x-sort-link>
                        </th>
                        <th class="p-3 text-center">{{ __('Best Seller') }}</th>
                        <th class="p-3 text-center">{{ __('Aksi') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($products as $product)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="p-3"><input type="checkbox" class="row-select" value="{{ $product->id }}"/></td>
                            <td class="p-3">
                                <div class="flex items-center gap-3">
                                    @if ($product->image)
                                        <img src="{{ asset('storage/products/' . $product->image) }}"
                                             class="w-12 h-12 rounded-lg object-cover cursor-pointer"
                                             data-lightbox="product-images">
                                    @else
                                        <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <x-icon name="photo" class="w-6 h-6 text-gray-400"/>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-medium">{{ $product->name }}</p>
                                        <p class="text-xs text-gray-500">#{{ $product->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3">
                                @if ($product->category_rel)
                                    <span class="badge badge-gray">{{ $product->category_rel->name }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-right font-medium">
                                Rp {{ number_format($product->price, 0, ',', '.') }}
                            </td>
                            <td class="p-3 text-center">
                                @if ($product->stock == 0)
                                    <span class="badge badge-danger">{{ __('Habis') }}</span>
                                @elseif ($product->stock < 5)
                                    <span class="badge badge-warning">{{ $product->stock }} {{ __('tersisa') }}</span>
                                @else
                                    <span>{{ $product->stock }}</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                @if ($product->is_best_seller)
                                    <x-icon name="star" class="w-5 h-5 text-warning-500 mx-auto"/>
                                @endif
                            </td>
                            <td class="p-3">
                                <div class="flex justify-center gap-1">
                                    <a href="{{ route('product.edit', $product) }}" class="btn-icon" title="Edit">
                                        <x-icon name="pencil" class="w-4 h-4"/>
                                    </a>
                                    <button class="btn-icon btn-icon-danger confirm-delete"
                                            data-action="{{ route('product.destroy', $product) }}"
                                            title="Hapus">
                                        <x-icon name="trash" class="w-4 h-4"/>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="p-4 border-t flex justify-between items-center">
                <p class="text-sm text-gray-500">
                    {{ __('Menampilkan :from-:to dari :total', [
                        'from' => $products->firstItem(),
                        'to' => $products->lastItem(),
                        'total' => $products->total()
                    ]) }}
                </p>
                {{ $products->withQueryString()->links() }}
            </div>
        @else
            <x-empty-state 
                icon="cube"
                title="{{ __('Belum ada produk') }}"
                description="{{ __('Tambahkan produk pertama untuk mulai berjualan.') }}"
                action-label="{{ __('Tambah Produk') }}"
                action-url="{{ route('product.create') }}"
            />
        @endif
    </div>
</div>
@endsection
```

### Create/Edit page

Improvements:
- 2-column layout: kiri info dasar, kanan upload foto + status
- Image upload: drag-drop area dengan preview, multi-image support (Phase 2 via Spatie medialibrary)
- Price input: pakai cleave.js untuk format `1.250.000`
- Description: textarea dengan character counter
- Toggle `is_best_seller`: switch component
- Validasi server: required, numeric min, image max 2MB, unique slug
- Tombol: "Simpan" + "Simpan & Tambah Lagi" + "Batal"

```blade
{{-- Sketch --}}
<form action="{{ route('product.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @csrf
    
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Informasi Produk') }}</h3>
            
            <x-form-input name="name" label="Nama Produk" required/>
            <x-form-textarea name="description" label="Deskripsi" rows="4" maxlength="500" counter/>
            
            <div class="grid grid-cols-2 gap-4">
                <x-form-input name="price" label="Harga" type="text" data-mask="rupiah" required/>
                <x-form-input name="stock" label="Stok" type="number" min="0" required/>
            </div>
            
            <x-form-select name="category_id" label="Kategori" :options="$categories" required/>
        </div>
    </div>
    
    <div class="space-y-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Foto Produk') }}</h3>
            <x-image-upload name="image" :preview="$product->image ?? null"/>
        </div>
        
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Status') }}</h3>
            <x-form-toggle name="is_best_seller" label="Tandai sebagai Best Seller"/>
        </div>
        
        <div class="flex flex-col gap-2">
            <button class="btn btn-primary">{{ __('Simpan') }}</button>
            <button name="save_and_new" value="1" class="btn btn-secondary">{{ __('Simpan & Tambah Lagi') }}</button>
            <a href="{{ route('product.index') }}" class="btn btn-ghost">{{ __('Batal') }}</a>
        </div>
    </div>
</form>
```

### Controller changes

```php
// app/Http/Controllers/ProductController.php
public function index(Request $request)
{
    $query = Product::query()->with('category_rel:id,name');
    
    if ($request->filled('q')) {
        $query->where('name', 'like', "%{$request->q}%");
    }
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }
    if ($request->stock_filter === 'low') {
        $query->where('stock', '<', 5)->where('stock', '>', 0);
    }
    if ($request->stock_filter === 'out') {
        $query->where('stock', 0);
    }
    
    $sort = $request->get('sort', 'created_at');
    $direction = $request->get('direction', 'desc');
    $allowedSorts = ['name', 'price', 'stock', 'created_at'];
    if (in_array($sort, $allowedSorts)) {
        $query->orderBy($sort, $direction);
    }
    
    $products = $query->paginate(15);
    $categories = Category::orderBy('name')->get(['id', 'name']);
    
    return view('pages.products.index', compact('products', 'categories'));
}

public function bulkDestroy(Request $request)
{
    $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
    Product::whereIn('id', $ids)->delete();
    
    return back()->with('success', __(':count produk dihapus', ['count' => count($ids)]));
}

public function export()
{
    return Excel::download(new ProductsExport, 'products-' . now()->format('Ymd') . '.xlsx');
}
```

### Action Items

- [ ] Tambah filter category & stock filter di index
- [ ] Sort header (clickable) untuk kolom name, price, stock
- [ ] Bulk select + bulk delete
- [ ] Image lightbox di kolom photo
- [ ] Format Rupiah untuk harga (helper `rupiah()`)
- [ ] Stock badge: hijau >5, kuning <5, merah 0
- [ ] Best seller indicator (star icon)
- [ ] Empty state component
- [ ] Create form: image preview, currency mask, character counter, "Simpan & Tambah Lagi"
- [ ] Edit form: tampilkan existing image, opsi hapus image
- [ ] Validation: `FormRequest` class (ProductStoreRequest, ProductUpdateRequest)
- [ ] Export Excel
- [ ] Hapus field `category` (string lama, sudah ada `category_id` foreign key)
- [ ] Rename relasi: `category()` → `category_rel()` atau tetap `category()` tapi pastikan migration `category` column dihapus
