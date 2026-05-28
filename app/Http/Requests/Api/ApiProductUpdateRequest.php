<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApiProductUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // V1: any authenticated user may edit a product (matches the
        // permissive ProductPolicy@update). The auth:sanctum middleware
        // already guarantees a logged-in user before we get here.
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // The route parameter is bound to the Product model by the time the
        // controller is invoked, but here it can still be a string id. Handle
        // both forms so the unique-rule's ignore clause works either way.
        $param = $this->route('product');
        $productId = is_object($param) ? $param->id : (int) $param;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                Rule::unique('products', 'name')->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            // Image is optional on update — keep existing if not provided.
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'is_best_seller' => ['nullable', 'boolean'],
        ];
    }
}
