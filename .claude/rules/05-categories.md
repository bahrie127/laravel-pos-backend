# Categories Page Enhancement

## Current State

`resources/views/pages/categories/index.blade.php`:
- Tabel: Name, Created At, Action
- Search by name only
- Tidak ada kolom **jumlah produk** per kategori
- Tidak ada icon/color identifier
- Action: Edit, Delete (native confirm)

`resources/views/pages/categories/create.blade.php` & `edit.blade.php`:
- Field hanya `name`
- Tidak ada deskripsi, icon, color, atau image

## Target Enhancement

### Schema additions (migration baru)

```php
Schema::table('categories', function (Blueprint $table) {
    $table->string('slug')->unique()->after('name');
    $table->string('description')->nullable()->after('slug');
    $table->string('icon')->nullable()->after('description');  // heroicon name atau emoji
    $table->string('color', 7)->default('#3B82F6')->after('icon'); // hex
    $table->unsignedInteger('sort_order')->default(0)->after('color');
    $table->boolean('is_active')->default(true)->after('sort_order');
});
```

### Index page (target)

Layout: **grid card view** (lebih visual) + toggle ke table view.

```blade
{{-- Grid view (default) --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach ($categories as $cat)
        <div class="card group hover:shadow-md transition cursor-pointer">
            <div class="w-12 h-12 rounded-lg flex items-center justify-center mb-3"
                 style="background: {{ $cat->color }}20; color: {{ $cat->color }}">
                <x-icon name="{{ $cat->icon ?? 'tag' }}" class="w-6 h-6"/>
            </div>
            <h3 class="font-semibold">{{ $cat->name }}</h3>
            <p class="text-xs text-gray-500 mt-1">{{ $cat->products_count }} {{ __('produk') }}</p>
            
            <div class="absolute top-2 right-2 hidden group-hover:flex gap-1">
                <a href="{{ route('categories.edit', $cat) }}" class="btn-icon btn-icon-sm">
                    <x-icon name="pencil" class="w-3 h-3"/>
                </a>
                <button class="btn-icon btn-icon-sm btn-icon-danger confirm-delete"
                        data-action="{{ route('categories.destroy', $cat) }}">
                    <x-icon name="trash" class="w-3 h-3"/>
                </button>
            </div>
            
            @if (!$cat->is_active)
                <span class="badge badge-gray absolute top-2 left-2">{{ __('Nonaktif') }}</span>
            @endif
        </div>
    @endforeach
</div>
```

### Create/Edit form

```blade
<form action="{{ route('categories.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    @csrf
    
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <x-form-input name="name" label="Nama Kategori" required/>
            <x-form-textarea name="description" label="Deskripsi" rows="3"/>
        </div>
    </div>
    
    <div class="space-y-6">
        <div class="card">
            <h3 class="font-semibold mb-4">{{ __('Tampilan') }}</h3>
            
            {{-- Icon picker (heroicon dropdown atau emoji) --}}
            <div class="mb-4">
                <label class="form-label">{{ __('Icon') }}</label>
                <div class="grid grid-cols-6 gap-2">
                    @foreach (['tag', 'cube', 'gift', 'cake', 'coffee', 'sparkles'] as $iconName)
                        <button type="button" 
                                class="icon-option p-2 border rounded-lg hover:border-primary-500"
                                data-icon="{{ $iconName }}">
                            <x-icon name="{{ $iconName }}" class="w-5 h-5 mx-auto"/>
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="icon" id="selected-icon" value="{{ old('icon', $category->icon ?? 'tag') }}">
            </div>
            
            {{-- Color picker --}}
            <div class="mb-4">
                <label class="form-label">{{ __('Warna') }}</label>
                <div class="flex gap-2">
                    @foreach (['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'] as $color)
                        <button type="button" 
                                class="color-option w-8 h-8 rounded-full border-2 border-transparent"
                                style="background: {{ $color }}"
                                data-color="{{ $color }}">
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="color" id="selected-color" value="{{ old('color', $category->color ?? '#3B82F6') }}">
            </div>
            
            <x-form-toggle name="is_active" label="Kategori Aktif" :checked="$category->is_active ?? true"/>
        </div>
        
        <button class="btn btn-primary w-full">{{ __('Simpan') }}</button>
    </div>
</form>
```

### Controller

```php
public function index(Request $request)
{
    $query = Category::withCount('products');
    
    if ($request->filled('q')) {
        $query->where('name', 'like', "%{$request->q}%");
    }
    
    $categories = $query->orderBy('sort_order')->orderBy('name')->paginate(20);
    
    return view('pages.categories.index', compact('categories'));
}

public function destroy(Category $category)
{
    if ($category->products()->exists()) {
        return back()->with('error', __('Kategori tidak dapat dihapus karena masih memiliki produk.'));
    }
    
    $category->delete();
    return back()->with('success', __('Kategori dihapus.'));
}

public function reorder(Request $request)
{
    foreach ($request->order as $i => $id) {
        Category::where('id', $id)->update(['sort_order' => $i]);
    }
    return response()->json(['ok' => true]);
}
```

### Model addition

```php
// app/Models/Category.php
public function products()
{
    return $this->hasMany(Product::class);
}

protected static function booted()
{
    static::saving(function ($cat) {
        if (empty($cat->slug)) {
            $cat->slug = Str::slug($cat->name);
        }
    });
}
```

## Action Items

- [ ] Migration: tambah kolom slug, description, icon, color, sort_order, is_active
- [ ] Index page: grid view dengan icon + color visualization
- [ ] Icon picker & color picker di form
- [ ] Toggle is_active
- [ ] Cegah delete kategori yang masih punya produk
- [ ] Drag & drop reorder (Phase 2, pakai Sortable.js)
- [ ] Tampilkan jumlah produk per kategori
- [ ] FormRequest validation
- [ ] API endpoint `list-categories` ikut include icon, color, dan products_count
