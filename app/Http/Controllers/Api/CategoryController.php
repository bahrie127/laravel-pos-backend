<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Responses\ApiResponse;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success(
            CategoryResource::collection($categories),
            'List kategori berhasil dimuat.'
        );
    }

    public function store(Request $request)
    {
        if (! $request->user()->can('create', Category::class)) {
            return ApiResponse::error('Anda tidak memiliki izin membuat kategori.', 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = Category::create($data + [
            'icon' => $data['icon'] ?? 'tag',
            'color' => $data['color'] ?? '#3B82F6',
            'is_active' => $data['is_active'] ?? true,
        ]);

        return ApiResponse::success(
            new CategoryResource($category->loadCount('products')),
            'Kategori berhasil dibuat.',
            201
        );
    }

    public function update(Request $request, Category $category)
    {
        if (! $request->user()->can('update', $category)) {
            return ApiResponse::error('Anda tidak memiliki izin mengubah kategori.', 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update($data);

        return ApiResponse::success(
            new CategoryResource($category->fresh()->loadCount('products')),
            'Kategori diperbarui.'
        );
    }

    public function destroy(Request $request, Category $category)
    {
        if (! $request->user()->can('delete', $category)) {
            return ApiResponse::error('Anda tidak memiliki izin menghapus kategori.', 403);
        }

        if ($category->products()->exists()) {
            return ApiResponse::error(
                'Kategori tidak bisa dihapus karena masih memiliki produk.',
                422
            );
        }

        $category->delete();

        return ApiResponse::success(null, 'Kategori dihapus.');
    }
}
