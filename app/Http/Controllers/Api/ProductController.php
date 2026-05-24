<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ApiProductStoreRequest;
use App\Http\Resources\ProductResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

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
}
