<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ApiOrderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'transaction_time' => ['required', 'date'],
            'kasir_id' => ['required', 'exists:users,id'],
            'total_price' => ['required', 'numeric', 'min:0'],
            'total_item' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'promo_id' => ['nullable', 'integer', 'exists:promos,id'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric', 'min:0'],
            'order_items' => ['required', 'array', 'min:1'],
            'order_items.*.product_id' => ['required', 'exists:products,id'],
            'order_items.*.quantity' => ['required', 'numeric', 'min:1'],
            'order_items.*.total_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
