<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiProductStoreRequest;
use App\Http\Requests\Api\ApiProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category:id,name')
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%' . $request->q . '%'))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id))
            ->orderByDesc('id')
            ->get();

        return ApiResponse::success(
            ProductResource::collection($products),
            'List produk berhasil dimuat.'
        );
    }

    public function show(Product $product)
    {
        $product->load('category:id,name');

        return ApiResponse::success(new ProductResource($product), 'Detail produk.');
    }

    public function store(ApiProductStoreRequest $request)
    {
        $data = $request->validated();

        $filename = time() . '_' . $request->image->getClientOriginalName();
        $request->image->storeAs('public/products', $filename);
        $data['image'] = $filename;

        $category = Category::find($data['category_id']);
        $data['category'] = $category?->name;

        $product = Product::create($data);
        $product->load('category:id,name');

        return ApiResponse::success(new ProductResource($product), 'Produk berhasil ditambahkan.', 201);
    }

    public function update(ApiProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            // Drop the old file (best-effort — don't fail update if missing).
            if (! empty($product->image)) {
                Storage::delete('public/products/' . $product->image);
            }
            $filename = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->storeAs('public/products', $filename);
            $data['image'] = $filename;
        } else {
            // Don't overwrite the existing image filename when no file uploaded.
            unset($data['image']);
        }

        // Keep the denormalized `category` text column in sync when category_id
        // changes (same pattern as store()).
        if (array_key_exists('category_id', $data)) {
            $category = Category::find($data['category_id']);
            $data['category'] = $category?->name;
        }

        $product->update($data);
        $product->load('category:id,name');

        return ApiResponse::success(new ProductResource($product), 'Produk berhasil diperbarui.');
    }
}
