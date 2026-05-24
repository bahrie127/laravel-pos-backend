<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $allowedSorts = ['name', 'price', 'stock', 'created_at'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $products = Product::query()
            ->with('category:id,name')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->stock_filter === 'low', fn ($q) => $q->whereBetween('stock', [1, 4]))
            ->when($request->stock_filter === 'out', fn ($q) => $q->where('stock', 0))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('pages.products.index', compact('products', 'categories', 'sort', 'direction'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pages.products.create', compact('categories'));
    }

    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();
        $data['is_best_seller'] = $request->boolean('is_best_seller');

        if ($request->hasFile('image')) {
            $filename = time() . '_' . $request->image->getClientOriginalName();
            $request->image->storeAs('public/products', $filename);
            $data['image'] = $filename;
        }

        $category = Category::find($data['category_id']);
        $data['category'] = $category?->name;

        Product::create($data);

        if ($request->boolean('save_and_new')) {
            return redirect()->route('product.create')->with('success', 'Produk berhasil ditambahkan. Tambahkan produk lain.');
        }

        return redirect()->route('product.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);
        $categories = Category::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pages.products.edit', compact('product', 'categories'));
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['is_best_seller'] = $request->boolean('is_best_seller');

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::delete('public/products/' . $product->image);
            }
            $filename = time() . '_' . $request->image->getClientOriginalName();
            $request->image->storeAs('public/products', $filename);
            $data['image'] = $filename;
        }

        $category = Category::find($data['category_id']);
        $data['category'] = $category?->name;

        $product->update($data);

        return redirect()->route('product.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        if ($product->image) {
            Storage::delete('public/products/' . $product->image);
        }
        $product->delete();

        return redirect()->route('product.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $this->authorize('create', Product::class); // sama level dengan create/delete

        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ])['ids'];

        $products = Product::whereIn('id', $ids)->get();
        foreach ($products as $product) {
            if ($product->image) {
                Storage::delete('public/products/' . $product->image);
            }
            $product->delete();
        }

        return redirect()->route('product.index')
            ->with('success', count($ids) . ' produk berhasil dihapus.');
    }
}
