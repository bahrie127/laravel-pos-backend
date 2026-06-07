# Step 11 — CRUD Categories

## Tujuan

Mempunyai **CRUD Categories lengkap**: list (grid + list view toggle), create, edit, delete dengan icon picker + color picker + slug auto + cegah delete kalau masih ada produk.

## Prasyarat

- Step 10 selesai

## Konteks

Categories adalah master data sederhana, tapi punya 6 kolom (slug, description, icon, color, sort_order, is_active). UI nya pakai grid card view untuk lebih visual.

## Prompt untuk AI

````
Project Laravel POS sudah punya dashboard. Sekarang buat CRUD Categories.

A. ROUTE

1. `routes/web.php` dalam group auth:
```php
Route::resource('categories', \App\Http\Controllers\CategoryController::class);
```

B. CONTROLLER

2. `app/Http/Controllers/CategoryController.php`:
```php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\Request;

class CategoryController extends Controller {
    public function __construct() {
        $this->authorizeResource(Category::class, 'category');
    }

    public function index(Request $request) {
        $view = $request->get('view', 'grid'); // grid|list
        $query = Category::withCount('products');
        if ($request->filled('q')) $query->where('name','like','%'.$request->q.'%');
        $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20)->withQueryString();
        return view('pages.categories.index', compact('categories', 'view'));
    }

    public function create() {
        $category = new Category(['icon' => 'tag', 'color' => '#3B82F6', 'is_active' => true]);
        return view('pages.categories.create', compact('category'));
    }

    public function store(CategoryRequest $request) {
        Category::create($request->validated());
        return redirect()->route('categories.index')
            ->with('success', __('messages.created', ['resource' => 'Kategori']));
    }

    public function edit(Category $category) {
        return view('pages.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category) {
        $category->update($request->validated());
        return redirect()->route('categories.index')
            ->with('success', __('messages.updated', ['resource' => 'Kategori']));
    }

    public function destroy(Category $category) {
        if ($category->products()->exists()) {
            return back()->with('error', __('Kategori tidak bisa dihapus karena masih memiliki produk.'));
        }
        $category->delete();
        return back()->with('success', __('messages.deleted', ['resource' => 'Kategori']));
    }
}
```

C. FORM REQUEST

3. `app/Http/Requests/CategoryRequest.php`:
```php
class CategoryRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        $id = $this->route('category')?->id;
        return [
            'name' => ['required','string','min:2','max:100','unique:categories,name,'.$id],
            'description' => ['nullable','string','max:500'],
            'icon' => ['nullable','string','max:50'],
            'color' => ['nullable','string','regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['nullable','boolean'],
            'sort_order' => ['nullable','integer','min:0'],
        ];
    }
    protected function prepareForValidation(): void {
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }
}
```

D. VIEWS

4. `resources/views/pages/categories/index.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Kategori'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Kategori') }}" subtitle="{{ __(':total kategori', ['total' => $categories->total()]) }}">
        <x-slot:actions>
            <a href="{{ route('categories.index', ['view' => 'grid']) }}" class="btn btn-sm btn-{{ $view==='grid'?'primary':'outline-secondary' }}"><i class="fas fa-th"></i></a>
            <a href="{{ route('categories.index', ['view' => 'list']) }}" class="btn btn-sm btn-{{ $view==='list'?'primary':'outline-secondary' }}"><i class="fas fa-list"></i></a>
            @can('create', App\Models\Category::class)
                <a href="{{ route('categories.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>{{ __('Tambah Kategori') }}</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <div class="card-clean mb-3">
        <form method="GET" class="row g-2">
            <input type="hidden" name="view" value="{{ $view }}">
            <div class="col-md-6">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Cari nama kategori...') }}" class="form-control">
            </div>
            <div class="col-md-3"><button class="btn btn-primary w-100">{{ __('Filter') }}</button></div>
            <div class="col-md-3"><a href="{{ route('categories.index') }}" class="btn btn-light w-100">{{ __('Reset') }}</a></div>
        </form>
    </div>

    @if($categories->isEmpty())
        <x-empty-state icon="tags" title="{{ __('Belum ada kategori') }}"
            description="{{ __('Tambahkan kategori untuk mengelompokkan produk.') }}"
            actionLabel="{{ __('Tambah Kategori') }}" actionUrl="{{ route('categories.create') }}"/>
    @elseif($view === 'grid')
        <div class="row">
            @foreach($categories as $cat)
                <div class="col-md-3 mb-3">
                    <div class="card-clean h-100 position-relative">
                        <div class="rounded-3 d-flex align-items-center justify-content-center mb-3"
                             style="width:48px;height:48px;background:{{ $cat->color }}22;color:{{ $cat->color }}">
                            <i class="fas fa-{{ $cat->icon }} fa-lg"></i>
                        </div>
                        <h6 class="mb-1">{{ $cat->name }}</h6>
                        <p class="small text-muted mb-2">{{ $cat->products_count }} {{ __('produk') }}</p>
                        @if(!$cat->is_active)<span class="badge bg-secondary">{{ __('Nonaktif') }}</span>@endif
                        <div class="position-absolute top-0 end-0 p-2 d-flex gap-1">
                            @can('update', $cat)<a href="{{ route('categories.edit', $cat) }}" class="btn btn-sm btn-light"><i class="fas fa-pencil-alt"></i></a>@endcan
                            @can('delete', $cat)<button class="btn btn-sm btn-light text-danger confirm-delete" data-action="{{ route('categories.destroy', $cat) }}"><i class="fas fa-trash"></i></button>@endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="card-clean">
            <table class="table table-hover align-middle">
                <thead><tr><th></th><th>{{ __('Nama') }}</th><th>{{ __('Produk') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @foreach($categories as $cat)
                        <tr>
                            <td><span class="rounded-3 d-inline-flex align-items-center justify-content-center" style="width:36px;height:36px;background:{{ $cat->color }}22;color:{{ $cat->color }}"><i class="fas fa-{{ $cat->icon }}"></i></span></td>
                            <td><strong>{{ $cat->name }}</strong><div class="small text-muted">{{ $cat->slug }}</div></td>
                            <td>{{ $cat->products_count }}</td>
                            <td>@if($cat->is_active)<span class="badge bg-success">{{ __('Aktif') }}</span>@else<span class="badge bg-secondary">{{ __('Nonaktif') }}</span>@endif</td>
                            <td class="text-end">
                                @can('update', $cat)<a href="{{ route('categories.edit', $cat) }}" class="btn btn-sm btn-light"><i class="fas fa-pencil-alt"></i></a>@endcan
                                @can('delete', $cat)<button class="btn btn-sm btn-light text-danger confirm-delete" data-action="{{ route('categories.destroy', $cat) }}"><i class="fas fa-trash"></i></button>@endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-3">{{ $categories->links() }}</div>
</section>
@endsection
```

5. `resources/views/pages/categories/_form.blade.php`:
```blade
@php $isEdit = isset($category) && $category->exists; @endphp
<form action="{{ $isEdit ? route('categories.update', $category) : route('categories.store') }}" method="POST">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <div class="row">
        <div class="col-lg-8">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Informasi Kategori') }}</h5>
                <x-form-input name="name" label="Nama Kategori" :value="$category->name" required/>
                <x-form-textarea name="description" label="Deskripsi" :value="$category->description" rows="3" maxlength="500" counter/>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-clean">
                <h5 class="mb-3">{{ __('Tampilan') }}</h5>
                <div class="mb-3">
                    <label class="form-label">{{ __('Icon') }}</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach(['tag','cube','gift','cake','coffee','sparkles','star','bolt'] as $ic)
                            <button type="button" class="btn btn-sm btn-outline-secondary icon-pick" data-icon="{{ $ic }}"><i class="fas fa-{{ $ic }}"></i></button>
                        @endforeach
                    </div>
                    <input type="hidden" name="icon" id="icon-input" value="{{ old('icon', $category->icon ?? 'tag') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Warna') }}</label>
                    <div class="d-flex gap-2">
                        @foreach(['#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6','#EC4899'] as $col)
                            <button type="button" class="color-pick rounded-circle border" data-color="{{ $col }}" style="width:32px;height:32px;background:{{ $col }}"></button>
                        @endforeach
                    </div>
                    <input type="hidden" name="color" id="color-input" value="{{ old('color', $category->color ?? '#3B82F6') }}">
                </div>
                <x-form-toggle name="is_active" label="Aktif" :checked="old('is_active', $category->is_active ?? true)"/>
                <div class="mt-3 d-grid gap-2">
                    <button class="btn btn-primary">{{ __('Simpan') }}</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-light">{{ __('Batal') }}</a>
                </div>
            </div>
        </div>
    </div>
</form>
<script>
document.querySelectorAll('.icon-pick').forEach(b => b.onclick = () => document.getElementById('icon-input').value = b.dataset.icon);
document.querySelectorAll('.color-pick').forEach(b => b.onclick = () => document.getElementById('color-input').value = b.dataset.color);
</script>
```

6. `resources/views/pages/categories/create.blade.php`:
```blade
@extends('layouts.app')
@section('title', __('Tambah Kategori'))
@section('main')
<section class="section">
    <x-page-header title="{{ __('Tambah Kategori') }}"
        :breadcrumbs="[['label'=>__('Kategori'),'url'=>route('categories.index')], ['label'=>__('Tambah')]]"/>
    @include('pages.categories._form')
</section>
@endsection
```

7. `resources/views/pages/categories/edit.blade.php` (sama, beda title + breadcrumb).

Update sidebar: pastikan menu `categories.index` aktif.

Tampilkan struktur folder `pages/categories/` setelah selesai.
````

## Hasil yang Diharapkan

```
app/Http/Controllers/CategoryController.php
app/Http/Requests/CategoryRequest.php
resources/views/pages/categories/
├── index.blade.php
├── create.blade.php
├── edit.blade.php
└── _form.blade.php
```

## Cara Test

```bash
# 1. /categories → list (grid view default), 4 kategori dari seeder
# 2. Klik view toggle list → table view
# 3. Klik "Tambah Kategori" → form muncul
# 4. Submit dengan name "Promo Special", icon "star", color "#EF4444"
# 5. Verifikasi:
#    - Redirect ke /categories dengan toast "Kategori berhasil ditambahkan"
#    - Kategori baru muncul di grid dengan icon+color
#    - Slug "promo-special" auto-generated (cek di DB)
# 6. Edit kategori → ubah ke icon "bolt"
# 7. Coba delete kategori yang ada produknya → error toast "tidak bisa dihapus"
# 8. Delete kategori kosong → sukses
```

## Penjelasan untuk Murid

Talking points:

1. **"Toggle view grid/list — kenapa via query string?"** — Bookmarkable, shareable. Tidak butuh JS state management. Server-side render based on `?view=grid`.

2. **"`prepareForValidation()` di FormRequest?"** — Mutasi request SEBELUM validasi. `is_active` checkbox kalau tidak di-check tidak muncul di payload → default ke true.

3. **"`unique:categories,name,'.$id`?"** — Unique kecuali untuk id sendiri (saat edit). Tanpa ini, edit-tanpa-ubah-nama akan error "name already taken".

4. **"`firstOrCreate` vs `create`?"** — Sudah dipakai di seeder. Di controller `create`, pakai `create()` aja karena form pasti new record.

5. **"Cegah delete kalau ada child relation — pattern?"** — Cek `$model->relation()->exists()`. Atau pakai FK `restrictOnDelete()` (DB-level guard). Web layer guard friendlier (pesan custom), DB guard last-line defense.

6. **"Icon picker pakai button array — kenapa tidak FontAwesome chooser?"** — Sederhana. 8 icon sudah cukup untuk kategori POS. Kalau butuh banyak, pakai library.

Pertanyaan reflektif:
- "Bagaimana support drag-drop reorder di list view?" (→ pakai Sortable.js, AJAX POST `categories.reorder` simpan `sort_order`)
- "Kalau seller minta hierarchical category (parent/child), schema-nya gimana?" (→ tambah `parent_id` self-referencing, atau pakai package `nestedset`)

---

**Next: [12-products-crud.md](12-products-crud.md)**
