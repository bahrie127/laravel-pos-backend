# Step 12 — CRUD Products

## Tujuan

CRUD Produk lengkap dengan: filter (kategori, stok), search, sort header, bulk delete, image upload + preview, currency mask, best-seller toggle, badge stock color (hijau/kuning/merah).

## Prasyarat

- Step 11 selesai

## Konteks

Produk = master data utama. UI nya paling banyak fitur: 6 kolom filter, image lightbox, bulk operation.

## Prompt untuk AI

````
Project Laravel POS sudah punya CRUD Categories. Sekarang buat CRUD Products.

A. ROUTE

1. `routes/web.php`:
```php
Route::delete('product/bulk', [\App\Http\Controllers\ProductController::class, 'bulkDestroy'])->name('product.bulk-destroy');
Route::resource('product', \App\Http\Controllers\ProductController::class);
```

B. CONTROLLER

2. `app/Http/Controllers/ProductController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller {
    public function __construct() {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request) {
        $query = Product::with('category:id,name');
        if ($request->filled('q')) $query->where('name','like','%'.$request->q.'%');
        if ($request->filled('category_id')) $query->where('category_id', $request->category_id);
        if ($request->stock_filter === 'low') $query->whereBetween('stock', [1, 4]);
        if ($request->stock_filter === 'out') $query->where('stock', 0);

        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');
        if (in_array($sort, ['name','price','stock','created_at'])) {
            $query->orderBy($sort, $direction);
        }

        $products = $query->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get(['id','name']);
        return view('pages.products.index', compact('products','categories','sort','direction'));
    }

    public function create() {
        $product = new Product(['is_best_seller' => false]);
        $categories = Category::orderBy('name')->get(['id','name']);
        return view('pages.products.create', compact('product','categories'));
    }

    public function store(ProductStoreRequest $request) {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
            // simpan filename only
            $data['image'] = basename($data['image']);
        }
        // backward-compat: text category
        $data['category'] = Category::find($data['category_id'])->name ?? 'food';
        Product::create($data);

        if ($request->has('save_and_new')) {
            return redirect()->route('product.create')->with('success', __('Produk ditambahkan. Tambah produk lain?'));
        }
        return redirect()->route('product.index')->with('success', __('messages.created', ['resource' => 'Produk']));
    }

    public function edit(Product $product) {
        $categories = Category::orderBy('name')->get(['id','name']);
        return view('pages.products.edit', compact('product','categories'));
    }

    public function update(ProductUpdateRequest $request, Product $product) {
        $data = $request->validated();
        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete('products/'.$product->image);
            $data['image'] = basename($request->file('image')->store('products','public'));
        }
        $data['category'] = Category::find($data['category_id'])->name ?? $product->category;
        $product->update($data);
        return redirect()->route('product.index')->with('success', __('messages.updated', ['resource' => 'Produk']));
    }

    public function destroy(Product $product) {
        if ($product->image) Storage::disk('public')->delete('products/'.$product->image);
        $product->delete();
        return back()->with('success', __('messages.deleted', ['resource' => 'Produk']));
    }

    public function bulkDestroy(Request $request) {
        $ids = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer'])['ids'];
        $this->authorize('delete', Product::class);
        $products = Product::whereIn('id', $ids)->get();
        foreach ($products as $p) {
            if ($p->image) Storage::disk('public')->delete('products/'.$p->image);
        }
        Product::whereIn('id', $ids)->delete();
        return back()->with('success', __(':count produk dihapus.', ['count' => count($ids)]));
    }
}
```

C. FORM REQUEST

3. `app/Http/Requests/ProductStoreRequest.php`:
```php
class ProductStoreRequest extends FormRequest {
    public function authorize(): bool { return $this->user()->can('create', Product::class); }
    public function rules(): array {
        return [
            'name' => ['required','string','min:2','max:255','unique:products,name'],
            'description' => ['nullable','string','max:1000'],
            'price' => ['required','integer','min:0'],
            'stock' => ['required','integer','min:0'],
            'category_id' => ['required','exists:categories,id'],
            'image' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:2048'],
            'is_best_seller' => ['nullable','boolean'],
        ];
    }
    protected function prepareForValidation(): void {
        $this->merge([
            'price' => (int) str_replace(['.', 'Rp', ' '], '', (string) $this->price), // strip mask
            'is_best_seller' => $this->boolean('is_best_seller'),
        ]);
    }
}
```

4. `ProductUpdateRequest.php` — sama kecuali `name unique ignore self`:
```php
'name' => ['required','string','min:2','max:255','unique:products,name,'.$this->route('product')->id],
```

D. VIEWS

5. `resources/views/pages/products/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Produk'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Produk') }}" subtitle="{{ __(':total produk', ['total' => $products->total()]) }}">
        <x-slot:actions>
            @can('create', App\Models\Product::class)
                <a href="{{ route('product.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>{{ __('Tambah Produk') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <div class="col-md-4"><input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama produk...') }}" class="form-control"></div>
            <div class="col-md-3">
                <select name="category_id" class="form-control">
                    <option value="">{{ __('Semua Kategori') }}</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="stock_filter" class="form-control">
                    <option value="">{{ __('Semua Stok') }}</option>
                    <option value="low" @selected(request('stock_filter')==='low')>{{ __('Stok Menipis (<5)') }}</option>
                    <option value="out" @selected(request('stock_filter')==='out')>{{ __('Stok Habis') }}</option>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
        </form>
    </div>

    <form id="bulk-form" method="POST" action="{{ route('product.bulk-destroy') }}">@csrf @method('DELETE')
    <div id="bulk-toolbar" class="card-clean mb-3 d-none">
        <span><strong id="selected-count">0</strong> {{ __('produk terpilih') }}</span>
        <button type="button" class="btn btn-sm btn-danger float-end" onclick="bulkDel()"><i class="fas fa-trash"></i> {{ __('Hapus') }}</button>
    </div>

    <div class="card-clean">
        @if($products->isEmpty())
            <x-empty-state icon="box-open" title="{{ __('Belum ada produk') }}"
                description="{{ __('Tambahkan produk pertama untuk mulai berjualan.') }}"
                actionLabel="{{ __('Tambah Produk') }}" actionUrl="{{ route('product.create') }}"/>
        @else
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="text-uppercase small text-muted">
                        <tr>
                            <th><input type="checkbox" id="select-all"></th>
                            <th>{{ __('Produk') }}</th>
                            <th>{{ __('Kategori') }}</th>
                            <th class="text-end"><x-sort-link column="price" :current="$sort" :direction="$direction">{{ __('Harga') }}</x-sort-link></th>
                            <th class="text-center"><x-sort-link column="stock" :current="$sort" :direction="$direction">{{ __('Stok') }}</x-sort-link></th>
                            <th class="text-center">{{ __('Best Seller') }}</th>
                            <th class="text-center">{{ __('Aksi') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $p)
                        <tr>
                            <td><input type="checkbox" name="ids[]" form="bulk-form" value="{{ $p->id }}" class="row-select"></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    @if($p->image)
                                        <img src="{{ asset('storage/products/'.$p->image) }}" class="rounded me-2" style="width:48px;height:48px;object-fit:cover">
                                    @else
                                        <div class="rounded bg-light me-2 d-flex align-items-center justify-content-center" style="width:48px;height:48px"><i class="fas fa-image text-muted"></i></div>
                                    @endif
                                    <div><div class="fw-semibold">{{ $p->name }}</div><div class="small text-muted">#{{ $p->id }}</div></div>
                                </div>
                            </td>
                            <td>@if($p->category)<span class="badge bg-light text-dark">{{ $p->category->name }}</span>@endif</td>
                            <td class="text-end fw-medium">{{ rupiah($p->price) }}</td>
                            <td class="text-center">
                                @if($p->stock == 0)<span class="badge bg-danger">{{ __('Habis') }}</span>
                                @elseif($p->stock < 5)<span class="badge bg-warning text-dark">{{ $p->stock }}</span>
                                @else<span>{{ $p->stock }}</span>
                                @endif
                            </td>
                            <td class="text-center">@if($p->is_best_seller)<i class="fas fa-star text-warning"></i>@endif</td>
                            <td class="text-center">
                                @can('update', $p)<a href="{{ route('product.edit', $p) }}" class="btn btn-sm btn-light"><i class="fas fa-pencil-alt"></i></a>@endcan
                                @can('delete', $p)<button class="btn btn-sm btn-light text-danger confirm-delete" data-action="{{ route('product.destroy', $p) }}"><i class="fas fa-trash"></i></button>@endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">{{ __('messages.showing', ['from' => $products->firstItem(), 'to' => $products->lastItem(), 'total' => $products->total()]) }}</small>
                {{ $products->links() }}
            </div>
        @endif
    </div>
    </form>
</section>

@push('scripts')
<script>
const sa = document.getElementById('select-all');
const tb = document.getElementById('bulk-toolbar');
const sc = document.getElementById('selected-count');
sa?.addEventListener('change', () => { document.querySelectorAll('.row-select').forEach(c => c.checked = sa.checked); upd(); });
document.querySelectorAll('.row-select').forEach(c => c.addEventListener('change', upd));
function upd() {
    const n = document.querySelectorAll('.row-select:checked').length;
    tb.classList.toggle('d-none', n === 0);
    sc.textContent = n;
}
function bulkDel() {
    if (!confirm('Hapus produk terpilih?')) return;
    document.getElementById('bulk-form').submit();
}
</script>
@endpush
@endsection
```

6. `resources/views/pages/products/_form.blade.php`:
```blade
@php $isEdit = $product->exists; @endphp
<form action="{{ $isEdit ? route('product.update', $product) : route('product.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Informasi Produk') }}</h5>
                <x-form-input name="name" label="Nama Produk" :value="$product->name" required/>
                <x-form-textarea name="description" label="Deskripsi" :value="$product->description" rows="4" maxlength="1000" counter/>
                <div class="row">
                    <div class="col-md-6">
                        <x-form-input name="price" label="Harga" :value="$product->price ? rupiah($product->price, false) : ''" required icon="dollar-sign"/>
                    </div>
                    <div class="col-md-6"><x-form-input name="stock" label="Stok" type="number" :value="$product->stock" required/></div>
                </div>
                <x-form-select name="category_id" label="Kategori" :options="$categories->pluck('name','id')->toArray()" :value="$product->category_id" required/>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-clean mb-3">
                <h5 class="mb-3">{{ __('Foto Produk') }}</h5>
                <div class="mb-2">
                    <img id="preview" src="{{ $product->image ? asset('storage/products/'.$product->image) : 'https://via.placeholder.com/300?text=Foto' }}"
                         class="img-fluid rounded">
                </div>
                <input type="file" name="image" id="imageInput" accept="image/*" class="form-control">
                @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Status') }}</h5>
                <x-form-toggle name="is_best_seller" label="Best Seller" :checked="$product->is_best_seller"/>
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-primary">{{ __('Simpan') }}</button>
                    @if(!$isEdit)
                        <button name="save_and_new" value="1" class="btn btn-outline-primary">{{ __('messages.save_and_new') }}</button>
                    @endif
                    <a href="{{ route('product.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
// Image preview
document.getElementById('imageInput')?.addEventListener('change', e => {
    const f = e.target.files[0]; if(!f) return;
    const r = new FileReader();
    r.onload = ev => document.getElementById('preview').src = ev.target.result;
    r.readAsDataURL(f);
});
// Currency mask sederhana
const price = document.querySelector('input[name="price"]');
price?.addEventListener('input', e => {
    const v = e.target.value.replace(/\D/g, '');
    e.target.value = new Intl.NumberFormat('id-ID').format(v || 0);
});
</script>
@endpush
```

7. `pages/products/create.blade.php` & `edit.blade.php` — extends layout, include `_form`.

Tampilkan struktur folder `pages/products/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/Http/Controllers/ProductController.php
app/Http/Requests/{ProductStoreRequest, ProductUpdateRequest}.php
resources/views/pages/products/{index, create, edit, _form}.blade.php
```

## Cara Test

```bash
# 1. /product → 30 produk dari seeder
# 2. Filter "Stok Habis" → hanya produk stock=0
# 3. Sort by Harga (klik header) → urut asc, klik lagi → desc
# 4. Tambah produk: nama "Es Kopi Susu Gula Aren", harga "25.000", stok 50, kategori Minuman, upload foto
# 5. Verifikasi:
#    - File tersimpan di storage/app/public/products/
#    - DB price = 25000 (mask stripped)
#    - List menampilkan produk + foto
# 6. Pilih 3 produk → bulk toolbar muncul → klik Hapus
# 7. Test "Simpan & Tambah Lagi" → balik ke create form
```

## Penjelasan untuk Murid

Talking points:

1. **"`authorizeResource()` di constructor?"** — Auto-call policy method per action. `index` → `viewAny`, `store` → `create`, dst.

2. **"`prepareForValidation()` untuk strip mask?"** — User input "25.000" → kita strip jadi "25000" SEBELUM rule `integer` validate. Tanpa ini, rule fail.

3. **"Image upload pakai `Storage::store('products','public')`?"** — Upload ke disk `public` (`storage/app/public`). Simbolik link `public/storage` (Step 07) bikin file accessible via URL.

4. **"`Storage::delete()` saat update/destroy?"** — Bersihkan file lama supaya tidak orphan. Penting untuk hemat disk.

5. **"Bulk delete pakai form + checkbox name `ids[]`?"** — Standard HTML pattern. Backend terima array di `$request->ids`.

6. **"`@selected()` directive?"** — Laravel 8+ shortcut untuk `selected="selected"`. Lebih readable.

Pertanyaan reflektif:
- "Kalau produk hanya bisa dihapus owner (bukan admin), policy diubah gimana?" (→ `delete: fn($u, $p) => $u->isOwner()`)
- "Bagaimana support import produk dari Excel?" (→ pakai Maatwebsite Excel `import`, controller `import` parse CSV, validate per row, insert batch)

---

**Next: [13-users-crud.md](13-users-crud.md)**
